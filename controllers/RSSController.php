<?php

namespace App\Controller;

use App\Entity\GithubAlphaBuild;
use App\Entity\GithubRelease;
use App\Entity\NewsArticle;
use Doctrine\ORM\EntityManager;
use FeedWriter\RSS2;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RSSController {

    public function newsFeed(
        Request $request,
        Response $response,
        EntityManager $em
    ){
        /** @var NewsArticle[] $articles */
        $articles = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC'], 5);

        // Create feed
        $feed = new RSS2();
        $feed
            ->setTitle('KeeperFX')
            ->setDescription('The latest news for KeeperFX')
            ->setLink($_ENV['APP_ROOT_URL'])
            ->setSelfLink($_ENV['APP_ROOT_URL'] . '/rss/news')
            ->setChannelElement('language', 'en-US')
            ->setDate(\time())
            ->addGenerator();

        // Loop trough all articles
        foreach($articles as $i => $article){

            // Add last updated date (first element)
            if($i === 0){
                $feed->setChannelElement('pubDate',  \date(\DATE_RSS, $article->getCreatedTimestamp()->getTimestamp()));
            }

            // Create URL to article
            $url = $_ENV['APP_ROOT_URL'] . '/news/' . $article->getId() . '/' . $article->getCreatedTimestamp()->format('Y-m-d') . '/' . $article->getTitleSlug();

            // Create feed item
            $item = $feed->createNewItem();
            $item
                ->setTitle($article->getTitle())
                ->setDescription($article->getText())
                ->setLink($url)
                ->setId($url, true)
                ->setDate($article->getCreatedTimestamp());

            $feed->addItem($item);
        }

        $response->getBody()->write(
            $feed->generateFeed()
        );

        return $response->withHeader('Content-Type', 'application/rss+xml');
    }


    public function stableBuildFeed(
        Request $request,
        Response $response,
        EntityManager $em
    ){
        /** @var GithubRelease[] $articles */
        $stable_builds = $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);

        // Create feed
        $feed = new RSS2();
        $feed
            ->setTitle('KeeperFX - Stable Releases')
            ->setDescription('The latest stable releases of KeeperFX')
            ->setLink($_ENV['APP_ROOT_URL'])
            ->setSelfLink($_ENV['APP_ROOT_URL'] . '/rss/stable')
            ->setChannelElement('language', 'en-US')
            ->setDate(\time())
            ->addGenerator();

        // Loop trough all articles
        foreach($stable_builds as $i => $build){

            // Add last updated date (first element)
            if($i === 0){
                $feed->setChannelElement('pubDate',  \date(\DATE_RSS, $build->getTimestamp()->getTimestamp()));
            }

            // Create feed item
            $item = $feed->createNewItem();
            $item
                ->setTitle($build->getName())
                ->setDescription($build->getTag())
                ->setLink($build->getDownloadUrl())
                ->setId($build->getDownloadUrl(), false)
                ->setDate($build->getTimestamp());

            $feed->addItem($item);
        }

        $response->getBody()->write(
            $feed->generateFeed()
        );

        return $response->withHeader('Content-Type', 'application/rss+xml');
    }


    public function alphaPatchFeed(
        Request $request,
        Response $response,
        EntityManager $em
    ){
        /** @var GithubAlphaBuild[] $articles */
        $alpha_patches = $em->getRepository(GithubAlphaBuild::class)->findBy([], ['timestamp' => 'DESC']);

        // Create feed
        $feed = new RSS2();
        $feed
            ->setTitle('KeeperFX - Alpha Patches')
            ->setDescription('The latest alpha patches for KeeperFX')
            ->setLink($_ENV['APP_ROOT_URL'])
            ->setSelfLink($_ENV['APP_ROOT_URL'] . '/rss/alpha')
            ->setChannelElement('language', 'en-US')
            ->setDate(\time())
            ->addGenerator();

        // Loop trough all articles
        foreach($alpha_patches as $i => $patch){

            // Add last updated date (first element)
            if($i === 0){
                $feed->setChannelElement('pubDate',  \date(\DATE_RSS, $patch->getTimestamp()->getTimestamp()));
            }

            $url = $_ENV['APP_ROOT_URL'] . '/download/' . $patch->getFilename();

            // Create feed item
            $item = $feed->createNewItem();
            $item
                ->setTitle($patch->getName())
                ->setDescription($patch->getWorkflowTitle())
                ->setLink($url)
                ->setId($url, false)
                ->setDate($patch->getTimestamp());

            $feed->addItem($item);
        }

        $response->getBody()->write(
            $feed->generateFeed()
        );

        return $response->withHeader('Content-Type', 'application/rss+xml');
    }

}
