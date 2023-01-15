<?php

namespace App\Twig\Extension;

class AssetTwigExtension extends \Twig\Extension\AbstractExtension
{
    private const PUBLIC_ROOT = APP_ROOT . '/public';

    public function getName(): string
    {
        return 'asset_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'asset',
                [$this, 'getAssetUri'],
            ),
        ];
    }

    /**
     * Retrieve an asset URI
     *
     * @param string $var
     * @return string
     */
    public function getAssetUri(string $uri): string
    {
        $asset_uri  = '/' . \ltrim($uri, '/');
        $asset_file = self::PUBLIC_ROOT . $asset_uri;

        if(!\file_exists($asset_file)){
            return $asset_uri;
        }

        $checksum = \substr(\md5(\filemtime($asset_file)), 0, 6);
        return $asset_uri . '?' . $checksum;
    }
}
