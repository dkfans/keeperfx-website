<?php

namespace App\Controller\DevCP;

use App\Entity\LauncherRelease;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Twig\Environment as TwigEnvironment;

class ModerateLauncherController
{
    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
    ) {
        $response->getBody()->write(
            $twig->render('devcp/launcher.list.devcp.html.twig', [
                'launcher_releases' => $em->getRepository(LauncherRelease::class)->findBy([], ['timestamp' => 'DESC']),
            ])
        );

        return $response;
    }
}
