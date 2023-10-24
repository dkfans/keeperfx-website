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
use App\Entity\WorkshopFile;
use App\Enum\UserNotificationType;
use App\UploadSizeHelper;

use App\Notifications\NotificationCenter;
use App\Notifications\Notification\WorkshopItemCommentNotification;
use App\Notifications\Notification\WorkshopItemCommentReplyNotification;

use URLify;
use Slim\Psr7\UploadedFile;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use GuzzleHttp\Psr7\LazyOpenStream;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;
use League\CommonMark\CommonMarkConverter;

use Psr\SimpleCache\CacheInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

use Slim\Exception\HttpNotFoundException;

class WorkshopCommentController {

    public function comment(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        NotificationCenter $nc,
        $id
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Get comment
        $post    = $request->getParsedBody();
        $content = (string) ($post['content'] ?? null);
        if(empty($content)){
            $flash->warning('You tried to submit an empty comment.');
            $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
            return $response;
        }

        // TODO: filter bad words

        // Add comment to DB
        $comment = new WorkshopComment();
        $comment->setItem($workshop_item);
        $comment->setUser($account->getUser());
        $comment->setContent($content);
        $em->persist($comment);
        $em->flush();

        // Notify workshop item submitter of the new comment
        if($workshop_item->getSubmitter() !== $account->getUser()){
            $nc->sendNotification(
                $workshop_item->getSubmitter(),
                WorkshopItemCommentNotification::class,
                [
                    'item_id'    => $workshop_item->getId(),
                    'comment_id' => $comment->getId(),
                    'item_name'  => $workshop_item->getName(),
                    'username'   => $account->getUser()->getUsername(),
                ]
            );
        }

        // Success!
        $flash->success('Your comment has been added!');
        $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

    public function updateComment(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        $item_id,
        $comment_id,
    ) {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Get the comment
        /** @var WorkshopComment $item */
        $comment = $em->getRepository(WorkshopComment::class)->find($comment_id);
        if(!$comment){
            throw new HttpNotFoundException($request);
        }

        // Check if comment is posted on this workshop item
        if($comment->getItem() !== $workshop_item){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'COMMENT_DOES_NOT_BELONG_TO_THIS_WORKSHOP_ITEM'
                ])
            );
            return $response;
        }

        // Only Workshop moderators and the original owner can edit the comment
        if($account->getUser()->getRole()->value < UserRole::Moderator->value && $comment->getUser() !== $account->getUser()){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'NOT_ALLOWED'
                ])
            );
            return $response;
        }

        // Get post
        $post = $request->getParsedBody();
        if(!\array_key_exists('content', $post) || !isset($post['content']) || !is_string($post['content'])){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'CONTENT_NOT_SET'
                ])
            );
            return $response;
        }

        // Make sure content is not empty
        if($post['content'] === ""){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'EMPTY_CONTENT'
                ])
            );
            return $response;
        }

        // Make sure new content is different
        if($comment->getContent() === $post['content']){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'SAME_CONTENT'
                ])
            );
            return $response;
        }

        // Update the comment
        $comment->setContent($post['content']);
        $em->flush();

        // Create HTML content from markdown
        $converter    = new CommonMarkConverter();
        $content_html = $converter->convert($comment->getContent())->getContent();

        // Return
        $response->getBody()->write(
            \json_encode([
                'success' => true,
                'workshop_comment' => [
                    'item_id'      => $comment->getItem()->getId(),
                    'id'           => $comment->getId(),
                    'content'      => $comment->getContent(),
                    'content_html' => $content_html,
                    'user'         => [
                        'id'           => $comment->getUser()->getId(),
                        'username'     => $comment->getUser()->getUsername(),
                        'role'         => $comment->getUser()->getRole()->value,
                        'is_submitter' => ($comment->getUser() === $comment->getItem()->getSubmitter())
                    ],
                ]
            ])
        );

        return $response;
    }


    public function deleteComment(
        Request $request,
        Response $response,
        EntityManager $em,
        Account $account,
        $item_id,
        $comment_id,
    ) {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Get the comment
        /** @var WorkshopComment $item */
        $comment = $em->getRepository(WorkshopComment::class)->find($comment_id);
        if(!$comment){
            throw new HttpNotFoundException($request);
        }

        // Check if comment is posted on this workshop item
        if($comment->getItem() !== $workshop_item){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'COMMENT_DOES_NOT_BELONG_TO_THIS_WORKSHOP_ITEM'
                ])
            );
            return $response;
        }

        // Only Workshop moderators and the original owner can edit the comment
        if($account->getUser()->getRole()->value < UserRole::Moderator->value && $comment->getUser() !== $account->getUser()){
            $response->getBody()->write(
                \json_encode([
                    'success' => false,
                    'error'   => 'NOT_ALLOWED'
                ])
            );
            return $response;
        }

        // Remove the comment
        $em->remove($comment);
        $em->flush();

        // Return success
        $response->getBody()->write(
            \json_encode([
                'success' => true,
            ])
        );
        return $response;
    }

    public function replyComment(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        TwigEnvironment $twig,
        EntityManager $em,
        NotificationCenter $nc,
        $item_id,
        $comment_id,
    ){
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($item_id);
        if(!$workshop_item){
            throw new HttpNotFoundException($request);
        }

        // Get the comment
        /** @var WorkshopComment $item */
        $parent_comment = $em->getRepository(WorkshopComment::class)->find($comment_id);
        if(!$parent_comment){
            throw new HttpNotFoundException($request);
        }

        // Get comment
        $post    = $request->getParsedBody();
        $content = (string) ($post['content'] ?? null);
        if(empty($content)){
            $flash->warning('You tried to submit an empty reply.');
            $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
            return $response;
        }

        // TODO: filter bad words

        // Add comment to DB
        $comment = new WorkshopComment();
        $comment->setItem($workshop_item);
        $comment->setUser($account->getUser());
        $comment->setContent($content);
        $comment->setParent($parent_comment);
        $em->persist($comment);
        $em->flush();

        // Notify if we are not replying to ourselves
        if($parent_comment->getUser() !== $account->getUser()){

            // Notify the user of the comment that we replied to them
            $nc->sendNotification(
                $parent_comment->getUser(),
                WorkshopItemCommentReplyNotification::class,
                [
                    'item_id'    => $workshop_item->getId(),
                    'comment_id' => $comment->getId(),
                    'item_name'  => $workshop_item->getName(),
                    'username'   => $account->getUser()->getUsername(),
                ]
            );

            // Notify workshop item submitter of the new comment
            // If we are not replying to them directly
            if($parent_comment->getUser() !== $workshop_item->getSubmitter()){
                $nc->sendNotification(
                    $workshop_item->getSubmitter(),
                    WorkshopItemCommentNotification::class,
                    [
                        'item_id'    => $workshop_item->getId(),
                        'comment_id' => $comment->getId(),
                        'item_name'  => $workshop_item->getName(),
                        'username'   => $account->getUser()->getUsername(),
                    ]
                );
            }
        }

        // Success!
        $flash->success('Your reply has been added!');
        $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

}
