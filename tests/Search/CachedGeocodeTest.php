<?php

namespace MapboxKit\Tests\Search;

use MapboxKit\Tests\TestCase;
use MapboxKit\Search\CachedGeocode;

class CachedGeocodeTest extends TestCase
{
    public function testPutCachesFeatureForAnHour(): void
    {
        $cache = new CachedGeocode();
        $feature = (object) ['id' => 'place.123'];

        $cache->put('London', $feature);

        $this->assertEquals(['feature' => $feature], $cache->get('London'));
        // Stash knocks a random 0-15% off each TTL to stop entries saved together expiring together
        $expiresIn = $this->cachePool->getItem($cache->cacheKey('London'))->getExpiration()->getTimestamp() - time();
        $this->assertGreaterThanOrEqual(floor(3600 * 0.85) - 1, $expiresIn);
        $this->assertLessThanOrEqual(3600, $expiresIn);
    }

    public function testGetReturnsNullOnMiss(): void
    {
        $this->assertNull((new CachedGeocode())->get('London'));
    }

    public function testPutCachesNoMatchDistinctlyFromAMiss(): void
    {
        $cache = new CachedGeocode();

        $cache->put('Nowhere', null);

        $this->assertSame(['feature' => null], $cache->get('Nowhere'));
    }

    public function testCacheKeyIsSafeForStashNamespaces(): void
    {
        $key = (new CachedGeocode())->cacheKey('Flat 1/2, High Street');

        $this->assertStringStartsWith('mapbox_geocode_', $key);
        $this->assertStringNotContainsString('/', $key);
    }

    public function testClearAllRemovesCachedLocations(): void
    {
        $cache = new CachedGeocode();
        $cache->put('London', (object) ['id' => 'place.123']);
        $this->assertSame([$cache->cacheKey('London')], $cache->getAllCachedKeys());

        $cache->clearAll();

        $this->assertNull($cache->get('London'));
        $this->assertSame([], $cache->getAllCachedKeys());
    }
}
