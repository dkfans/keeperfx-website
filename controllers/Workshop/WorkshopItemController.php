<?php

namespace App\Controller\Workshop;

use App\Enum\UserRole;
use App\Enum\WorkshopCategory;

use App\Entity\WorkshopTag;
use App\Entity\WorkshopFile;
use App\Entity\WorkshopItem;
use App\Entity\GithubRelease;
use App\Entity\User;
use App\Entity\WorkshopRating;
use App\Entity\WorkshopComment;
use App\Entity\UserNotification;
use App\Entity\WorkshopDifficultyRating;

use App\Account;
use App\FlashMessage;
use App\Config\Config;
use App\UploadSizeHelper;
use App\Notifications\NotificationCenter;
use App\Notifications\Notification\WorkshopItemNotification;
use App\Notifications\Notification\WorkshopItemCommentNotification;

use URLify;
use Doctrine\ORM\EntityManager;
use Slim\Csrf\Guard as CsrfGuard;
use geertw\IpAnonymizer\IpAnonymizer;
use Twig\Environment as TwigEnvironment;
use ByteUnits\Binary as BinaryFormatter;

use Slim\Psr7\UploadedFile;
use Psr\SimpleCache\CacheInterface;
use GuzzleHttp\Psr7\LazyOpenStream;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Xenokore\Utility\Helper\DirectoryHelper;

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
        /** @var ?WorkshopItem $workshop_item */
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

        // Get other workshop items for "more items by this user"
        $more_items_by_user = [];
        /** @var User $submitter */
        $submitter = $workshop_item->getSubmitter();
        if ($submitter && empty($workshop_item->getOriginalAuthor())) {
            $workshop_items = $submitter->getWorkshopItems();
            if ($workshop_items) {
                $workshop_items_array = [];
                /** @var WorkshopItem $workshop_item2 */
                foreach ($workshop_items as $workshop_item2) {
                    if ($workshop_item2->isLastFileBroken() === false) {
                        $more_items_by_user[] = $workshop_item2;
                    }
                }
            }
        }

        // Only keep 3 random workshop items for "more by this user"
        \shuffle($more_items_by_user);
        $more_items_by_user = \array_slice($more_items_by_user, 0, 3);

        // Render view
        $response->getBody()->write(
            $twig->render('workshop/item.workshop.html.twig', [
                'item'                     => $workshop_item,
                'rating_amount'            => $rating_count,
                'user_rating'              => $user_rating,
                'difficulty_rating_amount' => $difficulty_rating_count,
                'user_difficulty_rating'   => $user_difficulty_rating,
                'more_items_by_user'       => $more_items_by_user,
            ])
        );

        return $response;
    }
}
