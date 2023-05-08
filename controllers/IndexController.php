<?php

namespace App\Controller;

use App\Entity\GithubRelease;
use App\Entity\NewsArticle;
use App\Entity\WorkshopItem;

use App\Enum\UserRole;

use App\Account;
use App\FlashMessage;
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
        FlashMessage $flash,
        Account $account
    ){
        // Grab some stuff from DB to show on main page
        $articles = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC'], 3);
        $release = $em->getRepository(GithubRelease::class)->findOneBy([], ['timestamp' => 'DESC']);

        // Get featured Twitch stream
        $twitch_channel = null;
        $streams = $cache->get('twitch_streams', []);
        if(!empty($streams)){
            $twitch_channel = $streams[\array_rand($streams)];
        }

        $response->getBody()->write(
            $twig->render('index.html.twig', [
                'articles'           => $articles,
                'release'            => $release,
                'forum_threads'      => $cache->get('keeperfx_forum_threads', []),
                'twitch_channel'     => $twitch_channel,
                'twitch_parent_host' => \parse_url($_ENV['APP_ROOT_URL'], \PHP_URL_HOST),
            ])
        );

        return $response;
    }

}
