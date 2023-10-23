<?php

namespace App\Controller\Api\v1\Workshop;

use App\Enum\WorkshopScanStatus;

use App\Entity\WorkshopItem;
use App\Entity\WorkshopComment;

use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class WorkshopItemApiController {

    public function getItem(
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

    public function getComment(
        Request $request,
        Response $response,
        EntityManager $em,
        $id,
    ) {
        /** @var WorkshopComment $item */
        $comment = $em->getRepository(WorkshopComment::class)->find($id);
        if(!$comment){
            throw new HttpNotFoundException($request);
        }

        $response->getBody()->write(
            \json_encode(['workshop_comment' => [
                'item_id' => $comment->getItem()->getId(),
                'id'      => $comment->getId(),
                'content' => $comment->getContent(),
                'user'    => [
                    'id'           => $comment->getUser()->getId(),
                    'username'     => $comment->getUser()->getUsername(),
                    'role'         => $comment->getUser()->getRole()->value,
                    'is_submitter' => ($comment->getUser() === $comment->getItem()->getSubmitter())
                ],
            ]])
        );

        return $response;
    }
}
