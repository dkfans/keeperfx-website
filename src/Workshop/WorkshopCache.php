<?php

namespace App\Workshop;

use Psr\SimpleCache\CacheInterface;

class WorkshopCache {

    private const BROWSE_CACHE_NAMESPACE     = 'workshop_browse';
    private const BROWSE_CACHE_LIST_KEY      = 'all_keys';

    public function __construct(
        private CacheInterface $cache,
    ){}

    public function getCachedBrowsePageData(array $query_params): array|null
    {
        $cache_key = self::BROWSE_CACHE_NAMESPACE . ':' . \md5(\serialize((array)$query_params));
        return $this->cache->get($cache_key);
    }

    public function setCachedBrowsePageData(array $query_params, array $data): void
    {
        $cache_list_key = self::BROWSE_CACHE_NAMESPACE . ':' . self::BROWSE_CACHE_LIST_KEY;
        $cache_key      = self::BROWSE_CACHE_NAMESPACE . ':' . \md5(\serialize((array)$query_params));

        $this->cache->set($cache_key, $data);

        $browse_pages = $this->cache->get($cache_list_key);
        if($browse_pages !== null && \is_string($browse_pages)){
            $this->cache->set($cache_list_key, \json_encode(array_merge(\json_decode($browse_pages),[$cache_key])));
        } else {
            $this->cache->set($cache_list_key, \json_encode([$cache_key]));
        }
    }

    public function clearAllCachedBrowsePageData(): void
    {
        $cache_list_key = self::BROWSE_CACHE_NAMESPACE . ':' . self::BROWSE_CACHE_LIST_KEY;

        $browse_pages = $this->cache->get($cache_list_key);
        if($browse_pages !== null && \is_string($browse_pages)){
            $pages = \json_decode($browse_pages);
            foreach($pages as $page){
                $this->cache->delete($page);
            }
        }

        $this->cache->delete($cache_list_key);
    }

}
