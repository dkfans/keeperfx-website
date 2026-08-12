<?php

namespace App\Twig\Extension;

use ByteUnits\Binary as BinaryFormatter;
use Twig\TwigFilter;

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
     */
    public function formatBytes(string $bytes): string
    {
        return BinaryFormatter::bytes($bytes)->format();
    }
}
