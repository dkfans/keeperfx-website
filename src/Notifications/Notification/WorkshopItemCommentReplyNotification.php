<?php

namespace App\Notifications\Notification;

use Doctrine\ORM\EntityManager;

use App\Notifications\Notification;
use App\Notifications\NotificationInterface;

class WorkshopItemCommentReplyNotification extends Notification implements NotificationInterface {

    public function getText(): string
    {
        return "@{$this->data['username']} replied to your comment on **{$this->data['item_name']}**";
    }

    public function getUri(): string
    {
        return "/workshop/item/{$this->data['item_id']}#comment-{$this->data['comment_id']}";
    }

    public function getNotificationTitle(): string
    {
        return "New reply on your workshop item comment";
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => true,
            'email'   => true,
        ];
    }

}
