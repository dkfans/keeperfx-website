<?php

namespace App\Controller\Api\v1;

use App\Config\Config;
use App\Entity\CrashReport;
use App\Enum\UserRole;
use App\Notifications\Notification\CrashReportNotification;
use App\Notifications\NotificationCenter;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Xenokore\Utility\Helper\StringHelper;

class CrashReportApiController
{
    public function upload(
        Request $request,
        Response $response,
        EntityManager $em,
        NotificationCenter $nc,
    ) {
        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        // Get POST data
        $post = $request->getParsedBody();
        if ($post == null) {
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'INVALID_OR_MISSING_POST_DATA',
                ])
            );

            return $response->withStatus(500);
        }

        // Get game log
        $game_log = '';
        if (isset($post['game_log']) && \is_string($post['game_log'])) {
            // Check if game log is gzip+base64 encoded
            if (isset($post['game_log_encoding']) && \is_string($post['game_log_encoding']) && $post['game_log_encoding'] === 'gzip+base64') {
                $game_log = \gzdecode(\base64_decode($post['game_log']));
            } else {
                $game_log = $post['game_log'];
            }
        }

        // Create crash report entity
        $crash_report = new CrashReport();
        $crash_report->setDescription((string) ($post['description'] ?? ''));
        $crash_report->setGameConfig((string) ($post['game_config'] ?? ''));
        $crash_report->setGameLog($game_log);
        $crash_report->setGameOutput((string) ($post['game_output'] ?? ''));
        $crash_report->setSource((string) ($post['source'] ?? 'N/A'));

        // Check for exception source function
        if (\preg_match('/\[\#\d+\s?\]\s(?:keeperfx|KEEPERFX|KeeperFX)\S+?\s+\:\s+(\S+)\s+\[/', $game_log, $matches)) {
            $exception_source_function = $matches[1];
            if (!empty($exception_source_function) && StringHelper::startsWith($exception_source_function, '@') == false) {
                $crash_report->setExceptionSourceFunction($exception_source_function);
            }
        }

        // Add contact details
        if (isset($post['contact_details']) && \is_string($post['contact_details'])) {
            $crash_report->setContactDetails($post['contact_details']);
        }

        // Exit code of the game process. Windows crash codes are NTSTATUS values
        // (e.g. 0xC0000005), which arrive as negative signed 32-bit integers.
        if (isset($post['exit_code']) && \is_numeric($post['exit_code'])) {
            $crash_report->setExitCode(\sprintf('0x%08X', ((int) $post['exit_code']) & 0xFFFFFFFF));
        }

        // Make sure a game version is defined
        if (!\array_key_exists('game_version', $post) || !\is_string($post['game_version']) || \strlen($post['game_version']) == 0) {
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'NO_GAME_VERSION_DEFINED',
                ])
            );

            return $response;
        }
        $crash_report->setGameVersion($post['game_version']);

        // Savefile
        $savefile = $this->storeUploadedFile(
            $post,
            'save_file_name',
            'save_file_data',
            'crash-report-savefile',
            (int) $_ENV['APP_CRASH_REPORT_SAVEFILE_MAX_UPLOAD_SIZE'],
            ['sav', 'zip', '7z'],
            'SAVEFILE',
        );
        if ($savefile['error'] !== null) {
            $response->getBody()->write(\json_encode(['success' => false, 'error' => $savefile['error']]));

            return $response;
        }
        $crash_report->setSaveFilename($savefile['filename']);
        $stored_files = $savefile['filename'] !== null ? [Config::get('storage.path.crash-report-savefile') . '/' . $savefile['filename']] : [];

        // Crash dump (keeperfx_crash_*.dmp, usually compressed by the launcher)
        // and the replay the game was recording or playing when it crashed (.fxpkt)
        $attachments = [
            ['crash_dump_name', 'crash_dump_data', 'crash-report-dump', 'APP_CRASH_REPORT_DUMP_MAX_UPLOAD_SIZE', ['dmp', 'gz', '7z', 'zip'], 'CRASH_DUMP', 'setCrashDumpFilename'],
            ['replay_file_name', 'replay_file_data', 'crash-report-replay', 'APP_CRASH_REPORT_REPLAY_MAX_UPLOAD_SIZE', ['fxpkt', 'pck'], 'REPLAY', 'setReplayFilename'],
        ];
        foreach ($attachments as [$name_key, $data_key, $storage_key, $max_size_env, $extensions, $label, $setter]) {
            $stored = $this->storeUploadedFile($post, $name_key, $data_key, $storage_key, (int) ($_ENV[$max_size_env] ?? 33554432), $extensions, $label);
            if ($stored['error'] !== null) {
                foreach ($stored_files as $path) {
                    @\unlink($path);
                }
                $response->getBody()->write(\json_encode(['success' => false, 'error' => $stored['error']]));

                return $response;
            }
            if ($stored['filename'] !== null) {
                $stored_files[] = Config::get('storage.path.' . $storage_key) . '/' . $stored['filename'];
            }
            $crash_report->$setter($stored['filename']);
        }

        // Game turn of the crash. Older launchers don't send it, but the game logs it:
        // packetfile:{"turn":1234, "playback":false, "path":"..."}
        if (isset($post['replay_turn']) && \is_numeric($post['replay_turn'])) {
            $crash_report->setReplayTurn((int) $post['replay_turn']);
        } elseif (\preg_match('/packetfile:\{"turn":(\d+)/', (string) $crash_report->getGameLog(), $turn_matches)) {
            $crash_report->setReplayTurn((int) $turn_matches[1]);
        }

        // Save to DB
        $em->persist($crash_report);
        $em->flush();

        // Notify the developers
        $nc->sendNotificationToAllWithRole(
            UserRole::Developer,
            CrashReportNotification::class,
            [
                'id'           => $crash_report->getId(),
                'game_version' => $crash_report->getGameVersion(),
            ]
        );

        // Return success and include new ID
        $response->getBody()->write(
            \json_encode([
                'success' => true,
                'id'      => $crash_report->getId(),
            ])
        );

        return $response;
    }

    /**
     * Stores an optional base64-encoded file from the POST data under a random name.
     *
     * @param string $error_label used in the error codes, e.g. "SAVEFILE" gives "SAVEFILE_TOO_BIG"
     *
     * @return array{filename: ?string, error: ?string} filename is null when the request had no such file
     */
    private function storeUploadedFile(
        array $post,
        string $name_key,
        string $data_key,
        string $storage_config_key,
        int $max_size_in_bytes,
        array $allowed_extensions,
        string $error_label,
    ): array {
        $has_file = isset($post[$name_key], $post[$data_key])
            && \is_string($post[$name_key]) && $post[$name_key] !== ''
            && \is_string($post[$data_key]) && $post[$data_key] !== '';
        if (!$has_file) {
            return ['filename' => null, 'error' => null];
        }

        // Check if storage dir is configured
        $storage_dir = Config::get('storage.path.' . $storage_config_key);
        if ($storage_dir == null || $storage_dir == '') {
            return ['filename' => null, 'error' => "NO_{$error_label}_STORAGE_DIR_CONFIGURED"];
        }

        // Check (and try to make) storage dir
        if (!\file_exists($storage_dir) || !\is_writable($storage_dir)) {
            if (!@\mkdir($storage_dir, 0777, true)) {
                return ['filename' => null, 'error' => "NO_{$error_label}_STORAGE_DIR"];
            }
        }

        // Make sure the file is not too big
        $data = \base64_decode($post[$data_key]);
        if (\strlen($data) > $max_size_in_bytes) {
            return ['filename' => null, 'error' => "{$error_label}_TOO_BIG"];
        }

        // Get and check extension
        $extension = \strtolower(\pathinfo($post[$name_key], \PATHINFO_EXTENSION));
        if (!\in_array($extension, $allowed_extensions)) {
            return ['filename' => null, 'error' => "INVALID_{$error_label}_FILENAME_EXTENSION"];
        }

        // Generate storage filename and store the file
        $storage_filename = \substr(\md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . \time()), 0, 16) . '.' . $extension;
        $storage_path     = $storage_dir . '/' . $storage_filename;
        @\file_put_contents($storage_path, $data);
        if (!\file_exists($storage_path)) {
            return ['filename' => null, 'error' => "FAILED_TO_STORE_{$error_label}"];
        }

        return ['filename' => $storage_filename, 'error' => null];
    }
}
