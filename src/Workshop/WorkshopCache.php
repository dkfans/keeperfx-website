<?php

namespace App\Workshop;

use Psr\SimpleCache\CacheInterface;

class WorkshopCache
{
    private const BROWSE_CACHE_NAMESPACE = 'workshop-browse';
    private const BROWSE_CACHE_LIST_KEY  = 'all-keys';

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    /**
     * Get workshop browse page data from the cache.
     *
     * @param array<mixed> $query_params The query params that are used (these create a unique hash for this browser page)
     *
     * @return array<mixed>|null
     */
    public function getCachedBrowsePageData(array $query_params): ?array
    {
        $cache_key = self::BROWSE_CACHE_NAMESPACE . '-' . \md5(\serialize((array) $query_params));

        return $this->cache->get($cache_key);
    }

    /**
     * Store workshop browse page data into the cache.
     *
     * @param array<mixed> $query_params The query params that are used (these create a unique hash for this browser page)
     * @param array<mixed> $data         The data that must be cached
     */
    public function setCachedBrowsePageData(array $query_params, array $data): void
    {
        $cache_list_key = self::BROWSE_CACHE_NAMESPACE . '-' . self::BROWSE_CACHE_LIST_KEY;
        $cache_key      = self::BROWSE_CACHE_NAMESPACE . '-' . \md5(\serialize((array) $query_params));

        $this->cache->set($cache_key, $data);

        $browse_pages = $this->cache->get($cache_list_key);
        if ($browse_pages !== null && \is_string($browse_pages)) {
            $this->cache->set($cache_list_key, \json_encode(\array_merge(\json_decode($browse_pages), [$cache_key])));
        } else {
            $this->cache->set($cache_list_key, \json_encode([$cache_key]));
        }
    }

    public function clearAllCachedBrowsePageData(): void
    {
        $cache_list_key = self::BROWSE_CACHE_NAMESPACE . '-' . self::BROWSE_CACHE_LIST_KEY;

        $browse_pages = $this->cache->get($cache_list_key);
        if ($browse_pages !== null && \is_string($browse_pages)) {
            $pages = \json_decode($browse_pages);
            foreach ($pages as $page) {
                $this->cache->delete($page);
            }
        }

        $this->cache->delete($cache_list_key);
    }
}
