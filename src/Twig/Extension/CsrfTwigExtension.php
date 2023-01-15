<?php

namespace App\Twig\Extension;

use Slim\Csrf\Guard;

/**
 * CSRF Twig Extension.
 * Template for views:
 *      <input type="hidden" name="{{ csrf.keys.name }}" value="{{ csrf.name }}">
 *      <input type="hidden" name="{{ csrf.keys.value }}" value="{{ csrf.value }}">
 */
class CsrfTwigExtension extends \Twig\Extension\AbstractExtension implements \Twig\Extension\GlobalsInterface
{
    /**
     * @var Guard
     */
    protected $csrf;

    public function __construct(Guard $csrf)
    {
        $this->csrf = $csrf;
    }

    public function getName(): string
    {
        return 'csrf_extension';
    }

    public function getGlobals(): array
    {
        return [
            'csrf'   => [
                'keys' => [
                    'name'  => $this->csrf->getTokenNameKey(),
                    'value' => $this->csrf->getTokenValueKey()
                ],
                'name'  => $this->csrf->getTokenName(),
                'value' => $this->csrf->getTokenValue()
            ]
        ];
    }
}
