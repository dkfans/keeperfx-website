<?php

namespace App\Notifications;

use App\Enum\UserRole;

class Notification
{
    protected \DateTime|null $timestamp;

    protected array|null $data;

    protected bool $is_read;

    public function __construct(\DateTime|null $timestamp = null, array|null $data = null, bool $is_read = false)
    {
        $this->timestamp = $timestamp;
        $this->data      = $data;
        $this->is_read   = $is_read;
    }

    public function getTimestamp(): \DateTime|null
    {
        return $this->timestamp;
    }

    public function isRead(): bool
    {
        return $this->is_read;
    }

    public function getRequiredUserRole(): UserRole
    {
        return UserRole::User;
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => true,
            'email'   => true,
        ];
    }

    public function getData(): array|null
    {
        return $this->data;
    }
}
