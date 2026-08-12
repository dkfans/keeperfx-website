<?php

namespace App\Notifications;

use App\Enum\UserRole;

class Notification
{
    protected ?\DateTime $timestamp;

    protected ?array $data;

    protected bool $is_read;

    public function __construct(?\DateTime $timestamp = null, ?array $data = null, bool $is_read = false)
    {
        $this->timestamp = $timestamp;
        $this->data      = $data;
        $this->is_read   = $is_read;
    }

    public function getTimestamp(): ?\DateTime
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

    public function getData(): ?array
    {
        return $this->data;
    }
}
