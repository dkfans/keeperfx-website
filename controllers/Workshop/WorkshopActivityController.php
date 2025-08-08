<?php

namespace App\Controller\Workshop;

use App\Entity\User;
use App\Entity\WorkshopItem;
use App\Entity\WorkshopComment;
use App\Entity\WorkshopCommentReport;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use Doctrine\DBAL\Connection as DbalConnection;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class WorkshopActivityController
{

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        DbalConnection $dbal
    ) {
        $sql = "SELECT *
                FROM workshop_comment
                WHERE created_timestamp IS NOT NULL OR updated_timestamp IS NOT NULL
                ORDER BY COALESCE(updated_timestamp, created_timestamp) DESC
                LIMIT 100";

        $statement = $dbal->prepare($sql);
        $result    = $statement->executeQuery();
        $comments  = $result->fetchAllAssociative();

        // Create some array caches so we don't load too much
        $user_cache          = [];
        $workshop_item_cache = [];

        // Loop trough all comments and load the user, workshop item, reports etc
        // We do this manually because the COALESCE query is hard to do with the entity manager
        foreach ($comments as $i => $comment) {

            $comments[$i]['is_comment'] = true;

            // Get the user
            $user = null;
            $uid = $comment['user_id'];
            if (isset($user_cache[$uid])) {
                $user = $user_cache[$uid];
            } else {
                $user = $em->getRepository(User::class)->find($uid);
                $user_cache[$uid] = $user;
            }
            $comments[$i]['user'] = $user;

            // Get the workshop item
            $workshop_item = null;
            $workshop_id = $comment['item_id'];
            if (isset($workshop_item_cache[$workshop_id])) {
                $workshop_item = $workshop_item_cache[$workshop_id];
            } else {
                $workshop_item = $em->getRepository(WorkshopItem::class)->find($workshop_id);
                $workshop_item_cache[$workshop_id] = $workshop_item;
            }
            $comments[$i]['item'] = $workshop_item;

            // TODO: Maybe get parent comment?
        }

        // Create workshop item query
        $query = $em->getRepository(WorkshopItem::class)->createQueryBuilder('item')
            ->where('item.is_published = 1')
            ->setMaxResults(50);
        $query = $query->orderBy(
            $query->expr()->desc(
                'CASE WHEN item.updated_timestamp > item.creation_orderby_timestamp THEN item.updated_timestamp ELSE item.creation_orderby_timestamp END'
            )
        );
        $result = $query->getQuery()->getResult();

        // Create data array for the page
        $data = [];

        // Loop trough workshop items and save them by timestamp
        /** @var WorkshopItem $workshop_item */
        foreach ($result as $workshop_item) {
            $data[$workshop_item->getCreatedTimestamp()->getTimestamp()] = $workshop_item;
        }

        // Add the comments to the data array
        foreach ($comments as $comment) {
            $data[(new \DateTime($comment['created_timestamp']))->getTimestamp()] = $comment;
        }

        // Order the data array by timestamp
        \krsort($data, SORT_NUMERIC);

        $response->getBody()->write(
            $twig->render('workshop/activity.workshop.html.twig', [
                'data' => $data
            ])
        );

        return $response;
    }
}
