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

        // Get session
        $session = $container->get(Session::class);

        // Make sure we have a CSRF storage array in the session
        if(!isset($session['csrf'])) {
            $session['csrf'] = [];
        }

        // We need to pass the sub-array as a reference to the CSRF Guard class
        $csrf_storage = &$session['csrf'];

        // Create CSRF Guard
        return new \Slim\Csrf\Guard(
            $container->get(ResponseFactory::class),
            Config::get('csrf.prefix'),
            $csrf_storage,
            Config::get('csrf.failure_handler'),
            Config::get('csrf.storage_limit'),
            Config::get('csrf.strength'),
            Config::get('csrf.persistent_token_mode')
        );
    },

];
