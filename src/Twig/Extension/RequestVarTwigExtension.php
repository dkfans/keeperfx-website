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

    public function getFunctions(): array
    {
        return [
            new \Twig\TwigFunction('get_post_var', [$this, 'getPostVar']),
            new \Twig\TwigFunction('get_query_param', [$this, 'getQueryParam']),
        ];
    }

    public function getPostVar(string $name, $default = ''){
        return (string) ($_POST[$name] ?? $default);
    }

    public function getQueryParam(string $name, $default = ''){
        return (string) ($_GET[$name] ?? $default);
    }
}
