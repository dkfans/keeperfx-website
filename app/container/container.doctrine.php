<?php

use App\Config\Config;

use Doctrine\ORM\ORMSetup;

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
        $orm_config = ORMSetup::createAttributeMetadataConfiguration(
            Config::get('doctrine.entity_dirs'),
            Config::get('doctrine.dev_mode'),
            Config::get('doctrine.proxy_dir'),
            $cache
        );
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
