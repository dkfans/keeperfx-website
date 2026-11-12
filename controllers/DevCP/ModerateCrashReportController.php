<?php

namespace App\Controller\DevCP;

use App\Config\Config;
use App\Entity\CrashReport;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;

class ModerateCrashReportController
{
    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
    ): Response {
        $response->getBody()->write(
            $twig->render('devcp/crash-report/crash-report.list.devcp.html.twig', [
                'crash_reports' => $em->getRepository(CrashReport::class)->findBy([], ['id' => 'DESC']),
            ])
        );

        return $response;
    }

    public function view(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $id,
    ) {
        // Separated error var
        $error = [
            'info'  => [],
            'trace' => [],
        ];

        // Find crash report
        $crash_report = $em->getRepository(CrashReport::class)->find($id);
        if (!$crash_report) {
            $flash->warning('Crash report not found.');
            $response = $response->withHeader('Location', '/dev/crash-report/list')->withStatus(302);

            return $response;
        }

        // Check if there is a game log
        $game_log = $crash_report->getGameLog();
        if ($game_log) {

            // Clear old crash lines
            $game_log = \preg_replace('/^=== Crash ===\R?/m', '', $game_log);

            // Check for error pattern
            $error_pattern = '/Exception ([0-9xa-fA-F]+) thrown\: ([0-9_A-Za-f]+)[\n\r]+Error\: (.+)/';
            if (\preg_match($error_pattern, $game_log, $error_matches)) {
                $error['info'] = [
                    'exception_code' => $error_matches[1],
                    'identifier'     => $error_matches[2],
                    'error_message'  => $error_matches[3],
                ];
            }

            // Check for trace
            $trace_pattern = '/\[\#(\d+)\s?\]\s(.+)$/m';
            if (\preg_match_all($trace_pattern, $game_log, $trace_matches, \PREG_PATTERN_ORDER)) {

                $trace = [];
                for ($i = 0; $i < \count($trace_matches[0]); ++$i) {
                    $trace[(int) $trace_matches[1][$i]] = (string) $trace_matches[2][$i];
                }

                $error['trace'] = $trace;
            }
        }

        // Show output
        $response->getBody()->write(
            $twig->render('devcp/crash-report/crash-report.devcp.html.twig', [
                'crash_report' => $crash_report,
                'error'        => $error,
                'symbols_url'  => $_ENV['APP_CRASH_REPORT_SYMBOLS_URL'] ?? 'https://raw.githubusercontent.com/dkfans/keeperfx-symbols/tables/',
            ])
        );

        return $response;
    }

    public function delete(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        FlashMessage $flash,
        $id,
    ) {
        // Find crash report
        $crash_report = $em->getRepository(CrashReport::class)->find($id);
        if (!$crash_report) {
            $flash->warning('Crash report not found.');
            $response = $response->withHeader('Location', '/dev/crash-report/list')->withStatus(302);

            return $response;
        }

        // Delete savefile
        $save_filename = $crash_report->getSaveFilename();
        if ($save_filename) {
            $dir = Config::get('storage.path.crash-report-savefile');
            if (\file_exists($dir)) {
                $filepath = $dir . '/' . $save_filename;
                if (\file_exists($filepath)) {
                    if (\unlink($filepath) === false) {
                        throw new \Exception("failed to delete savefile: {$filepath}");
                    }
                }
            }
        }

        // Delete crash dump and replay
        $attachments = [
            'crash-report-dump'   => $crash_report->getCrashDumpFilename(),
            'crash-report-replay' => $crash_report->getReplayFilename(),
        ];
        foreach ($attachments as $storage_key => $filename) {
            if (!$filename) {
                continue;
            }
            $filepath = Config::get('storage.path.' . $storage_key) . '/' . $filename;
            if (\file_exists($filepath) && \unlink($filepath) === false) {
                throw new \Exception("failed to delete {$storage_key}: {$filepath}");
            }
        }

        // Remove the crash report
        $em->remove($crash_report);
        $em->flush();

        // Navigate back to list
        $flash->success('Crash report removed.');
        $response = $response->withHeader('Location', '/dev/crash-report/list')->withStatus(302);

        return $response;
    }

    // Served through PHP rather than nginx so only developers can download dumps:
    // they hold process memory, which can include the player's username or paths.
    public function downloadCrashDump(
        Request $request,
        Response $response,
        EntityManager $em,
        FlashMessage $flash,
        $id,
    ) {
        $crash_report = $em->getRepository(CrashReport::class)->find($id);
        $filename = $crash_report?->getCrashDumpFilename();
        $extension = \pathinfo((string) $filename, \PATHINFO_EXTENSION);

        return $this->serveAttachment($response, $flash, $crash_report, 'crash-report-dump', $filename, 'dump.' . ($extension === 'gz' ? 'dmp.gz' : $extension), 'Crash dump');
    }

    public function downloadReplay(
        Request $request,
        Response $response,
        EntityManager $em,
        FlashMessage $flash,
        $id,
    ) {
        $crash_report = $em->getRepository(CrashReport::class)->find($id);
        $filename = $crash_report?->getReplayFilename();

        return $this->serveAttachment($response, $flash, $crash_report, 'crash-report-replay', $filename, 'replay.' . \pathinfo((string) $filename, \PATHINFO_EXTENSION), 'Replay');
    }

    public function downloadSave(
        Request $request,
        Response $response,
        EntityManager $em,
        FlashMessage $flash,
        $id,
    ) {
        $crash_report = $em->getRepository(CrashReport::class)->find($id);
        $filename = $crash_report?->getSaveFilename();

        return $this->serveAttachment($response, $flash, $crash_report, 'crash-report-savefile', $filename, 'save.' . \pathinfo((string) $filename, \PATHINFO_EXTENSION), 'Save file');
    }

    /** Opens a save or replay in the browser; the file never leaves the developer's machine. */
    public function saveViewer(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
    ) {
        $response->getBody()->write($twig->render('devcp/save-viewer.devcp.html.twig'));

        return $response;
    }

    private function serveAttachment(Response $response, FlashMessage $flash, ?CrashReport $crash_report, string $storage_key, ?string $filename, string $download_suffix, string $label): Response
    {
        $filepath = $filename ? Config::get('storage.path.' . $storage_key) . '/' . $filename : null;
        if ($filepath === null || !\file_exists($filepath)) {
            $flash->warning($label . ' not found.');

            return $response->withHeader('Location', '/dev/crash-report/list')->withStatus(302);
        }

        return $response
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Length', (string) \filesize($filepath))
            ->withHeader('Content-Disposition', 'attachment; filename="crash-report-' . $crash_report->getId() . '-' . $download_suffix . '"')
            ->withBody(new LazyOpenStream($filepath, 'r'));
    }
}
