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
        $workshop_item_entities = $em->getRepository(WorkshopItem::class)->findBy(['is_last_file_broken' => false], ['creation_orderby_timestamp' => 'DESC'], 10);

        if($workshop_item_entities){
            /** @var WorkshopItem $entity */
            foreach($workshop_item_entities as $entity){

                // Get submitter username
                $submitter = $entity->getSubmitter();
                if(!$submitter){
                    $username = 'KeeperFX Team';
                } else {
                    $username = $submitter->getUsername();
                }

                $workshop_items[] = [
                    'name'                 => $entity->getName(),
                    'category'             => $entity->getCategory()->name,
                    'created_timestamp'    => $entity->getCreatedTimestamp()->format('Y-m-d'),
                    'install_instructions' => $entity->getInstallInstructions(),
                    'description'          => $entity->getDescription(),

                    'url'                  => $_ENV['APP_ROOT_URL'] . '/workshop/item/' . $entity->getId() . '/' . URLify::slug($entity->getName()),

                    'image'                => \count($entity->getImages()) > 0 ?
                        $_ENV['APP_ROOT_URL'] . '/workshop/image/' . $entity->getId() . '/' . $entity->getImages()[0]->getFilename() :
                        $_ENV['APP_ROOT_URL'] . '/img/no-image-256.png',

                    'thumbnail'             => $entity->getThumbnail() ?
                        $_ENV['APP_ROOT_URL'] . '/workshop/image/' . $entity->getId() . '/' . $entity->getThumbnail() :
                        null,

                    'submitter' => [
                        'username' => $username,
                    ],
                ];
            }
        }

        $response->getBody()->write(
            \json_encode(['workshop_items' => $workshop_items])
        );

        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        return $response;
    }

}
