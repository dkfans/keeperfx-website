<?php

use App\Config\Config;
use App\Kernel\Exception\ContainerException;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

return [
    // PSR-6 cache interface
    CacheItemPoolInterface::class => static function () {

        $cache = null;

        switch (Config::get('cache.adapter')) {
            case 'filesystem':
                $cache = new FilesystemAdapter(
                    Config::get('cache.namespace') ?? Config::get('app.app_name'),
                    Config::get('cache.default_lifetime'),
                    Config::get('cache.adapter_config.filesystem.dir')
                );
                break;
            case 'redis':
                $cache = new RedisAdapter(
                    RedisAdapter::createConnection(Config::get('cache.adapter_config.redis.dsn')),
                    Config::get('cache.namespace') ?? Config::get('app.app_name'),
                    Config::get('cache.default_lifetime'),
                    null
                );
                break;

        }

        if ($cache === null) {
            throw new ContainerException('Invalid cache adapter. Set a correct one in \'' . APP_ROOT . '/config/cache.config.php\'');
        }

        return $cache;
    },

    // PSR-16 cache interface
    CacheInterface::class => static function (CacheItemPoolInterface $psr6_cache) {
        return new Psr16Cache($psr6_cache);
    },

];
