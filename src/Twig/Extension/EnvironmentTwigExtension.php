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
     * @return mixed
     */
    public function getEnvironmentVar(string $var): mixed
    {
        $val = $_ENV[$var] ?? null;

        if($val === null){
            return null;
        }

        if(\filter_var($val, \FILTER_VALIDATE_INT) !== false){
            return (int) $val;
        }

        if(\filter_var($val, \FILTER_VALIDATE_FLOAT) !== false){
            return (float) $val;
        }

        return (string) $val;
    }
}
