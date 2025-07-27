<?php

namespace App\Controller\DevCP;

use App\Entity\CrashReport;

use App\FlashMessage;
use App\Config\Config;

use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class ModerateCrashReportController
{

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ) {
        $response->getBody()->write(
            $twig->render('devcp/crash-report/crash-report.list.devcp.html.twig', [
                'crash_reports'   => $em->getRepository(CrashReport::class)->findBy([], ['id' => 'DESC'])
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
        $id
    ) {
        $error = [];

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

            // Check for error pattern
            $error_pattern = '/Exception ([0-9xa-fA-F]+) thrown\: ([0-9_A-Za-f]+)\nError\: (.+)/';
            if (\preg_match($error_pattern, $game_log, $error_matches)) {

                $error = [
                    'exception_code' => $error_matches[1],
                    'identifier'     => $error_matches[2],
                    'error_message'  => $error_matches[3],
                ];

                // Check for trace
                $trace_pattern = '/\[\#(\d+)\s?\]\s(.+)$/m';
                if (\preg_match_all($trace_pattern, $game_log, $trace_matches, PREG_PATTERN_ORDER)) {

                    $trace = [];
                    for ($i = 0; $i < \count($trace_matches[0]); $i++) {
                        $trace[(int)$trace_matches[1][$i]] = (string)$trace_matches[2][$i];
                    }

                    $error['trace'] = $trace;
                }
            }
        }

        // Show output
        $response->getBody()->write(
            $twig->render('devcp/crash-report/crash-report.devcp.html.twig', [
                'crash_report' => $crash_report,
                'error'        => $error,
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
        $id
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

        // Remove the crash report
        $em->remove($crash_report);
        $em->flush();

        // Navigate back to list
        $flash->success('Crash report removed.');
        $response = $response->withHeader('Location', '/dev/crash-report/list')->withStatus(302);
        return $response;
    }
}
