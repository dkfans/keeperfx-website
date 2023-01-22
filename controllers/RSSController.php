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

        $feed = new RSS2();
        $feed
            ->setTitle('KeeperFX News')
            ->setDescription('The latest news for KeeperFX')
            ->setLink($_ENV['APP_ROOT_URL'])
            ->setSelfLink($_ENV['APP_ROOT_URL'] . '/rss/news')
            ->setChannelElement('language', 'en-US');

        foreach($articles as $article){
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
