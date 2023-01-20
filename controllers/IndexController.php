<?php

namespace App\Controller;

use App\Entity\GithubRelease;
use App\Entity\NewsArticle;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;

class IndexController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        CacheInterface $cache,
    ){
        $articles = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC'], 3);
        $release = $em->getRepository(GithubRelease::class)->findOneBy([], ['timestamp' => 'DESC']);

        $response->getBody()->write(
            $twig->render('index.html.twig', [
                'articles'      => $articles,
                'release'       => $release,
                'forum_threads' => $cache->get('keeperfx_forum_threads', []),
            ])
        );

        return $response;
    }

}
