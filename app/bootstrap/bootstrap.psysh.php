<?php

/**
 * This file will load the app bootstrap and load a ton of useful classes into variables.
 */

// Load app bootstrap
require __DIR__ . '/bootstrap.php';

/** @var Psr\Container\ContainerInterface $container */

/** @var Doctrine\DBAL\Connection $dbal */
$dbal = $container->get(Doctrine\DBAL\Connection::class);

/** @var Doctrine\ORM\EntityManager $em */
$em = $container->get(Doctrine\ORM\EntityManager::class);

/** @var Psr\SimpleCache\CacheInterface $cache */
$cache = $container->get(Psr\SimpleCache\CacheInterface::class);

/** @var Compwright\PhpSession\Session $session */
$session = $container->get(Compwright\PhpSession\Session::class);

/** @var App\I18n\Locale $locale */
$locale = $container->get(App\I18n\Locale::class);

/** @var App\I18n\Translator $translator */
$translator = $container->get(App\I18n\Translator::class);

/** @var App\DiscordNotifier $discord */
$discord = $container->get(App\DiscordNotifier::class);

/** @var App\Notifications\NotificationCenter $nc */
$nc = $container->get(App\Notifications\NotificationCenter::class);
