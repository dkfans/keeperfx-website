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
        // Check if we have an uppercase enum name with only one word
        if (
            \strtoupper($content) === $content &&
            \str_contains($content, '-') === false &&
            \str_contains($content, '_') === false
        ) {
            // Return content as a single word with an uppercase first letter
            return \ucfirst(\strtolower($content));
        }

        // Uppercase the first letters of the enum and split it into words
        $content = \ucwords(\implode(' ', \preg_split('/(?=[A-Z])/', $content)));
        $content = \str_replace(['_', '-'], [' ', ' '], $content);
        return \trim($content);
    }
}
