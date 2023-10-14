<?php

namespace App\Notifications\Notification;

class Notification implements NotificationInterface {

    protected \DateTime $timestamp;

    protected array $data;

    public function __construct(\DateTime $timestamp, array|null $data)
    {
        $this->timestamp = $timestamp;
        $this->data      = $data;
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
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
