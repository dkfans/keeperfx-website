<?php

namespace App\Controller;

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
            ->setDate(\time());

        // Loop trough all articles
        foreach($articles as $i => $article){

            // Add last updated date (first element)
            if($i === 0){
                $feed->setChannelElement('pubDate',  \date(\DATE_RSS, $article->getCreatedTimestamp()->getTimestamp()));
            }

            // Create feed item
            $item = $feed->createNewItem();
            $item
                ->setTitle($article->getTitle())
                ->setDescription($article->getText())
                ->setLink($_ENV['APP_ROOT_URL'] . '/news/' . $article->getId() . '/' . $article->getCreatedTimestamp()->format('Y-m-d') . '/' . $article->getTitleSlug())
                ->setAuthor('KeeperFX')
                ->setDate($article->getCreatedTimestamp());

            $feed->addItem($item);
        }

        $response->getBody()->write(
            $feed->generateFeed()
        );

        return $response;
    }

}
