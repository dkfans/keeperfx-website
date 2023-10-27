<?php

namespace App\Controller\Workshop;


use App\Enum\UserRole;

use App\Entity\WorkshopItem;
use App\Entity\WorkshopComment;
use App\Entity\UserNotification;

use App\Account;
use App\FlashMessage;
use App\Workshop\WorkshopCache;
use Doctrine\ORM\EntityManager;
use Twig\Environment as TwigEnvironment;
use League\CommonMark\GithubFlavoredMarkdownConverter;

use App\Notifications\NotificationCenter;
use App\Notifications\Notification\WorkshopItemCommentNotification;
use App\Notifications\Notification\WorkshopItemCommentReplyNotification;
use App\Notifications\Notification\WorkshopItemCommentReportNotification;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;

class WorkshopCommentController {

    public function comment(
        Request $request,
        Response $response,
        FlashMessage $flash,
        Account $account,
        WorkshopCache $workshop_cache,
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

        // Clear the workshop browse page cache so it reflects the new data
        $workshop_cache->clearAllCachedBrowsePageData();

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

        // Update the comment
        $comment->setContent($post['content']);
        $em->flush();

        // Create HTML content from markdown
        $converter    = new GithubFlavoredMarkdownConverter();
        $content_html = $comment->getContent();
        $content_html = \preg_replace('~\|\|(.+?)\|\|~', '<span class="spoiler">$1</span>', $content_html);
        $content_html = $converter->convert($content_html)->getContent();

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
        NotificationCenter $nc,
        WorkshopCache $workshop_cache,
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

        // Get any reports for this comment
        $reports = $comment->getReports();
        if(!is_null($reports) && \count($reports) > 0){

            // Get all the IDs for the reports and then remove them
            $report_ids = [];
            foreach($reports as $report){
                $report_ids[] = $report->getId();
                $em->remove($report);
            }

            // Remove notifications linking to these reports
            $nc->clearNotificationsWithData(
                WorkshopItemCommentReportNotification::class,
                ['report_id' => $report_ids],
                true
            );
        }

        // Remove the comment
        $em->remove($comment);

        // Save changes to DB
        $em->flush();

        // Clear the workshop browse page cache so it reflects the new data
        $workshop_cache->clearAllCachedBrowsePageData();

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
        WorkshopCache $workshop_cache,
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

        // Notify the owner of the comment if we are not replying to ourselves
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
        }

        // Notify workshop item submitter of the new comment if it was not them we replied to
        if(
            $parent_comment->getUser() !== $workshop_item->getSubmitter() &&
            $workshop_item->getSubmitter() !== $account->getUser()
        ){
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

        // Clear the workshop browse page cache so it reflects the new data
        $workshop_cache->clearAllCachedBrowsePageData();

        // Success!
        $flash->success('Your reply has been added!');
        $response = $response->withHeader('Location', '/workshop/item/' . $workshop_item->getId())->withStatus(302);
        return $response;
    }

}
