<?php

namespace App\Controller;

use App\Account;
use App\Entity\GithubRelease;
use App\Entity\NewsArticle;
use App\Entity\WorkshopItem;
use App\FlashMessage;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;
use Twig\Environment as TwigEnvironment;

class IndexController
{
    public function index(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        CacheInterface $cache,
        FlashMessage $flash,
        Account $account,
    ) {
        // Grab some stuff from DB to show on main page
        $articles = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC'], 3);
        $release  = $em->getRepository(GithubRelease::class)->findOneBy([], ['timestamp' => 'DESC']);

        // TODO: use the following query but cache it instead of the big query builder a few lines below
        // $latest_workshop_items = $em->getRepository(WorkshopItem::class)->findBy(['is_published' => true, 'is_last_file_broken' => false], ['created_timestamp' => 'DESC'], 3);

        $latest_workshop_items = $em->createQueryBuilder()

            // Use partials to stop lazy loading
            ->select(
                'w',
                'PARTIAL i.{id, filename}',
                'PARTIAL s.{id, username, avatar, avatar_small}',
                'PARTIAL bio.{id}',
                'PARTIAL verify.{id}',
            )

            ->from(WorkshopItem::class, 'w')
            ->leftJoin('w.images', 'i')
            ->leftJoin('w.submitter', 's')

            // Left join the following entities instantly to lower amount of queries
            ->leftJoin('s.bio', 'bio')
            ->leftJoin('s.email_verification', 'verify')

            ->where('w.is_published = :published')
            ->andWhere('w.is_last_file_broken = :broken')
            ->setParameter('published', true)
            ->setParameter('broken', false)

            ->orderBy('w.created_timestamp', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        // Get featured Twitch stream
        $twitch_channel = null;
        $streams        = $cache->get('twitch_streams', []);
        if (!empty($streams)) {
            $twitch_channel = $streams[\array_rand($streams)];
        }

        $response->getBody()->write(
            $twig->render('index.html.twig', [
                'articles'              => $articles,
                'release'               => $release,
                'latest_workshop_items' => $latest_workshop_items,
                'forum_threads'         => $cache->get('keeperfx_forum_threads', []),
                'discord_info'          => $cache->get('discord_info', []),
                'twitch_channel'        => $twitch_channel,
                'twitch_parent_host'    => \parse_url($_ENV['APP_ROOT_URL'], \PHP_URL_HOST),
            ])
        );

        return $response;
    }
}
