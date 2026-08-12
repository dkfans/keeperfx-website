<?php

namespace App\Notifications\Notification;

use App\Enum\UserRole;
use App\Notifications\Notification;
use App\Notifications\NotificationInterface;

class NewUserNotification extends Notification implements NotificationInterface
{
    public function getRequiredUserRole(): UserRole
    {
        return UserRole::Admin;
    }

    public function getNotificationTitle(): string
    {
        return 'New user registration';
    }

    public function getText(): string
    {
        return "New user registered! {$this->data['username']} (ID: {$this->data['id']})";
    }

    public function getUri(): string
    {
        return "/admin/user/{$this->data['id']}";
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => true,
            'email'   => false,
        ];
    }
}
