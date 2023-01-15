<?php

namespace App\Twig;

use Psr\Container\ContainerInterface;

class TwigGlobalProvider {

    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public static function getGlobals()
    {
        return [];
    }
}
