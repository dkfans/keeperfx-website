<?php

use App\Config\Config;
use Doctrine\ORM\ORMSetup;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

/*
 * Doctrine container definitions
 */
return [
    DebugBar\Bridge\Doctrine\DebugBarSQLMiddleware::class => \DI\create(),

    Doctrine\Common\EventManager::class => DI\create(),

    Doctrine\DBAL\Configuration::class => static function (ContainerInterface $container) {
        $dbal_config = new Doctrine\DBAL\Configuration();

        // Add php-debugbar middleware for logging SQL
        if (isset($_ENV['APP_ENV']) && $_ENV['APP_ENV'] === 'dev') {
            $sql_middleware = $container->get(DebugBar\Bridge\Doctrine\DebugBarSQLMiddleware::class);
            $dbal_config->setMiddlewares(
                \array_merge($dbal_config->getMiddlewares(), [$sql_middleware])
            );
        }

        return $dbal_config;
    },

    Doctrine\DBAL\Connection::class => static function (Doctrine\DBAL\Configuration $dbal_config) {
        return Doctrine\DBAL\DriverManager::getConnection(
            Config::get('doctrine.connection'),
            $dbal_config,
        );
    },

    Doctrine\ORM\Configuration::class => static function (CacheItemPoolInterface $cache) {

        // Create ORM config
        $orm_config = ORMSetup::createAttributeMetadataConfig(
            Config::get('doctrine.entity_dirs'),
            Config::get('doctrine.dev_mode'),
            Config::get('doctrine.proxy_dir'),
            $cache
        );

        // Enable PHP native lazy objects
        $orm_config->enableNativeLazyObjects(true);

        // Set table naming strategy
        if (\is_object(Config::get('doctrine.orm_naming_strategy'))) {
            $orm_config->setNamingStrategy(Config::get('doctrine.orm_naming_strategy'));
        }

        // Set proxy class generation mode
        $orm_config->setAutoGenerateProxyClasses(Config::get('doctrine.proxy_class_generation'));

        // Enable query cache
        if (Config::get('doctrine.enable_query_cache')) {
            $orm_config->setQueryCache($cache);
        }

        // Enable result cache
        if (Config::get('doctrine.enable_result_cache')) {
            $orm_config->setResultCache($cache);
        }

        return $orm_config;
    },

    Doctrine\ORM\EntityManager::class => static function (ContainerInterface $container, Doctrine\DBAL\Connection $conn, Doctrine\ORM\Configuration $config, Doctrine\Common\EventManager $event_manager) {

        // Handle database read only mode
        if ($_ENV['APP_DB_READ_ONLY_MODE'] ?? false) {
            $event_manager->addEventListener(
                [Doctrine\ORM\Events::preFlush],
                new App\Doctrine\ReadOnlyListener()
            );
        }

        return new Doctrine\ORM\EntityManager($conn, $config, $event_manager);
    },

    Doctrine\Migrations\DependencyFactory::class => static function (Doctrine\DBAL\Connection $conn, Doctrine\ORM\Configuration $config) {

        // Bypass any caching
        $config->setAutoGenerateProxyClasses(Doctrine\ORM\Proxy\ProxyFactory::AUTOGENERATE_ALWAYS);
        $config->setMetadataCache(new ArrayAdapter());

        // Create non caching entity manager
        $em = new Doctrine\ORM\EntityManager($conn, $config);

        return Doctrine\Migrations\DependencyFactory::fromEntityManager(
            new Doctrine\Migrations\Configuration\Migration\ConfigurationArray(Config::get('doctrine.migration_config')),
            new Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager($em)
        );
    },
];
