<?php

namespace App\Notifications;

use App\Enum\UserRole;

use App\Entity\User;
use App\Entity\UserNotification;

use App\Account;
use Doctrine\ORM\EntityManager;

use Psr\SimpleCache\CacheInterface;
use App\Notifications\Notification\NotificationInterface;

use App\Notifications\Exception\NotificationClassNotFoundException;
use App\Notifications\Exception\NotificationException;

class NotificationCenter {

    private const CACHE_KEY_NOTIFICATIONS = "keeperfx:unread-notification:%s";

    private array|null $unread_notifications = null;

    public function __construct(
        private Account $account,
        private EntityManager $em,
        private CacheInterface $cache,
    ) {

        if($this->account->isLoggedIn()){

            // Get cached unread notifications
            $count = $cache->get($this->getUserCacheKey());
            if($count !== null){
                $this->unread_notifications = $count;
            }

        }
    }

    public function sendNotification(User $user, string $class, array|null $data = null)
    {
        $notification = $this->createUserNotification($user, $class, $data);
        $this->em->persist($notification);
        $this->em->flush();
        $this->clearUserCache($user);
    }

    public function sendNotificationToAdmins(string $class, array|null $data = null): void
    {
        $admins = $this->em->getRepository(User::class)->findBy(['role' => UserRole::Admin]);
        if($admins){
            foreach($admins as $admin){
                $notification = $this->createUserNotification($admin, $class, $data);
                $this->em->persist($notification);
                $this->clearUserCache($admin);
            }
            $this->em->flush();
        }
    }

    public function getUnreadNotifications()
    {
        if(!$this->account->isLoggedIn()){
            throw new NotificationException("can't get unread notifications if not logged in");
        }

        if($this->unread_notifications !== null){
            return $this->unread_notifications;
        }

        $this->unread_notifications = [];

        $user_notifications = $this->em->getRepository(UserNotification::class)->findBy(
            [
                'user'    => $this->account->getUser(),
                'is_read' => false,
            ],
            ['created_timestamp' => 'DESC'],
        );

        if($user_notifications){
            foreach($user_notifications as $user_notification){
                $this->unread_notifications[$user_notification->getId()] = $this->createNotificationObject($user_notification);
            }
        }

        $this->cache->set($this->getUserCacheKey(), $this->unread_notifications);

        return $this->unread_notifications;
    }

    public function getAllNotifications()
    {
        if(!$this->account->isLoggedIn()){
            throw new NotificationException("can't get notifications if not logged in");
        }

        $notifications = [];

        $user_notifications = $this->em->getRepository(UserNotification::class)->findBy(
            [
                'user'    => $this->account->getUser(),
            ],
            ['created_timestamp' => 'DESC'],
        );

        if($user_notifications){
            foreach($user_notifications as $user_notification){
                $notifications[$user_notification->getId()] = $this->createNotificationObject($user_notification);
            }
        }

        return $notifications;
    }

    public function createNotificationObject(UserNotification $user_notification): NotificationInterface
    {
        $class = $user_notification->getClass();

        if(!\class_exists($class)){
            throw new NotificationClassNotFoundException("notification class '{$class}' does not exist");
        }

        /** @var NotificationInterface $notification */
        $notification = new $class(
            $user_notification->getCreatedTimestamp(),
            $user_notification->getData(),
            $user_notification->isRead(),
        );

        return $notification;
    }

    private function createUserNotification(User $user, string $class, array|null $data = null): UserNotification
    {
        if(!\class_exists($class)){
            throw new NotificationClassNotFoundException("notification class '{$class}' does not exist");
        }

        // TODO: check notification settings for this user

        $notification = new UserNotification();
        $notification->setClass($class);
        $notification->setUser($user);
        $notification->setData($data);
        return $notification;
    }

    private function getUserCacheKey(): string
    {
        if(!$this->account->isLoggedIn()){
            throw new NotificationException("can't get user cache key if not logged in");
        }

        return \sprintf(self::CACHE_KEY_NOTIFICATIONS, $this->account->getUser()->getId());
    }

    public function clearUserCache(User|null $user = null)
    {
        if($user === null){
            $this->cache->delete($this->getUserCacheKey());
        } else {
            $this->cache->delete(
                \sprintf(self::CACHE_KEY_NOTIFICATIONS, $user->getId())
            );
        }
    }
}
