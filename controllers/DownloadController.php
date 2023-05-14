<?php

namespace App\Controller;

use App\Entity\GithubAlphaBuild;
use App\Entity\GithubRelease;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class DownloadController {

    public function downloadsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $stable_releases = $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC'], 3);
        $alpha_builds    = $em->getRepository(GithubAlphaBuild::class)->findBy([], ['workflow_run_id' => 'DESC', 'timestamp' => 'DESC'], 5);

        $response->getBody()->write(
            $twig->render('downloads.html.twig', [
                'stable_releases' => $stable_releases,
                'alpha_builds'    => $alpha_builds,
            ])
        );

        return $response;
    }

    public function stableDownloadsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $stable_releases = $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('downloads.stable.html.twig', [
                'stable_releases' => $stable_releases,
            ])
        );

        return $response;
    }

    public function alphaDownloadsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em
    ){
        $alpha_builds    = $em->getRepository(GithubAlphaBuild::class)->findBy([], ['timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('downloads.alpha.html.twig', [
                'alpha_builds'    => $alpha_builds,
            ])
        );

        return $response;
    }

}
