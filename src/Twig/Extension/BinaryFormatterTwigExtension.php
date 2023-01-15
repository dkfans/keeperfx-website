<?php

namespace App\Twig\Extension;

use Twig\TwigFilter;

use ByteUnits\Binary as BinaryFormatter;


class BinaryFormatterTwigExtension extends \Twig\Extension\AbstractExtension
{

    public function getName(): string
    {
        return 'binary_formatter_extension';
    }

    public function getFilters()
    {
        return [
            new TwigFilter('format_bytes', [$this, 'formatBytes']),
        ];
    }

    /**
     * Format bytes into a readable string.
     *
     * @param string $bytes
     * @return string
     */
    public function formatBytes(string $bytes): string
    {
        return BinaryFormatter::bytes($bytes)->format();
    }
}
