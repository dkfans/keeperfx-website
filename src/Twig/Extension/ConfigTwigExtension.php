<?php

namespace App\Twig\Extension;

use App\Config\Config;

class ConfigTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getName(): string
    {
        return 'config_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'config',
                [$this, 'getConfigOption'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    public function getConfigOption(string $var): mixed
    {
        return Config::get($var);
    }
}
