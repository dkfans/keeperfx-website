<?php

namespace App\Controller;

use App\Entity\GitCommit;
use App\Entity\GithubAlphaBuild;
use App\Entity\GithubRelease;
use Compwright\PhpSession\Session;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class StaticPageController {

    public function screenshotsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ){
        $response->getBody()->write(
            $twig->render('screenshots.html.twig')
        );

        return $response;
    }

    public function changelogIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        $tag
    ){

        $release = $em->getRepository(GithubRelease::class)->findBy(['tag' => $tag]);

        if(!$release){
            // bye
        }

        $commits = $em->getRepository(GitCommit::class)->findBy(['release' => $release], ['timestamp' => 'DESC']);

        if(!$commits){
            // bye
        }

        $response->getBody()->write(
            $twig->render('changelog.html.twig', [
                'release' => $release[0],
                'commits' => $commits,
            ])
        );

        return $response;
    }

}
