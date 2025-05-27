<?php

namespace App\Controller\Workshop;


use URLify;
use App\Account;

use App\FlashMessage;
use App\Config\Config;
use App\Enum\UserRole;
use App\UploadSizeHelper;
use App\Entity\WorkshopTag;
use Slim\Psr7\UploadedFile;

use App\Entity\WorkshopFile;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;
use App\Entity\WorkshopRating;
use App\Enum\WorkshopCategory;
use App\Entity\WorkshopComment;

use Doctrine\ORM\EntityManager;
use App\Entity\UserNotification;
use Slim\Csrf\Guard as CsrfGuard;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\SimpleCache\CacheInterface;
use geertw\IpAnonymizer\IpAnonymizer;
use App\Entity\WorkshopDifficultyRating;
use ByteUnits\Binary as BinaryFormatter;

use Twig\Environment as TwigEnvironment;
use App\Notifications\NotificationCenter;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\UploadedFileInterface;

use Xenokore\Utility\Helper\DirectoryHelper;

use Psr\Http\Message\ResponseInterface as Response;

use Psr\Http\Message\ServerRequestInterface as Request;
use App\Notifications\Notification\WorkshopItemNotification;
use App\Notifications\Notification\WorkshopItemCommentNotification;

class WorkshopItemController
{

    public function itemIndex(
        Request $request,
        Response $response,
        TwigEnvironment $twig,
        FlashMessage $flash,
        EntityManager $em,
        Account $account,
        NotificationCenter $nc,
        $id,
        $slug = null
    ) {
        // Check if workshop item exists
        $workshop_item = $em->getRepository(WorkshopItem::class)->find($id);
        if (!$workshop_item) {
            throw new HttpNotFoundException($request);
        }

        // Make sure title slug is in URL and matches
        if (URLify::slug($workshop_item->getName()) !== $slug) {
            $response = $response->withHeader(
                'Location',
                '/workshop/item/' . $workshop_item->getId() . '/' . URLify::slug($workshop_item->getName())
            )->withStatus(302);
            return $response;
        }

        // Remove the notification for this workshop item if the user has one for it
        // We don't remove comment notifications because the user might want to check them out one by one
        if ($account->isLoggedIn()) {
            $notifications = $nc->getUnreadNotifications();
            if ($notifications) {
                foreach ($notifications as $notification_id => $notification) {
                    if ($notification instanceof WorkshopItemNotification) {
                        if ($notification->getData()['item_id'] === (int)$id) {
                            $notification = $em->getRepository(UserNotification::class)->find($notification_id);
                            if ($notification) {
                                $notification->setRead(true);
                                $em->flush();
                                $nc->clearUserCache();
                                $nc->removeUnreadNotificationById($notification_id);
                            }
                        }
                    }
                }
            }
        }

        // Show non-published notice
        if (!$workshop_item->isPublished()) {
            $flash->warning('The requested workshop item has not been published.');
            $response->getBody()->write(
                $twig->render('workshop/alert.workshop.html.twig')
            );
            return $response;
        }

        // Get workshop item rating counts
        $rating_count            = \count($workshop_item->getRatings());
        $difficulty_rating_count = \count($workshop_item->getDifficultyRatings());

        // Get user rating
        $user_rating = $em->getRepository(WorkshopRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account?->getUser()
        ])?->getScore();

        // Get user difficulty rating
        $user_difficulty_rating = $em->getRepository(WorkshopDifficultyRating::class)->findOneBy([
            'item' => $workshop_item,
            'user' => $account?->getUser()
        ])?->getScore();

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/item.workshop.html.twig', [
                'item'                     => $workshop_item,
                'rating_amount'            => $rating_count,
                'user_rating'              => $user_rating,
                'difficulty_rating_amount' => $difficulty_rating_count,
                'user_difficulty_rating'   => $user_difficulty_rating,
            ])
        );

        return $response;
    }
}
