<?php

namespace App\Controller\Api\v1;

use App\Entity\NewsArticle;

use Doctrine\ORM\EntityManager;
use App\Twig\Extension\Markdown\CustomMarkdownConverter;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class NewsApiController
{

    public function listLatest(
        Request $request,
        Response $response,
        EntityManager $em,
        CustomMarkdownConverter $md_converter,
        // TODO: CacheInterface $cache,
    ) {
        $articles = [];
        $article_entities = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC'], 3);
        if ($article_entities) {
            foreach ($article_entities as $article_entity) {

                $contents_with_markdown = $article_entity->getContents();
                $contents_with_html     = $md_converter->convert($contents_with_markdown);
                $contents_text_only     = \trim(\strip_tags($contents_with_html));

                $excerpt_with_markdown = $article_entity->getExcerpt();
                $excerpt_with_html     = $md_converter->convert($excerpt_with_markdown);
                $excerpt_text_only     = \trim(\strip_tags($excerpt_with_html));

                $articles[] = [
                    'title'             => $article_entity->getTitle(),
                    'created_timestamp' => $article_entity->getCreatedTimestamp()->format('Y-m-d'),

                    'contents_markdown' => $contents_with_markdown,
                    'contents_html'     => $contents_with_html,
                    'contents'          => $contents_text_only,

                    'excerpt_markdown' => $excerpt_with_markdown,
                    'excerpt_html'     => $excerpt_with_html,
                    'excerpt'          => $excerpt_text_only,

                    'url'               => $_ENV['APP_ROOT_URL'] . '/news/' . $article_entity->getId() .
                        '/' . $article_entity->getCreatedTimestamp()->format('Y-m-d') .
                        '/' . $article_entity->getTitleSlug(),
                    'image'             => $article_entity->getImage() ?
                        $_ENV['APP_ROOT_URL'] . '/news/image/' . $article_entity->getImage() :
                        $_ENV['APP_ROOT_URL'] . '/img/horny-face-256.png',
                ];
            }
        }

        $response->getBody()->write(
            \json_encode(['articles' => $articles])
        );

        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        return $response;
    }
}
