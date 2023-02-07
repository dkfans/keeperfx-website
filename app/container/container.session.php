<?php

use App\Config\Config;

use Psr\SimpleCache\CacheInterface;
use Psr\Container\ContainerInterface;

return [

    \Compwright\PhpSession\Factory::class => DI\create(),

    \Compwright\PhpSession\Manager::class => function(\Compwright\PhpSession\Factory $factory, CacheInterface $cache){
        return $factory->psr16Session($cache, Config::get('session'));
    },

    \Compwright\PhpSession\Session::class => function(\Compwright\PhpSession\Manager $manager){

        // Check if session has already been started and start it if it isn't
        $started = $manager->start();
        if ($started === false) {

            // Session was not started yet, so this condition should now be true
            $started = $manager->start();
            if ($started === false) {
                throw new \RuntimeException("The session failed to start");
            }
        }

        return $manager->getCurrentSession();
    }

];
