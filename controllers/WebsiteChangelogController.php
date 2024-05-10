<?php

namespace App\Controller;

use App\FlashMessage;
use Symfony\Component\Process\Process;
use Twig\Environment as TwigEnvironment;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WebsiteChangelogController {

    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        CacheInterface $cache,
    ){

        $response->getBody()->write(
            $twig->render('website.changelog.html.twig', [
                'commits' => $cache->get("website-changelog-commits", []),
            ])
        );

        return $response;
    }

}
