<?php

namespace App\Twig\Extension;

use App\Account;
use App\Notifications\NotificationCenter;
use Twig\TwigFilter;

class NotificationTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{
    public function __construct(
        private Account $account,
        private NotificationCenter $nc,
    ) {
    }

    public function getName(): string
    {
        return 'notification_extension';
    }

    public function getFilters()
    {
        return [
            new TwigFilter('notification_render', [$this, 'notificationRender'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Render notification text.
     */
    public function notificationRender(string $string): string
    {
        // @username
        $string = \preg_replace('/@([A-Za-z0-9_]+)/', '<span class="text-special">$1</span>', $string);

        // **interesting**
        $string = \preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $string);

        // `code`
        $string = \preg_replace('/\`(.*?)\`/', '<code>$1</code>', $string);

        // ::muted::
        $string = \preg_replace('/\:\:(.*?)\:\:/', '<span class="text-muted">$1</span>', $string);

        return $string;
    }

    public function getGlobals(): array
    {
        return [
            'unread_notifications' => $this->account->isLoggedIn() ? $this->nc->getUnreadNotifications() : [],
        ];
    }
}
