<?php

namespace App\Notifications;

use App\Enum\UserRole;

use App\Entity\User;
use App\Entity\UserNotification;

use App\Account;
use App\Mailer;
use Doctrine\ORM\EntityManager;
use App\Notifications\NotificationSettings;

use Psr\SimpleCache\CacheInterface;
use App\Notifications\NotificationInterface;

use App\Notifications\Exception\NotificationClassNotFoundException;
use App\Notifications\Exception\NotificationException;

use Xenokore\Utility\Helper\ClassHelper;
use Xenokore\Utility\Helper\DirectoryHelper;

class NotificationCenter {

    private const CACHE_KEY_NOTIFICATIONS = "unread-notification:unread-UID:%s";

    private array|null $unread_notifications = null;

    public function __construct(
        private Account $account,
        private EntityManager $em,
        private CacheInterface $cache,
        private NotificationSettings $notification_settings,
        private Mailer $mailer,
    ) {

        if($this->account->isLoggedIn()){

            // Get cached unread notifications
            $count = $cache->get($this->getUserCacheKey());
            if($count !== null){
                $this->unread_notifications = $count;
            }

        }
    }

    public function sendNotification(User $user, string $class, array|null $data = null): bool
    {
        // Get the notification settings of the receiving user
        $setting = $this->notification_settings->getUserSetting($user, $class);

        // Check if we are creating a notification on the website
        if($setting['website'] === true){
            $notification = $this->createUserNotification($user, $class, $data);
            $this->em->persist($notification);
            $this->em->flush();

            // Clear this users cache
            $this->clearUserCache($user);

            $notification_object = $this->createNotificationObject($notification);

            // Check if we need to send an email
            if($setting['email']){

                // Create and send mail
                $email_body = $notification_object->getNotificationTitle() . PHP_EOL . PHP_EOL;
                $email_body .= $_ENV['APP_ROOT_URL'] . '/account/notification/' . $notification->getId();
                $this->mailer->createMailForUser(
                    $user,
                    true,
                    $notification_object->getNotificationTitle(),
                    $email_body
                );
            }

            return true;
        }

        // Check if we did not create a website notification and just need to send an email.
        // This is important because the URL in the email will not point to a notification itself.
        if($setting['website'] === false && $setting['email'] === true){

            if(!\class_exists($class)){
                throw new NotificationClassNotFoundException("notification class '{$class}' not found");
            }

            // Make a blank notification object so we can load the title
            $notification_object = new $class(
                null,
                $data,
                false,
            );

            // Create and send mail
            $email_body = $notification_object->getNotificationTitle() . PHP_EOL . PHP_EOL;
            $email_body .= $_ENV['APP_ROOT_URL'] . $notification_object->getUri();
            $this->mailer->createMailForUser(
                $user,
                true,
                $notification_object->getNotificationTitle(),
                $email_body
            );

            return true;
        }

        return false;
    }

    public function sendNotificationToAllWithRole(UserRole $role, string $class, array|null $data = null): void
    {
        $users = $this->em->getRepository(User::class)->findAll();
        if($users){
            foreach($users as $user){
                if($user->getRole()->value >= $role->value){
                    $this->sendNotification($user, $class, $data);
                }
            }
        }
    }

    public function sendNotificationToAll(string $class, array|null $data = null): void
    {
        $users = $this->em->getRepository(User::class)->findAll();
        if($users){
            foreach($users as $user){
                $this->sendNotification($user, $class, $data);
            }
        }
    }

    public function sendNotificationToAllExceptSelf(string $class, array|null $data = null): void
    {
        if(!$this->account->isLoggedIn()){
            throw new \Exception("user needs to be logged in");
        }

        $users = $this->em->getRepository(User::class)->findAll();
        if($users){
            foreach($users as $user){
                if($user !== $this->account->getUser()){
                    $this->sendNotification($user, $class, $data);
                }
            }
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

    public function getNotificationSettings(): array
    {
        if(!$this->account->isLoggedIn()){
            throw new \Exception("user needs to be logged in");
        }

        return $this->notification_settings->getAllUserSettings($this->account->getUser());
    }

    /**
     * Clear all notifications that have specific data set.
     *
     * This is useful for when somebody removes something comment that still has a notification for somebody pending.
     * We don't want a notification that point to something that does not exist anymore.
     *
     * If a value in the data to match is an array, the whole array needs to match, except if $check_multiple is true.
     *
     * If $check_multiple is true, an array that is given inside of the data to match, will have all of its values checked separately.
     * Example:
     * [
     *     'item' => 1,
     *     'comment' => [1, 2, 3]
     * ]
     * The above example will remove notifications that have 'item' => 1 and a 'comment' that is either 1, 2 or 3.
     *
     * @param string $class
     * @param array $data_to_match
     * @param bool $check_multiple
     * @return bool
     */
    public function clearNotificationsWithData(string $class, array $data_to_match, bool $check_multiple = false): bool
    {
        $need_database_flush = false;
        $clear_cache_for_users = [];

        $notifications = $this->em->getRepository(UserNotification::class)->findBy(['class' => $class]);
        foreach($notifications as $notification){
            $notification_data = $notification->getData();

            // We'll remember if the stuff we have already checked still matches.
            // We haven't checked anything yet, so this should be true for now.
            $data_matches = true;

            foreach($data_to_match as $key => $value)
            {
                // We reset the 'data_matches' because we are checking data.
                // If the checked data matches this will be set to true again.
                $data_matches = false;

                // Make sure the key of the data exists in the notification data
                if(!\array_key_exists($key, $notification_data)){
                    break;
                }

                // If we are checking multiple data values, we need to compare each value separately
                if($check_multiple && \is_array($value)){
                    foreach($value as $check_value){
                        if(gettype($notification_data[$key]) === \gettype($check_value) && $notification_data[$key] === $check_value){
                            $data_matches = true;
                            break;
                        }
                    }
                    continue;
                }

                // Just compare the value
                // This makes it so an array need to match an array completely.
                if(gettype($notification_data[$key]) === \gettype($value) && $notification_data[$key] === $value){
                    $data_matches = true;
                }
            }

            // If ALL data matches, we'll remove this notification and clear the cache of the user
            if($data_matches){
                $need_database_flush     = true;
                $clear_cache_for_users[] = $notification->getUser();
                $this->em->remove($notification);
            }
        }

        // If we need to flush the database at least one notification has been removed
        if($need_database_flush){
            $this->em->flush();

            // Clear the caches for all affected users
            foreach($clear_cache_for_users as $user){
                $this->clearUserCache($user);
            }

            return true;
        }

        // No notifications have been removed
        return false;
    }
}
