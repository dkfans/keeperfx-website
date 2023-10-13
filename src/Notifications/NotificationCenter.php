<?php

namespace App\Notifications;

use App\Enum\UserRole;

use App\Entity\User;

use App\Account;
use App\Entity\UserNotification;
use Doctrine\ORM\EntityManager;

use App\Workshop\Exception\NotificationClassNotFoundException;
use App\Workshop\Exception\NotificationException;
use Psr\SimpleCache\CacheInterface;

class NotificationCenter {

    private array|null $notifications = null;

    private int|null $unread_notifications = null;

    public function __construct(
        private Account $account,
        private EntityManager $em,
        private CacheInterface $cache,
    ) {

        if($this->account->isLoggedIn()){

            // Get cached unread notification count
            $count = $cache->get(\sprintf("keeperfx:notification:%s:unread-count", $account->getUser()->getId()));
            if($count !== null){
                $this->unread_notifications = $count;
            }

        }
    }

    public function getUnreadNotificationCount(): int
    {
        if(!$this->account->isLoggedIn()){
            throw new NotificationException("can't get notification count if not logged in");
        }

        if($this->unread_notifications !== null){
            return $this->unread_notifications;
        }

        // TODO: get unread notification count from DB
        // TODO: cache the count

        // TODO: return the count



    }

    public function getNotifications(int $limit = 10)
    {
        if(!$this->account->isLoggedIn()){
            throw new NotificationException("can't get notifications if not logged in");
        }

        if($this->unread_notifications !== null){
            return $this->unread_notifications;
        }


    }

    public function sendNotification(User $user, string $class, array|null $data = null)
    {
        $notification = $this->createUserNotification($user, $class, $data);
        $this->em->persist($notification);
        $this->em->flush();
    }

    public function sendNotificationToAdmins(string $class, array|null $data = null): void
    {
        $admins = $this->em->getRepository(User::class)->findBy(['role' => UserRole::Admin]);
        if($admins){
            foreach($admins as $admin){
                $notification = $this->createUserNotification($admin, $class, $data);
                $this->em->persist($notification);
            }
            $this->em->flush();
        }
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

}
