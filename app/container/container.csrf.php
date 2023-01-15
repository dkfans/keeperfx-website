<?php

use App\Config\Config;
use Compwright\PhpSession\Session;

use Slim\Psr7\Factory\ResponseFactory;

use Psr\Container\ContainerInterface;

/**
 * CSRF Container Definition
 * Uses `slim/csrf` library.
 */
return [

    \Slim\Csrf\Guard::class => function(ContainerInterface $container) {

        // Session object/array needs to be passed as a variable
        $session = $container->get(Session::class);

        return new \Slim\Csrf\Guard(
            $container->get(ResponseFactory::class),
            Config::get('csrf.prefix'),
            $session,
            Config::get('csrf.failure_handler'),
            Config::get('csrf.storage_limit'),
            Config::get('csrf.strength'),
            Config::get('csrf.persistent_token_mode')
        );
    },

];
