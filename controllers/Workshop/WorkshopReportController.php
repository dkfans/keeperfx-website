<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;
use App\Enum\WorkshopCategory;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;
use App\Entity\WorkshopRating;
use App\Entity\WorkshopComment;
use App\Entity\WorkshopDifficultyRating;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\Entity\WorkshopCommentReport;
use App\Entity\WorkshopFile;
use App\Enum\UserNotificationType;
use App\UploadSizeHelper;

use App\Notifications\NotificationCenter;
use App\Notifications\Notification\WorkshopItemCommentReportNotification;
use App\Notifications\Notification\WorkshopItemCommentReplyNotification;

use URLify;
use Slim\Psr7\UploadedFile;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use GuzzleHttp\Psr7\LazyOpenStream;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;
use League\CommonMark\GithubFlavoredMarkdownConverter;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use Slim\Exception\HttpNotFoundException;

class WorkshopReportController {

    public function reportComment(
        Request $request,
        Response $response,
        FlashMessage $flash,
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
        FlashMessage $flash,
        Account $account,
        EntityManager $em,
        NotificationCenter $nc,
        $report_id
    ){

        // Get the comment
        /** @var WorkshopComment $item */
        $report = $em->getRepository(WorkshopCommentReport::class)->find($report_id);
        if(!$report){
            throw new HttpNotFoundException($request);
        }

        $em->remove($report);
        $em->flush();


        $response->getBody()->write(
            \json_encode([
                'success' => true,
            ])
        );
        return $response;
    }

}
