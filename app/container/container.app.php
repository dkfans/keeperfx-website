<?php


use Compwright\PhpSession\Session;
use DebugBar\Bridge\NamespacedTwigProfileCollector;
use Psr\Container\ContainerInterface;

use Psr\Log\LoggerInterface;
use Slim\Psr7\Factory\ResponseFactory;

use Twig\Environment as TwigEnvironment;

return [

    // Debug bar
    \DebugBar\StandardDebugBar::class => DI\create(),

];
