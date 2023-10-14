<?php

namespace App\Notifications\Notification;

use Doctrine\ORM\EntityManager;

use App\Notifications\Exception\NotificationDataException;

/**
 * Workshop Item Comment notification
 *
 * This notification is sent to the submitter of a workshop item when it receives a reply
 */
class NewUserNotification extends Notification {

    public function getText(): string
    {
        return "{$this->data['username']} commented on {$this->data['workshop_item_name']}";
    }

    public function getUri(): string
    {
        return "/workshop/item/{$this->data['id']}";
    }

}
