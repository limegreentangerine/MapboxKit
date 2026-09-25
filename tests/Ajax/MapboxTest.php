<?php

namespace Mapbox\Tests\Ajax;

use Mapbox\Ajax\Mapbox;
use Mapbox\Tests\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

class MapboxTest extends TestCase
{
    public function testGetApiKeyReturnsConfiguredKeyAsJson(): void
    {
        $this->bindApiKey('pk.test-key');

        $response = (new Mapbox())->getApiKey();

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['apiKey' => 'pk.test-key'], json_decode($response->getContent(), true));
    }

    public function testGetApiKeyReturnsNullWhenNotConfigured(): void
    {
        $this->bindApiKey(null);

        $response = (new Mapbox())->getApiKey();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['apiKey' => null], json_decode($response->getContent(), true));
    }
}
