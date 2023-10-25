<?php

namespace App\Notifications\Notification;

use App\Enum\UserRole;

use Doctrine\ORM\EntityManager;

use App\Notifications\Notification;
use App\Notifications\NotificationInterface;

class WorkshopItemCommentReportNotification extends Notification implements NotificationInterface {

    public function getRequiredUserRole(): UserRole
    {
        return UserRole::Moderator;
    }

    public function getText(): string
    {
        return "@{$this->data['username']} reported a comment on **{$this->data['item_name']}**";
    }

    public function getUri(): string
    {
        return "/workshop/item/{$this->data['item_id']}#comment-{$this->data['comment_id']}";
    }

    public function getNotificationTitle(): string
    {
        return "New workshop comment report";
    }

    public function getDefaultSettings(): array
    {
        return [
            'website' => true,
            'email'   => true,
        ];
    }

}
