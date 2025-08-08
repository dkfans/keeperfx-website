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

    public function getTests()
    {
        return [
            new TwigTest('instanceof', [$this, 'isInstanceof'])
        ];
    }

    /**
     * @param $var
     * @param $instance
     * @return bool
     */
    public function isInstanceof($var, $instance)
    {
        return  $var instanceof $instance;
    }
}
