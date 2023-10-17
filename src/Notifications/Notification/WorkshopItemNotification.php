<?php

namespace App\Notifications\Notification;

use Doctrine\ORM\EntityManager;

use App\Notifications\Notification;
use App\Notifications\NotificationInterface;

class WorkshopItemNotification extends Notification implements NotificationInterface {

    public function getText(): string
    {
        return "@{$this->data['username']} uploaded a new workshop item: **{$this->data['item_name']}**";
    }

    public function getUri(): string
    {
        return "/workshop/item/{$this->data['item_id']}";
    }

    public function getNotificationTitle(): string
    {
        return "New workshop item";
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => false,
            'email'   => false,
        ];
    }

}
