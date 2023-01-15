<?php

namespace App\Twig\Extension;

class EnvironmentTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getName(): string
    {
        return 'environment_extension';
    }

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction(
                'get_env',
                [$this, 'getEnvironmentVar'],
                ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Retrieve an environment variable
     *
     * @param string $var
     * @return string
     */
    public function getEnvironmentVar(string $var): string
    {
        return $_ENV[$var] ?? null;
    }
}
