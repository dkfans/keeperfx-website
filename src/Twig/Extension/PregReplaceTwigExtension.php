<?php

namespace App\Twig\Extension;

use Twig\TwigFilter;

class PregReplaceTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getName(): string
    {
        return 'preg_replace_extension';
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter(
                'preg_replace',
                [$this, 'preg_replace'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Standard PHP preg_replace() function.
     */
    public function preg_replace(string $subject, array|string $pattern, array|string $replacement, int $limit = -1): string
    {
        return \preg_replace($pattern, $replacement, $subject, $limit);
    }
}
