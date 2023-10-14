<?php

namespace App\Notifications\Notification;

interface NotificationInterface {

    public function __construct(\DateTime $timestamp, array|null $data);

    public function getTimestamp(): \DateTime;

    public function getText(): string;

    public function getUri(): string;

}
