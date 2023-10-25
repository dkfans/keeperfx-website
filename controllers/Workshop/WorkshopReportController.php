<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;

use App\Entity\WorkshopComment;
use App\Entity\UserNotification;
use App\Entity\WorkshopCommentReport;

use App\Notifications\NotificationCenter;
use App\Notifications\Notification\WorkshopItemCommentReportNotification;

use App\Account;
use Doctrine\ORM\EntityManager;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class WorkshopReportController {

    public function reportComment(
        Request $request,
        Response $response,
        Account $account,
        EntityManager $em,
        NotificationCenter $nc,
        $comment_id
    ){

        // Get the comment
        /** @var WorkshopComment $item */
        $comment = $em->getRepository(WorkshopComment::class)->find($comment_id);
        if(!$comment){
            throw new HttpNotFoundException($request);
        }

        // Get post
        $post = $request->getParsedBody();
        if(!\array_key_exists('reason', $post) || !isset($post['reason']) || !is_string($post['reason'])){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'INVALID_REQUEST'
                ])
            );
            return $response;
        }

        // Create report
        $report = new WorkshopCommentReport();
        $report->setUser($account->getUser());
        $report->setComment($comment);
        $report->setReason($post['reason']);
        $em->persist($report);
        $em->flush();

        // Send notification to moderators
        $nc->sendNotificationToAllWithRole(
            UserRole::Moderator,
            WorkshopItemCommentReportNotification::class,
            [
                'report_id'  => $report->getId(),
                'item_id'    => $comment->getItem()->getId(),
                'comment_id' => $comment->getId(),
                'item_name'  => $comment->getItem()->getName(),
                'username'   => $account->getUser()->getUsername(),
            ]
        );

        // Return success
        $response->getBody()->write(
            \json_encode([
                'success' => true,
            ])
        );
        return $response;
    }

    public function removeCommentReport(
        Request $request,
        Response $response,
        EntityManager $em,
        $report_id
    ){
        // Get the report
        /** @var WorkshopCommentReport $item */
        $report = $em->getRepository(WorkshopCommentReport::class)->find($report_id);
        if(!$report){
            throw new HttpNotFoundException($request);
        }

        // Remember report ID
        $report_id = $report->getId();

        // Remove the report
        $em->remove($report);

        // Remove notifications linking to this report
        $notifications = $em->getRepository(UserNotification::class)->findBy(['class' => WorkshopItemCommentReportNotification::class]);
        foreach($notifications as $notification){
            $data = $notification->getData();
            if(isset($data['report_id']) && $data['report_id'] === $report_id){
                $em->remove($notification);
            }
        }

        // Save changes to DB
        $em->flush();

        // Return success
        $response->getBody()->write(
            \json_encode([
                'success' => true,
            ])
        );
        return $response;
    }

}
