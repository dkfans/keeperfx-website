<?php

namespace App\Controller;

use App\Entity\GitCommit;
use App\Entity\GithubRelease;
use App\Entity\NewsArticle;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class NewsController {

    public function newsArticleIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        $id,
        $date_str = null,
        $slug = null
    ){

        // Get news article
        $article = $em->getRepository(NewsArticle::class)->find($id);
        if(!$article){
            throw new HttpNotFoundException($request, 'News article not found');
        }

        // Make sure title slug and date are in URL
        if($article->getTitleSlug() !== $slug  || $article->getCreatedTimestamp()->format('Y-m-d') !== $date_str){
            $response = $response->withHeader('Location',
                '/news/' . $article->getId() . '/' . $article->getCreatedTimestamp()->format('Y-m-d') . '/' . $article->getTitleSlug()
            )->withStatus(302);
            return $response;
        }

        // Return news article view
        $response->getBody()->write(
            $twig->render('news.html.twig', [
                'article' => $article,
            ])
        );

        return $response;
    }

    public function newsListIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
    ){

        // Get news article
        $articles = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC']);

        // Return news article view
        $response->getBody()->write(
            $twig->render('news.list.html.twig', [
                'articles' => $articles,
            ])
        );

        return $response;
    }

    public function outputNewsImage(
        Request $request,
        Response $response,
        $filename
    ){
        // Make sure the news image storage is set
        if(!isset($_ENV['APP_NEWS_IMAGE_STORAGE']) || empty($_ENV['APP_NEWS_IMAGE_STORAGE'])){
            throw new HttpNotFoundException($request);
        }

        // Get image filepath
        $filepath = $_ENV['APP_NEWS_IMAGE_STORAGE'] . '/' . $filename;

        // Check if file exists
        if(!\file_exists($filepath)){
            throw new HttpNotFoundException($request, 'news image not found');
        }

        // Get mimetype of image
        $finfo        = \finfo_open(\FILEINFO_MIME_TYPE);
        $content_type = \finfo_file($finfo, $filepath);
        \finfo_close($finfo);

        // Return image
        $cache_time = (int)($_ENV['APP_IMAGE_OUTPUT_CACHE_TIME'] ?? 86400);
        $response = $response
            ->withHeader('Pragma', 'public')
            ->withHeader('Cache-Control', 'max-age=' . $cache_time)
            ->withHeader('Expires', \gmdate('D, d M Y H:i:s \G\M\T', time() + $cache_time))
            ->withHeader('Content-Type', $content_type);
        $response->getBody()->write(
            \file_get_contents($filepath)
        );

        return $response;
    }

}
