<?php

namespace App\Twig\Extension;

use Twig\TwigFilter;

class EnumTwigExtension extends \Twig\Extension\AbstractExtension
{

    public function getName(): string
    {
        return 'enum_extension';
    }

    public function getFilters()
    {
        return [
            new TwigFilter('enum_beautify', [$this, 'enumBeautify']),
        ];
    }

    /**
     * Beautifies ENUM variable names.
     *
     * - Changes camelCase strings to ucwords
     * - Replaces underscores and hyphens with spaces
     *
     * @param string $content
     * @return string
     */
    public static function enumBeautify(string $content): string
    {
        $content = \ucwords(\implode(' ', \preg_split('/(?=[A-Z])/', $content)));
        $content = \str_replace(['_', '-'], [' ', ' '], $content);
        return $content;
    }
}
