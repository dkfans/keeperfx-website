<?php

namespace App\Twig\Extension;

use Twig\TwigTest;

/**
 * InstanceOf Twig Extension.
 */
class InstanceOfTwigExtension extends \Twig\Extension\AbstractExtension
{
    public function getName(): string
    {
        return 'instanceof_extension';
    }

    /**
     * @return \Twig\TwigTest[]
     */
    public function getTests(): array
    {
        return [
            new TwigTest('instanceof', [$this, 'isInstanceof']),
        ];
    }

    /**
     * @return bool
     */
    public function isInstanceof($var, $instance): bool
    {
        return $var instanceof $instance;
    }
}
