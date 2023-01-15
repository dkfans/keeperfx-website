Cache
=====



## Usage

Both PSR-6 and PSR-16 caches are implemented.  
Load one of the following classes from the container or inject them into classes or controllers:
- PSR-6: `\Psr\Cache\CacheItemPoolInterface`
- PSR-16: `\Psr\SimpleCache\CacheInterface`

*PSR-16 is easier to use and in most cases recommended*

### PSR-6 usage
```

```

### PSR-16 usage
```

```

## Configuration

Settings can be set with the following **environment variables**:
```shell
APP_CACHE_ADAPTER=redis # possible values: filesystem,redis

APP_CACHE_REDIS_DSN=redis://127.0.0.1:6379
```

### Internal configuration

Internal configuration is found in: `<APP_ROOT>/config/cache.config.php`.  
In most cases you should use environment vars instead of editing this file.

### Adapters

| Adapter         | Information |
|-----------------|-------------|
| `filesystem`    | The filesystem adapter should typically only be used in development, or as a fallback. By default it saves the key-value cache files in: `<APP_ROOT>/cache/store`
| `redis`         | Redis requires either the `redis` php extension or the `predis/predis` package. If your PHP env does not have the extension, add the Predis package to the project: `composer require predis/predis`. To configure the Redis connection, have a look at the [DSN configuration](https://symfony.com/doc/current/components/cache/adapters/redis_adapter.html#configure-the-connection) documentation.
