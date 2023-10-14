<?php

namespace App\Notifications\Notification;

class Notification implements NotificationInterface {

    protected \DateTime $timestamp;

    protected array $data;

    protected bool $is_read;

    public function __construct(\DateTime $timestamp, array|null $data, bool $is_read)
    {
        $this->timestamp = $timestamp;
        $this->data      = $data;
        $this->is_read   = $is_read;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function isRead(): bool
    {
        return $this->is_read;
    }

    public function getText(): string
    {
        return "Missing notification text";
    }

    public function getUri(): string
    {
        return "";
    }

}
