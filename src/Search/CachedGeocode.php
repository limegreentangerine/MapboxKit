<?php

namespace MapboxKit\Search;

use MapboxKit\Log\MapboxLogger;
use ClassKit\Search\CachedSearch;

/**
 * Caches geocoding results from the Mapbox API, keyed by location.
 */
class CachedGeocode extends CachedSearch
{
    public function __construct(string $indexKey = 'mapbox_geocode', int $ttl = 3600)
    {
        parent::__construct(MapboxLogger::class, $indexKey, $ttl);
    }

    /**
     * Normalises a location so that equivalent searches share a cache entry.
     */
    protected function normaliseLocation(string $location): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', trim($location)));
    }

    /**
     * Builds the cache key for a location. The location is hashed because Stash
     * treats `/` in a key as a namespace separator.
     */
    public function cacheKey(string $location): string
    {
        return $this->indexKey . '_' . sha1($this->normaliseLocation($location));
    }

    /**
     * Returns the cached payload (`['feature' => object|null]`) for a location, or null on a miss.
     */
    public function get(string $location): ?array
    {
        $item = $this->cache->getItem($this->cacheKey($location));

        return $item->isHit() ? $item->get() : null;
    }

    /**
     * Caches the matched feature for a location, or null when there was no confident match.
     * The feature is wrapped in an array because Stash treats null data as a miss.
     */
    public function put(string $location, ?object $feature): void
    {
        $cacheKey = $this->cacheKey($location);
        $item = $this->cache->getItem($cacheKey);
        $item->set(['feature' => $feature])->expiresAfter($this->ttl);
        $this->cache->save($item);

        $this->storeCacheKey($cacheKey);
    }
}
