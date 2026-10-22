<?php

namespace App\Notifications\Notification;

use App\Notifications\Notification;
use App\Notifications\NotificationInterface;

class AwardNotification extends Notification implements NotificationInterface
{
    public function getText(): string
    {
        return "You have received the **{$this->data['award_name']}** award!";
    }

    public function getUri(): string
    {
        return "/workshop/user/{$this->data['username']}";
    }

    public function getNotificationTitle(): string
    {
        return 'Received an award';
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => true,
            'email'   => true,
        ];
    }
}
