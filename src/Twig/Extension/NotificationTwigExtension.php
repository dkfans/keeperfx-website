<?php

namespace App\Twig\Extension;

use Twig\TwigFilter;

class NotificationTwigExtension extends \Twig\Extension\AbstractExtension
{

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
     * Render notification text
     *
     * @param string $string
     * @return string
     */
    public function notificationRender(string $string): string
    {
        // @username
        $string = \preg_replace('/@([A-Za-z0-9_]+)/', '<span class="text-special">$1</span>', $string);

        // **interesting**
        $string = \preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $string);

        return $string;
    }
}
