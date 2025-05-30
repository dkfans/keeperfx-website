<?php

namespace App\Controller\Api\v1;

use App\Enum\UserRole;

use App\Entity\CrashReport;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\UploadSizeHelper;
use App\Notifications\NotificationCenter;
use App\Notifications\Notification\CrashReportNotification;

use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Psr\SimpleCache\CacheInterface;

class CrashReportApiController {

    public function upload(
        Request $request,
        Response $response,
        EntityManager $em,
        NotificationCenter $nc,
    ){
        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        // Get POST data
        $post = $request->getParsedBody();
        if($post == null){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'INVALID_OR_MISSING_POST_DATA'
                ])
            );
            return $response->withStatus(500);
        }

        // Create crash report entity
        $crash_report = new CrashReport();
        $crash_report->setDescription((string) ($post['description'] ?? ''));
        $crash_report->setGameConfig((string) ($post['game_config'] ?? ''));
        $crash_report->setGameLog((string) ($post['game_log'] ?? ''));
        $crash_report->setGameOutput((string) ($post['game_output'] ?? ''));
        $crash_report->setSource((string) ($post['source'] ?? 'N/A'));

        // Add contact details
        if(isset($post['contact_details']) && \is_string($post['contact_details'])){
            $crash_report->setContactDetails($post['contact_details']);
        }

        // Make sure a game version is defined
        if(!\array_key_exists('game_version', $post) || !\is_string($post['game_version']) || \strlen($post['game_version']) == 0){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'NO_GAME_VERSION_DEFINED'
                ])
            );
            return $response;
        } else {
            $crash_report->setGameVersion($post['game_version']);
        }

        // Check if savefile is included in request
        if(
            array_key_exists('save_file_name', $post) && \is_string($post['save_file_name']) && \strlen($post['save_file_name']) > 0
            && array_key_exists('save_file_data', $post) && \is_string($post['save_file_data']) && \strlen($post['save_file_data']) > 0
        ) {

            // Check if savefile storage dir is configured
            $savefile_storage_dir = Config::get('storage.path.crash-report-savefile');
            if($savefile_storage_dir == null || $savefile_storage_dir == ''){
                $response->getBody()->write(
                    \json_encode([
                        'success' => false,
                        'error'   => 'NO_SAVEFILE_STORAGE_DIR_CONFIGURED'
                    ])
                );
                return $response;
            }

            // Check (and try to make) savefile storage dir
            if(!\file_exists($savefile_storage_dir) || !\is_writable($savefile_storage_dir)) {
                if(!@mkdir($savefile_storage_dir, 0777, true)){
                    $response->getBody()->write(
                        \json_encode([
                            'success' => false,
                            'error'   => 'NO_SAVEFILE_STORAGE_DIR'
                        ])
                    );
                    return $response;
                }
            }

            // Get savefile filename and base64 data
            $savefile_filename = $post['save_file_name'];
            $savefile_data     = \base64_decode($post['save_file_data']);

            // Make sure the savefile is not too big
            $file_size_in_bytes = \strlen($savefile_data);
            if($file_size_in_bytes > (int)$_ENV['APP_CRASH_REPORT_SAVEFILE_MAX_UPLOAD_SIZE']){
                $response->getBody()->write(
                    \json_encode([
                        'success' => false,
                        'error'   => 'SAVEFILE_TOO_BIG'
                    ])
                );
                return $response;
            }

            // Get and check extension
            $savefile_ext = \strtolower(\pathinfo($savefile_filename, \PATHINFO_EXTENSION));
            if(!\in_array($savefile_ext, ['sav', 'zip', '7z'])){
                $response->getBody()->write(
                    \json_encode([
                        'success' => false,
                        'error'   => 'INVALID_SAVEFILE_FILENAME_EXTENSION'
                    ])
                );
                return $response;
            }

            // Generate storage filename and path
            $savefile_storage_filename = \substr(\md5(\random_int(\PHP_INT_MIN, \PHP_INT_MAX) . time()), 0, 16) . '.' . $savefile_ext;
            $savefile_storage_path = $savefile_storage_dir . '/' . $savefile_storage_filename;

            // Store file
            @\file_put_contents($savefile_storage_path, $savefile_data);
            if(!\file_exists($savefile_storage_path)){
                $response->getBody()->write(
                    \json_encode([
                        'success' => false,
                        'error'   => 'FAILED_TO_STORE_SAFEFILE'
                    ])
                );
                return $response;
            }

            $crash_report->setSaveFilename($savefile_storage_filename);
        }

        // Save to DB
        $em->persist($crash_report);
        $em->flush();

        // Notify the developers
        $nc->sendNotificationToAllWithRole(
            UserRole::Developer,
            CrashReportNotification::class,
            [
                'id' => $crash_report->getId(),
                'game_version' => $crash_report->getGameVersion(),
            ]
        );

        // Return success and include new ID
        $response->getBody()->write(
            \json_encode([
                'success' => true,
                'id'      => $crash_report->getId()
            ])
        );
        return $response;
    }

}
