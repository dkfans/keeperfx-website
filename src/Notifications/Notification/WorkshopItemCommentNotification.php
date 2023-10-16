<?php

namespace App\Notifications\Notification;

use Doctrine\ORM\EntityManager;

use App\Notifications\Notification\Notification;
use App\Notifications\Notification\NotificationInterface;

class WorkshopItemCommentNotification extends Notification implements NotificationInterface {

    public function getText(): string
    {
        return "@{$this->data['username']} commented on **{$this->data['item_name']}**";
    }

    public function getUri(): string
    {
        return "/workshop/item/{$this->data['item_id']}#comment-{$this->data['comment_id']}";
    }

    public function getNotificationTitle(): string
    {
        return "New comment on your workshop item";
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => true,
            'email'   => true,
        ];
    }

}
