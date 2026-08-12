<?php

namespace App\Notifications\Notification;

use App\Enum\UserRole;
use App\Notifications\Notification;
use App\Notifications\NotificationInterface;

class VirusRemovedNotification extends Notification implements NotificationInterface
{
    public function getRequiredUserRole(): UserRole
    {
        return UserRole::Admin;
    }

    public function getNotificationTitle(): string
    {
        return 'Virus removed';
    }

    public function getText(): string
    {
        return "Virus removed: `{$this->data['filename']}` ::[{$this->data['item_name']}]::";
    }

    public function getUri(): string
    {
        return "/workshop/item/{$this->data['item_id']}";
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => true,
            'email'   => false,
        ];
    }
}
