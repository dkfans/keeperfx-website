<?php

use App\Config\Config;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Doctrine container definitions
 */
return [

    \Doctrine\DBAL\Configuration::class => DI\create(),

    \Doctrine\DBAL\Connection::class => function (\Doctrine\DBAL\Configuration $dbal_config) {
        return \Doctrine\DBAL\DriverManager::getConnection(
            Config::get('doctrine.connection'),
            $dbal_config
        );
    },

    \Doctrine\ORM\Configuration::class => function (CacheItemPoolInterface $cache){

        // Create ORM config
        $orm_config = ORMSetup::createAttributeMetadataConfiguration(
            Config::get('doctrine.entity_dirs'),
            Config::get('doctrine.dev_mode'),
            Config::get('doctrine.proxy_dir'),
            $cache
        );

        // Set table naming strategy
        if(\is_object(Config::get('doctrine.orm_naming_strategy'))){
            $orm_config->setNamingStrategy(Config::get('doctrine.orm_naming_strategy'));
        }

        // Set proxy class generation mode
        $orm_config->setAutoGenerateProxyClasses(Config::get('doctrine.proxy_class_generation'));

        // Enable query cache
        if(Config::get('doctrine.enable_query_cache')){
            $orm_config->setQueryCache($cache);
        }

        // Enable result cache
        if(Config::get('doctrine.enable_result_cache')){
            $orm_config->setResultCache($cache);
        }

        return $orm_config;
    },

    \Doctrine\ORM\EntityManager::class => DI\create()->constructor(
        DI\get(\Doctrine\DBAL\Connection::class),
        DI\get(\Doctrine\ORM\Configuration::class)
    ),

    \Doctrine\Migrations\DependencyFactory::class => function(\Doctrine\DBAL\Connection $conn, \Doctrine\ORM\Configuration $config) {

        // Bypass any caching
        $config->setAutoGenerateProxyClasses(\Doctrine\Common\Proxy\AbstractProxyFactory::AUTOGENERATE_EVAL);
        $config->setMetadataCache(new ArrayAdapter());

        // Create non caching entity manager
        $em = new \Doctrine\ORM\EntityManager($conn, $config);

        return Doctrine\Migrations\DependencyFactory::fromEntityManager(
            new \Doctrine\Migrations\Configuration\Migration\ConfigurationArray(Config::get('doctrine.migration_config')),
            new \Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager($em)
        );
    }
];
