<?php

namespace App;

use App\Enum\UserNotificationType;

use App\Entity\User;
use App\Entity\UserNotification;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Collections\Collection;

use Xenokore\Utility\Helper\JsonHelper;

use Doctrine\ORM\Exception\ORMException;

class Notification {

    /**
     * i should make an endpoint
     *
     * /notification/<id>
     *
     * that way i can mark the notification as read, and navigate the user
     *
     */

    private array $routes;
    private array $text;

    public function __construct(
        private Account $account,
        private EntityManager $em,
    ) {
        $this->routes = require __DIR__ . '/../app/notification.routes.php';
        $this->text   = require __DIR__ . '/../app/notification.text.php';
    }

    public function notify(User $user, UserNotificationType $type, array $data): bool
    {
        try {
            $notification = new UserNotification();
            $notification->setUser($user);
            $notification->setType($type);
            $notification->setData(JsonHelper::encode($data));

            $this->em->persist($notification);
            $this->em->flush();

            return true;
        } catch (ORMException $ex) {
            return false;
        }

        return false;
    }

    public function notifySelf(UserNotificationType $type, array $data): bool
    {
        if(!$this->account->isLoggedIn()){
            throw new \Exception("can't notify self because we are not logged in");
        }

        return $this->notify($this->account->getUser(), $type, $data);
    }

    public function getRoute(UserNotificationType $type): string|false
    {
        return $this->routes[$type->name] ?? false;
    }

    public function handleNotificationText(UserNotificationType $type, array $data): string
    {
        if(!isset($this->text[$type->name])){
            throw new \Exception('no text string found for notification');
        }

        $str = $this->text[$type->name];

        return $str;
    }
}
