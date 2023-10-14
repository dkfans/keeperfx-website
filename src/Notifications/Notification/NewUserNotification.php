<?php

namespace App\Notifications\Notification;

use Doctrine\ORM\EntityManager;

use App\Notifications\Exception\NotificationDataException;

class NewUserNotification extends Notification {

    public function getText(): string
    {
        return "New user registered! {$this->data['username']} (ID: {$this->data['id']})";
    }

    public function getUri(): string
    {
        return "/admin/user/{$this->data['id']}";
    }

}
