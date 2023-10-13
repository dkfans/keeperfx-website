<?php

namespace App\Notifications\Notification;

use App\Workshop\Exception\NotificationDataException;

/**
 * New user notification.
 *
 * This notification is sent to admins when a new user registers.
 */
class NewUserNotification implements NotificationInterface {

    private int $id;
    private \DateTime $timestamp;
    private string $username;

    public function __construct(\DateTime $timestamp, array|null $data)
    {
        if($data === null || !isset($data['username']) || !isset($data['id'])){
            throw new NotificationDataException("invalid notification data. 'username' and 'id' are required.");
        }

        if(!\is_numeric($data['username'])){
            throw new NotificationDataException("invalid notification data. 'username' must be a string.");

        }

        if(!\is_numeric($data['id'])){
            throw new NotificationDataException("invalid notification data. 'id' must be numeric.");
        }

        $this->timestamp = $timestamp;
        $this->username  = (string) $data['username'];
        $this->id        = (int) $data['id'];
    }

    public function getTimestamp(): \DateTime
    {
        return $this->timestamp;
    }

    public function getText(): string
    {
        return "New user registered! {$this->username} (ID: {$this->id})";
    }

    public function getUri(): string
    {
        return "/admin/user/{$this->id}";
    }

}
