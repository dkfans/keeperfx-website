<?php

namespace App\Controller;

use App\Entity\GitCommit;
use App\Entity\GithubRelease;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CommitsController
{

    public function commitsIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        $tag
    ) {

        // Get release
        $release = $em->getRepository(GithubRelease::class)->findBy(['tag' => $tag]);
        if (!$release) {
            throw new HttpNotFoundException($request, "Release not found: {$tag}");
        }

        // Get commits
        $commits = $em->getRepository(GitCommit::class)->findBy(['release' => $release], ['timestamp' => 'DESC']);
        if (!$commits) {
            throw new HttpNotFoundException($request, "Commits not found");
        }

        // Response
        $response->getBody()->write(
            $twig->render('commits.html.twig', [
                'release' => $release[0],
                'commits' => $commits,
            ])
        );

        return $response;
    }
}
