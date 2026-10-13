<?php

namespace App\Twig\Extension;

/**
 * Request Twig Extension.
 */
class RequestVarTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getName(): string
    {
        return 'request_extension';
    }

    /**
     * @return \Twig\TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('get_post_var', [$this, 'getPostVar']),
            new \Twig\TwigFunction('get_query_param', [$this, 'getQueryParam']),
        ];
    }

    public function getPostVar(string $name, string $default = ''): string
    {
        return (string) ($_POST[$name] ?? $default);
    }

    public function getQueryParam(string $name, string $default = ''): string
    {
        return (string) ($_GET[$name] ?? $default);
    }
}
