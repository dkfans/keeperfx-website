<?php

namespace App\Notifications\Notification;

interface NotificationInterface {

    public function loadData(\DateTime $timestamp, array|null $data): void;

    public function getText(): string;

    public function getTimestamp(): \DateTime;

    // public function setRead(bool $is_read): void;

}
