<?php

namespace App\Controller\DevCP;

use App\Entity\GithubAlphaBuild;

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
        // $response->getBody()->write(
        //     $twig->render('devcp/crash-report.list.devcp.html.twig', [
        //         'alpha_builds'   => $em->getRepository(GithubAlphaBuild::class)->findBy([],['id' => 'DESC'])
        //     ])
        // );

        return $response;
    }
}
