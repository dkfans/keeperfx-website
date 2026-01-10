<?php

namespace App\Controller;

use App\Entity\NewsArticle;
use App\Entity\GithubRelease;
use App\Entity\GithubAlphaBuild;

use Laminas\Feed\Writer\Feed;
use Laminas\Feed\Writer\Entry;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use League\CommonMark\CommonMarkConverter;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class RSSController
{

    public function rssInfoIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig
    ) {
        $response->getBody()->write(
            $twig->render('rss-info.html.twig')
        );

        return $response;
    }

    public function newsFeed(
        Request $request,
        Response $response,
        EntityManager $em
    ) {
        /** @var NewsArticle[] $articles */
        $articles = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC'], 5);

        $feed = new Feed();
        $feed
            ->setTitle('KeeperFX')
            ->setDescription('The latest news for KeeperFX')
            ->setLink($_ENV['APP_ROOT_URL'])
            ->setFeedLink($_ENV['APP_ROOT_URL'] . '/rss/news', 'rss')
            ->setLanguage('en-US');

        // Loop trough all articles
        /** @var NewsArticle $article */
        foreach ($articles as $i => $article) {

            // Add last updated date (first element)
            if ($i === 0) {
                $feed->setDateModified($article->getCreatedTimestamp());
            }

            // Create URL to article
            $url = $_ENV['APP_ROOT_URL'] . '/news/' . $article->getId() . '/' . $article->getCreatedTimestamp()->format('Y-m-d') . '/' . $article->getTitleSlug();

            // Create HTML content from markdown
            $converter = new CommonMarkConverter();
            $content   = (string) $converter->convert($article->getContents());

            // Create feed item
            /** @var Entry $entry */
            $entry = $feed->createEntry();
            $entry
                ->setTitle($article->getTitle())
                ->setContent($content)
                ->setLink($url)
                ->setDateModified($article->getCreatedTimestamp()) // TODO: add updated-timestamp to news articles
                ->setDateCreated($article->getCreatedTimestamp());

            // Add to feed
            $feed->addEntry($entry);
        }

        $response->getBody()->write(
            $feed->export('rss')
        );

        return $response->withHeader('Content-Type', 'application/rss+xml');
    }

    public function stableBuildFeed(
        Request $request,
        Response $response,
        EntityManager $em
    ) {
        $stable_builds = $em->getRepository(GithubRelease::class)->findBy([], ['timestamp' => 'DESC']);

        // Create feed
        $feed = new Feed();
        $feed
            ->setTitle('KeeperFX - Stable Releases')
            ->setDescription('The latest stable releases for KeeperFX')
            ->setLink($_ENV['APP_ROOT_URL'])
            ->setFeedLink($_ENV['APP_ROOT_URL'] . '/rss/stable', 'rss');

        // Loop trough all builds
        /** @var GithubRelease $build */
        foreach ($stable_builds as $i => $build) {

            // Add last updated date (first element)
            if ($i === 0) {
                $feed->setDateModified($build->getTimestamp());
            }

            // Create feed item
            /** @var Entry $entry */
            $entry = $feed->createEntry();
            $entry
                ->setTitle($build->getName())
                ->setDescription($build->getTag())
                ->setLink($build->getDownloadUrl())
                ->setDateCreated($build->getTimestamp());

            // Add to feed
            $feed->addEntry($entry);
        }

        $response->getBody()->write(
            $feed->export('rss')
        );

        return $response->withHeader('Content-Type', 'application/rss+xml');
    }


    public function alphaPatchFeed(
        Request $request,
        Response $response,
        EntityManager $em
    ) {
        $alpha_patches = $em->getRepository(GithubAlphaBuild::class)->findBy(['is_available' => true], ['timestamp' => 'DESC']);

        // Create feed
        $feed = new Feed();
        $feed
            ->setTitle('KeeperFX - Alpha Patches')
            ->setDescription('The latest alpha patches for KeeperFX')
            ->setLink($_ENV['APP_ROOT_URL'])
            ->setFeedLink($_ENV['APP_ROOT_URL'] . '/rss/alpha', 'rss');

        // Loop trough all alpha patches
        /** @var GithubAlphaBuild $patch */
        foreach ($alpha_patches as $i => $patch) {

            // Add last updated date (first element)
            if ($i === 0) {
                $feed->setDateModified($patch->getTimestamp());
            }

            $url = $_ENV['APP_ROOT_URL'] . '/download/' . $patch->getFilename();

            // Create feed item
            /** @var Entry $entry */
            $entry = $feed->createEntry();
            $entry
                ->setTitle($patch->getName())
                ->setDescription($patch->getWorkflowTitle())
                ->setLink($url)
                ->setDateCreated($patch->getTimestamp());

            // Add to feed
            $feed->addEntry($entry);
        }

        $response->getBody()->write(
            $feed->export('rss')
        );

        return $response->withHeader('Content-Type', 'application/rss+xml');
    }
}
