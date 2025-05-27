<?php

namespace App\Notifications;

use App\Enum\UserRole;

interface NotificationInterface
{

    public function __construct(\DateTime|null $timestamp = null, array|null $data = null, bool $is_read = false);

    public function getTimestamp(): \DateTime|null;

    public function getText(): string;

    public function getUri(): string;

    public function getNotificationTitle(): string; // Used for notification settings and email subject

    public function getRequiredUserRole(): UserRole;

    public function getDefaultSettings(): array;

    public function getData(): array|null;

    public function isRead(): bool;
}
