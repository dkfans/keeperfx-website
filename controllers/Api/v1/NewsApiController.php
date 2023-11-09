<?php

namespace App\Controller\Api\v1;

use App\Entity\NewsArticle;

use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Psr\SimpleCache\CacheInterface;

class NewsApiController {

    public function listLatest(
        Request $request,
        Response $response,
        EntityManager $em,
        // TODO: CacheInterface $cache,
    ){
        $articles = [];
        $article_entities = $em->getRepository(NewsArticle::class)->findBy([], ['created_timestamp' => 'DESC'], 3);
        if($article_entities){
            foreach($article_entities as $article_entity){
                $articles[] = [
                    'title'             => $article_entity->getTitle(),
                    'created_timestamp' => $article_entity->getCreatedTimestamp()->format('Y-m-d'),
                    'excerpt'           => $article_entity->getExcerpt(),
                    'url'               => $_ENV['APP_ROOT_URL'] . '/news/' . $article_entity->getId() .
                                            '/' . $article_entity->getCreatedTimestamp()->format('Y-m-d') .
                                            '/' . $article_entity->getTitleSlug()
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
