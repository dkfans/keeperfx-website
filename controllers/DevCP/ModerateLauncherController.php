<?php

namespace App\Controller\DevCP;

use App\Entity\GithubAlphaBuild;
use App\Entity\LauncherRelease;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpForbiddenException;

class ModerateLauncherController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $response->getBody()->write(
            $twig->render('devcp/launcher.list.devcp.html.twig', [
                'launcher_releases' => $em->getRepository(LauncherRelease::class)->findBy([],['timestamp' => 'DESC']),
            ])
        );

        return $response;
    }
}
