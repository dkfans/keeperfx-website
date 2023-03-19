<?php

namespace App\Twig\Extension;

use URLify;
use Twig\TwigFilter;

class SlugifyTwigExtension extends \Twig\Extension\AbstractExtension
{

    public function getName(): string
    {
        return 'slug_extension';
    }

    public function getFilters()
    {
        return [
            new TwigFilter('slugify', [$this, 'slugify']),
        ];
    }

    /**
     * Slugify the string.
     *
     * @param string $string
     * @return string
     */
    public function slugify(string $string): string
    {
        return URLify::slug($string);
    }
}
