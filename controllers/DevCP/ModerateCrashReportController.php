<?php

namespace App\Controller\DevCP;

use App\Entity\CrashReport;

use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

class ModerateCrashReportController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('devcp/crash-report/crash-report.list.devcp.html.twig', [
                'crash_reports'   => $em->getRepository(CrashReport::class)->findBy([],['id' => 'DESC'])
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
    ){
        // Find crash report
        $crash_report = $em->getRepository(CrashReport::class)->find($id);
        if(!$crash_report){
            $flash->warning('Crash report not found.');
            $response = $response->withHeader('Location', '/dev/crash-report/list')->withStatus(302);
            return $response;
        }

        // Show output
        $response->getBody()->write(
            $twig->render('devcp/crash-report/crash-report.devcp.html.twig', [
                'crash_report' => $crash_report
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
    ){
        // Find crash report
        $crash_report = $em->getRepository(CrashReport::class)->find($id);
        if(!$crash_report){
            $flash->warning('Crash report not found.');
            $response = $response->withHeader('Location', '/dev/crash-report/list')->withStatus(302);
            return $response;
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
