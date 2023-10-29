<?php

namespace App\Controller\ModCP\Workshop;

use App\Entity\WorkshopItem;

use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Entity\WorkshopComment;
use Doctrine\DBAL\Connection as DbalConnection;
use App\Entity\User;
use App\Entity\WorkshopCommentReport;

class ModerateWorkshopCommentController {

    public function listIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        EntityManager $em,
        DbalConnection $dbal
    ){

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
        $comment_cache       = [];
        $workshop_item_cache = [];

        // Loop trough all comments and load the user, workshop item, reports etc
        // We do this manually because the COALESCE query is hard to do with the entity manager
        foreach($comments as $i => $comment){

            // Get the user
            $user = null;
            $uid = $comment['user_id'];
            if(isset($user_cache[$uid])){
                $user = $user_cache[$uid];
            } else {
                $user = $em->getRepository(User::class)->find($uid);
                $user_cache[$uid] = $user;
            }
            $comments[$i]['user'] = $user;

            // Get the workshop item
            $workshop_item = null;
            $workshop_id = $comment['item_id'];
            if(isset($workshop_item_cache[$workshop_id])){
                $workshop_item = $workshop_item_cache[$workshop_id];
            } else {
                $workshop_item = $em->getRepository(WorkshopItem::class)->find($workshop_id);
                $workshop_item_cache[$workshop_id] = $workshop_item;
            }
            $comments[$i]['item'] = $workshop_item;

            // TODO: Maybe get parent comment?

            // Get any reports
            $comments[$i]['reports'] = $em->getRepository(WorkshopCommentReport::class)->findBy(['comment'=>$comment['id']]);
        }

        $response->getBody()->write(
            $twig->render('modcp/workshop/comments.modcp.html.twig', [
                'comments' => $comments
            ])
        );

        return $response;
    }
}
