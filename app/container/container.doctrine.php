<?php

use App\Config\Config;

use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use Psr\Container\ContainerInterface;

use App\Kernel\Exception\ContainerException;

/**
 * Doctrine container definitions
 */
return [

    \Doctrine\DBAL\Configuration::class => DI\create(),

    \Doctrine\DBAL\Connection::class => function (ContainerInterface $container, \Doctrine\DBAL\Configuration $dbal_config) {

        // Make sure Doctrine is enabled in config
        if(Config::get('doctrine.is_enabled') === false){
            throw new ContainerException(
                'The container tried to resolve Doctrine but it\'s disabled. (\\Doctrine\\DBAL\\Connection) ' .
                'Enable it in: \'' . APP_ROOT . '/config/doctrine.config.php\''
            );
        }

        // Get DBAL connection
        $conn = \Doctrine\DBAL\DriverManager::getConnection(
            Config::get('doctrine.connection'),
            $dbal_config
        );

        return $conn;
    },

    \Doctrine\ORM\Configuration::class => function (ContainerInterface $container){

        // Create ORM config
        /** @var \Doctrine\ORM\Configuration $orm_config */

        if(Config::get('doctrine.force_annotations') === false){
            $orm_config = ORMSetup::createAttributeMetadataConfiguration(
                Config::get('doctrine.entity_dirs'),
                Config::get('doctrine.dev_mode'),
            );
        } else {
            $orm_config = ORMSetup::createAnnotationMetadataConfiguration(
                Config::get('doctrine.entity_dirs'),
                Config::get('doctrine.dev_mode'),
            );
        }

        // Handle proxies
        if(Config::get('doctrine.proxy.is_enabled')){
            $orm_config->setProxyDir(Config::get('doctrine.proxy.dir'));
            $orm_config->setProxyNamespace(Config::get('doctrine.proxy.namespace'));
            $orm_config->setAutoGenerateProxyClasses(Config::get('doctrine.proxy.auto_generate'));
        }

        // Set caches
        if(Config::get('doctrine.dev_mode')){

            // Never cache queries and metadata when in dev mode
            $orm_config->setQueryCache(new ArrayAdapter());
            $orm_config->setMetadataCache(new ArrayAdapter());

            // TODO: implement result cache
            // Even in dev mode we should use one to make sure everything works as expected
            // --> Maybe make a unique configuration option for this
            // $orm_config->setResultCache(new ArrayAdapter());
        } else {

            // Query and Metadata caching can be stored as files
            // They only change when queries and entities are edited
            $orm_config->setQueryCache(new \Symfony\Component\Cache\Adapter\PhpFilesAdapter('query', 0, Config::get('doctrine.cache_dir')));
            $orm_config->setMetadataCache(new \Symfony\Component\Cache\Adapter\PhpFilesAdapter('metadata', 0, Config::get('doctrine.cache_dir')));

            // TODO: Add a result cache implementation (create a Kernel\CacheFactory)
            // https://www.doctrine-project.org/projects/doctrine-orm/en/2.8/reference/caching.html#result-cache
            // A result cache must be enabled on the query itself. It's not global
            // $orm_config->setResultCache(new \Symfony\Component\Cache\Adapter\PhpFilesAdapter('result', 0, Config::get('doctrine.cache_dir')));
        }

        // Add naming strategy
        if(\is_object(Config::get('doctrine.orm_naming_strategy'))){
            $orm_config->setNamingStrategy(Config::get('doctrine.orm_naming_strategy'));
        }

        return $orm_config;
    },

    \Doctrine\ORM\EntityManager::class => DI\create()->constructor(
        DI\get(\Doctrine\DBAL\Connection::class),
        DI\get(\Doctrine\ORM\Configuration::class)
    ),

    \Doctrine\Migrations\DependencyFactory::class => function(\Doctrine\ORM\EntityManager $em) {
        return Doctrine\Migrations\DependencyFactory::fromEntityManager(
            new \Doctrine\Migrations\Configuration\Migration\ConfigurationArray(Config::get('doctrine.migration_config')),
            new \Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager($em)
        );
    }
];
