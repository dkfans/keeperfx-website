<?php

namespace App\Helper;

class FormatHelper
{
    public static function formatBytes(int $bytes, int $precision = 2, string $separator = ' '): string
    {
        $units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
        $bytes = \max($bytes, 0);
        $pow   = \floor(($bytes ? \log($bytes) : 0) / \log(1024));
        $pow   = \min($pow, \count($units) - 1);

        return \round($bytes / (1024 ** $pow), $precision) . $separator . $units[$pow];
    }
}
