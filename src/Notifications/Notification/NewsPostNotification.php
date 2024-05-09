<?php

namespace App\Notifications\Notification;

use App\Enum\UserRole;

use Doctrine\ORM\EntityManager;

use App\Notifications\Notification;
use App\Notifications\NotificationInterface;
use App\Notifications\Exception\NotificationDataException;

class NewsPostNotification extends Notification implements NotificationInterface {

    public function getNotificationTitle(): string
    {
        return "New news post";
    }

    public function getText(): string
    {
        return "**{$this->data['title']}**";
    }

    public function getUri(): string
    {
        return '/news/' . $this->data['id'] . '/' . $this->data['date_string'] . '/' . $this->data['title_slug'];
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => true,
            'email'   => false,
        ];
    }
}
