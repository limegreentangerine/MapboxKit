<?php

namespace MapboxKit\Tests\Support;

use MapboxKit\Api\Mapbox;
use GuzzleHttp\Client as HttpClient;

/**
 * Test seam: lets tests swap the Guzzle client ConnectionController creates internally.
 */
class TestableMapboxApi extends Mapbox
{
    public function setHttpClient(HttpClient $client): self
    {
        $this->client = $client;

        return $this;
    }
}
