<?php

namespace App\Controller\Api\v1\Workshop;

use App\Entity\WorkshopItem;
use App\Enum\WorkshopScanStatus;
use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class WorkshopItemApiController {

    public function item(
        Request $request,
        Response $response,
        EntityManager $em,
        // TODO: CacheInterface $cache,
        $id,
    ){
        /** @var WorkshopItem $item */
        $item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$item || $item->getIsPublished() == false){
            throw new HttpNotFoundException($request);
        }

        $files = [];
        foreach($item->getFiles() as $file){
            if($file->getScanStatus() !== WorkshopScanStatus::SCANNED && $_ENV['APP_ENV'] !== 'dev'){
                continue;
            }
            $files[] = [
                'id'        => $file->getId(),
                'filename'  => $file->getFilename(),
                'url'       => $_ENV['APP_ROOT_URL'] . '/workshop/download/' . $item->getid() . '/' . $file->getId() . '/' . \urlencode($file->getFilename()),
                'timestamp' => $file->getCreatedTimestamp()->format('Y-m-d'),
                'size'      => $file->getSize(),
            ];
        }

        $response->getBody()->write(
            \json_encode(['workshop_item' => [
                'name'  => $item->getName(),
                'files' => $files,
                // TODO: add a lot more information to this API endpoint
            ]])
        );

        return $response;
    }
}
