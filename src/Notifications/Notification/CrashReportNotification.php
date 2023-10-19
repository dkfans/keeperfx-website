<?php

namespace App\Notifications\Notification;

use App\Enum\UserRole;

use Doctrine\ORM\EntityManager;

use App\Notifications\Notification;
use App\Notifications\NotificationInterface;
use App\Notifications\Exception\NotificationDataException;

class CrashReportNotification extends Notification implements NotificationInterface {

    public function getRequiredUserRole(): UserRole
    {
        return UserRole::Developer;
    }

    public function getNotificationTitle(): string
    {
        return "New crash report";
    }

    public function getText(): string
    {
        return "New crash report for {$this->data['game_version']} (ID: {$this->data['id']})";
    }

    public function getUri(): string
    {
        return "/dev/crash-report/{$this->data['id']}";
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => true,
            'email'   => true,
        ];
    }
}
