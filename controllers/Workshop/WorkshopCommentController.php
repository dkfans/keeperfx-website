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

use URLify;
use Slim\Psr7\UploadedFile;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use GuzzleHttp\Psr7\LazyOpenStream;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;

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

}
