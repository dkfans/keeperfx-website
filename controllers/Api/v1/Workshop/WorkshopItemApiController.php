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
        if(!$item || $item->isPublished() == false){
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
                'url'       => APP_ROOT_URL . '/workshop/download/' . $item->getid() . '/' . $file->getId() . '/' . \urlencode($file->getFilename()),
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

        $response = $response->withHeader('Content-Type', 'application/json');

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

        $response = $response->withHeader('Content-Type', 'application/json');

        return $response;
    }

    public function search(
        Request $request,
        Response $response,
        EntityManager $em,
    ){

        // Get queries
        $q = $request->getQueryParams();

        // Make sure a query is given
        if(empty($q['q'])){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error' => 'NO_SEARCH_QUERY_GIVEN'
                ])
            );
            $response = $response->withHeader('Content-Type', 'application/json');
            return $response;
        }

        // Create query
        $query = $em->getRepository(WorkshopItem::class)->createQueryBuilder('item')
            ->where('item.is_published = 1')
            ->andWhere('item.is_last_file_broken = 0');

        // Add search criteria
        $query = $query->leftJoin('item.submitter', 'submitter');
        $search_params = \explode(" ", $q['q']);
        foreach($search_params as $i => $search_param){
            $query = $query->andWhere($query->expr()->orX(
                $query->expr()->like('item.name', ':search'.$i),
                $query->expr()->like('item.original_author', ':search'.$i),
                $query->expr()->like('item.map_number', ':search'.$i),
                $query->expr()->like('submitter.username', ':search'.$i)
            ))->setParameter('search'.$i, '%' . $search_param . '%');
        }

        // Do the DB query
        $result = $query->getQuery()->getResult();

        // Loop trough all results
        $workshop_items = [];
        foreach($result as $workshop_item){
            $workshop_items[] = [
                'id' => $workshop_item->getId(),
                'name' => $workshop_item->getName(),
                'submitter' => $workshop_item->getSubmitter() === null ? null : [
                    'id'          => $workshop_item->getSubmitter()->getId(),
                    'username'    => $workshop_item->getSubmitter()->getUsername(),
                    'avatar'      => $workshop_item->getSubmitter()->getAvatar(),
                    'avatarSmall' => $workshop_item->getSubmitter()->getAvatarSmall(),
                    'role'        => $workshop_item->getSubmitter()->getRole(),
                ],
                'category'                => $workshop_item->getCategory(),
                'createdTimestamp'        => $workshop_item->getCreatedTimestamp(),
                'updatedTimestamp'        => $workshop_item->getUpdatedTimestamp(),
                'difficultyRatingEnabled' => $workshop_item->isDifficultyRatingEnabled(),
                'downloadCount'           => $workshop_item->getDownloadCount(),
                'originalAuthor'          => $workshop_item->getOriginalAuthor(),
                'originalCreationDate'    => $workshop_item->getOriginalCreationDate(),
                'thumbnail'               => $workshop_item->getThumbnail(),
                'images'                  => \count($workshop_item->getImages()) === 0 ? [] : [
                    0 => [
                        'filename' => $workshop_item->getImages()->first()->getFilename(),
                    ]
                ],
                'ratingScore'              => $workshop_item->getRatingScore(),
                'difficultyRatingScore'    => $workshop_item->getDifficultyRatingScore(),
                'comment_count'            => \count($workshop_item->getComments()),
                'minGameBuild'             => $workshop_item->getMinGameBuild(),
                'isLastFileBroken'         => $workshop_item->isLastFileBroken(),
                'description'              => $workshop_item->getDescription(),
                'installationInstructions' => $workshop_item->getInstallInstructions(),
            ];
        }


        $response->getBody()->write(
            \json_encode(['workshop_items' => $workshop_items])
        );
        $response = $response->withHeader('Content-Type', 'application/json');
        return $response;
    }


    public function checkMapNumber(
        Request $request,
        Response $response,
        EntityManager $em,
        $map_number,
    ){
        // Output JSON
        $response = $response->withHeader('Content-Type', 'application/json');

        $map_number = (int)$map_number;

        // Check if map number is valid
        if($map_number < 202 || $map_number > 32767){
            $response->getBody()->write(
                \json_encode([
                    'success'    => true,
                    'map_number' => $map_number,
                    'available'  => false,
                ])
            );
            return $response;
        }

        // Check if a workshop item with this map number already exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->findOneBy(['map_number' => $map_number]);
        if($workshop_item){
            $response->getBody()->write(
                \json_encode([
                    'success'    => true,
                    'map_number' => $map_number,
                    'available'  => false,
                ])
            );
            return $response;
        }

        // Map number is available!
        $response->getBody()->write(
            \json_encode([
                'success'    => true,
                'map_number' => $map_number,
                'available'  => true,
            ])
        );
        return $response;
    }
}
