<?php

declare(strict_types=1);

namespace App;

class AvatarGenerator
{
    /**
     * Generates the avatar and returns the GD Image object.
     */
    public static function generate(int $size, string $username, string $font): \GdImage
    {
        if (!\file_exists($font)) {
            throw new \InvalidArgumentException("Font file not found: {$font}");
        }

        $initials    = self::getInitials($username);
        [$r, $g, $b] = self::getBackgroundColor($username);

        // Create the canvas
        $image = \imagecreatetruecolor($size, $size);

        // Allocate colors
        $bgColor   = \imagecolorallocate($image, $r, $g, $b);
        $textColor = self::isLight($r, $g, $b)
            ? \imagecolorallocate($image, 0, 0, 0)
            : \imagecolorallocate($image, 255, 255, 255);

        // Fill background
        \imagefill($image, 0, 0, $bgColor);

        // Calculate font size (40% of the image size usually looks well-proportioned)
        $fontSize = $size * 0.4;

        // Calculate bounding box for perfect centering
        $bbox = \imageftbbox($fontSize, 0, $font, $initials);

        // $bbox array: 0=lower-left X, 1=lower-left Y, 2=lower-right X, 3=lower-right Y
        // 4=upper-right X, 5=upper-right Y, 6=upper-left X, 7=upper-left Y
        $textWidth  = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];

        $x = (int) (($size - $textWidth) / 2 - $bbox[0]);
        $y = (int) (($size - $textHeight) / 2 - $bbox[7]);

        // Draw the text
        \imagefttext($image, $fontSize, 0, $x, $y, $textColor, $font, $initials);

        return $image;
    }

    /**
     * Splits the username by space, dot, dash, or underscore.
     */
    private static function getInitials(string $username): string
    {
        // Split by the requested delimiters, ignoring empty strings
        $parts = \preg_split('/[\s.\-_]+/', \trim($username), -1, \PREG_SPLIT_NO_EMPTY);

        if (\count($parts) >= 2) {
            // Take the first letter of the first two parts
            $initials = \mb_substr($parts[0], 0, 1) . \mb_substr($parts[1], 0, 1);
        } elseif (\count($parts) === 1) {
            // Take the first two letters of the single part
            $initials = \mb_substr($parts[0], 0, 2);
        } else {
            // Fallback for empty strings
            $initials = '??';
        }

        return \mb_strtoupper($initials);
    }

    /**
     * Hashes the username to create a consistent, unique RGB background.
     */
    private static function getBackgroundColor(string $username): array
    {
        $hash = \md5($username);

        return [
            \hexdec(\substr($hash, 0, 2)), // Red
            \hexdec(\substr($hash, 2, 2)), // Green
            \hexdec(\substr($hash, 4, 2)), // Blue
        ];
    }

    /**
     * Calculates relative luminance to determine if text should be dark or light.
     */
    private static function isLight(int $r, int $g, int $b): bool
    {
        // Standard WCAG formula for perceived brightness
        $luminance = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);

        return $luminance > 127.5;
    }
}
