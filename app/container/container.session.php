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
        $started = $manager->start(); // returns true if session is already started
        if ($started === false) {
            throw new \RuntimeException("The session failed to start");
        }
        return $manager->getCurrentSession();
    }

];
