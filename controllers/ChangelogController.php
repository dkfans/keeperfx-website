<?php

namespace App\Controller;

use App\Entity\GitCommit;
use App\Entity\GithubRelease;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ChangelogController {

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
