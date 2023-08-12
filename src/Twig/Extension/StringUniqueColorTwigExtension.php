<?php

namespace App\Twig\Extension;

use Twig\TwigFilter;

class StringUniqueColorTwigExtension extends \Twig\Extension\AbstractExtension
{

    public function getName(): string
    {
        return 'string_unique_color_extension';
    }

    public function getFilters()
    {
        return [
            new TwigFilter('unique_color', [$this, 'unique_color'], ['is_safe' => ['all']]),
        ];
    }

    /**
     * Generate a unique color for this specific string
     *
     * @param string $string
     * @return string
     */
    public function unique_color(string $string): string
    {
        $color_hex = \substr(\md5($string), 0, 6);
        return "<span style='color:#{$color_hex}'>{$string}</span>";
    }
}
