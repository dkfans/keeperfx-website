<?php

namespace App\Twig\Extension;

use App\Helper\FormatHelper;
use Twig\TwigFilter;

class BinaryFormatterTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getName(): string
    {
        return 'binary_formatter_extension';
    }

    /**
     * @return \Twig\TwigFilter[]
     */
    public function getFilters(): array
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
        return FormatHelper::formatBytes((int) $bytes);
    }
}
