<?php

namespace App\Notifications\Notification;

use Doctrine\ORM\EntityManager;

use App\Notifications\Exception\NotificationDataException;

class WorkshopItemCommentNotification extends Notification {

    public function getText(): string
    {
        return "{$this->data['username']} commented on {$this->data['workshop_item_name']}";
    }

    public function getUri(): string
    {
        return "/workshop/item/{$this->data['id']}";
    }

}
