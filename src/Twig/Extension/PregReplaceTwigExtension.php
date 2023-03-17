<?php

namespace App\Twig\Extension;

use Twig\TwigFilter;

class PregReplaceTwigExtension extends \Twig\Extension\AbstractExtension
{

    public function getName(): string
    {
        return 'preg_replace_extension';
    }

    public function getFilters()
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
     *
     * @param array|string $pattern
     * @param array|string $replacement
     * @param array|string $subject
     * @param integer $limit
     * @return string
     */
    public function preg_replace(string $subject, array|string $pattern, array|string $replacement, int $limit = -1): string
    {
        return \preg_replace($pattern, $replacement, $subject, $limit);
    }
}
