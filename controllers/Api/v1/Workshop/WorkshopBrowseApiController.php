<?php

namespace App\Controller\Api\v1\Workshop;

use App\Entity\WorkshopItem;

use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Psr\SimpleCache\CacheInterface;
use URLify;

class WorkshopBrowseApiController {

    public function listLatest(
        Request $request,
        Response $response,
        EntityManager $em,
        // TODO: CacheInterface $cache,
    ){
        $workshop_items = [];
        $workshop_item_entities = $em->getRepository(WorkshopItem::class)->findBy([], ['creation_orderby_timestamp' => 'DESC'], 10);

        if($workshop_item_entities){
            foreach($workshop_item_entities as $entity){
                $workshop_items[] = [
                    'name'              => $entity->getName(),
                    'created_timestamp' => $entity->getCreatedTimestamp()->format('Y-m-d'),
                    'image'             => \count($entity->getImages()) > 0 ?
                        $_ENV['APP_ROOT_URL'] . '/workshop/image/' . $entity->getId() . '/' . $entity->getImages()[0]->getFilename() :
                        $_ENV['APP_ROOT_URL'] . '/img/horny-face.png',
                    'url'               => $_ENV['APP_ROOT_URL'] . '/workshop/item/' . $entity->getId() . '/' . URLify::slug($entity->getName()),
                ];
            }
        }

        $response->getBody()->write(
            \json_encode(['workshop_items' => $workshop_items])
        );

        return $response;
    }

}
