<?php

namespace App\Notifications\Notification;

interface NotificationInterface {

    public function __construct(\DateTime $timestamp, array|null $data, bool $is_read);

    public function getTimestamp(): \DateTime;

    public function getText(): string;

    public function getUri(): string;

    public function isRead(): bool;
}
