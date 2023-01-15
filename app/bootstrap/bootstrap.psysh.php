<?php

use App\Config\Config;

/**
 * Available REPL variables:
 * - Config::class
 * - $container        \Psr\ContainerInterface
 * - $logger           \Psr\LoggerInterface
 * - $dbal             \Doctrine\DBAL\Connection
 * - $em               \Doctrine\ORM\EntityManager
 * - $cache            \Psr\SimpleCache\CacheInterface
 */

// Load app bootstrap
require __DIR__ . '/bootstrap.php';

// Set Doctrine DBAL and EntityManager variables
if(Config::get('doctrine.is_enabled') === true){
    $dbal = $container->get(\Doctrine\DBAL\Connection::class);
    $em   = $container->get(\Doctrine\ORM\EntityManager::class);
}

// Set variables
$cache      = $container->get(\Psr\SimpleCache\CacheInterface::class);
$session    = $container->get(\Compwright\PhpSession\Session::class);
$locale     = $container->get(\App\I18n\Locale::class);
$translator = $container->get(\App\I18n\Translator::class);
