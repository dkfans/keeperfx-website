<?php

namespace App\Twig\Extension;

class StringShortenTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getName(): string
    {
        return 'string_shorten_extension';
    }

    public function getFilters(): array
    {
        return [
            new \Twig\TwigFilter(
                'shorten',
                [$this, 'shortenString'],
            ),
        ];
    }

    /**
     * Shorten a string to a max length.
     */
    public function shortenString(?string $string, int $max_length): ?string
    {
        if (!\is_string($string)) {
            return $string;
        }

        // Get length
        $string_length = \strlen($string);

        // Check if string can be returned directly
        if (($string_length - 3) <= $max_length) {
            return $string;
        }

        // Get the part length
        $max_length_part     = ($max_length - 3) / 2;
        $length_start_offset = \ceil($max_length_part);
        $length_end_offset   = \floor($max_length_part);

        // Create strings
        $string_start = \substr($string, 0, $length_start_offset);
        $string_end   = \substr($string, $string_length - $length_end_offset);

        // Return new string
        return $string_start . '...' . $string_end;
    }
}
