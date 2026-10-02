<?php

namespace MapboxKit\Tests\Api;

use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use MapboxKit\Tests\TestCase;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ConnectException;
use MapboxKit\Tests\Support\TestableMapboxApi;
use Symfony\Component\HttpFoundation\JsonResponse;

class MapboxTest extends TestCase
{
    /**
     * @var array<int, array{request: \Psr\Http\Message\RequestInterface}>
     */
    private array $history = [];

    /**
     * Builds the API with the given key and a Guzzle client that replays $responses.
     *
     * @param array<int, Response|\Throwable> $responses
     */
    private function makeApi(array $responses, ?string $apiKey = 'pk.test-key'): TestableMapboxApi
    {
        $this->bindApiKey($apiKey);

        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($this->history));

        return (new TestableMapboxApi())->setHttpClient(new HttpClient(['handler' => $stack]));
    }

    private function geocodeResponse(array $features): Response
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode([
            'type' => 'FeatureCollection',
            'features' => $features,
        ]));
    }

    private function feature(float $relevance, string $name = 'London'): array
    {
        return [
            'id' => 'place.123',
            'place_name' => $name . ', Greater London, England, United Kingdom',
            'relevance' => $relevance,
            'center' => [-0.1275, 51.50722],
        ];
    }

    public function testConstructorLoadsApiKeyFromPackageConfig(): void
    {
        $api = $this->makeApi([]);

        $this->assertSame('pk.test-key', $api->getApiKey());
    }

    public function testSetApiKeyIsFluent(): void
    {
        $api = $this->makeApi([]);

        $this->assertSame($api, $api->setApiKey('pk.other'));
        $this->assertSame('pk.other', $api->getApiKey());
    }

    public function testConstructorThrowsWhenApiKeyIsNotConfigured(): void
    {
        // Pins current behaviour: setApiKey(string) rejects the null config value.
        $this->expectException(\TypeError::class);

        $this->makeApi([], null);
    }

    public function testGetLocationDetailsSendsAuthenticatedGbRestrictedRequest(): void
    {
        $api = $this->makeApi([$this->geocodeResponse([])]);

        $api->getLocationDetails('London');

        $this->assertCount(1, $this->history);
        /** @var Request $request */
        $request = $this->history[0]['request'];
        $this->assertSame('GET', $request->getMethod());
        $this->assertSame('api.mapbox.com', $request->getUri()->getHost());
        $this->assertSame('/geocoding/v5/mapbox.places/London.json', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(['access_token' => 'pk.test-key', 'country' => 'gb'], $query);
    }

    public static function locationEncodingProvider(): array
    {
        return [
            'space' => ['St Albans', 'St%20Albans'],
            'slash' => ['Newcastle/Tyne', 'Newcastle%2FTyne'],
            'question mark' => ['Stoke?on', 'Stoke%3Fon'],
            'hash' => ['No. 10 #2', 'No.%2010%20%232'],
            'ampersand' => ['Brighton & Hove', 'Brighton%20%26%20Hove'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('locationEncodingProvider')]
    public function testGetLocationDetailsUrlEncodesLocation(string $location, string $encoded): void
    {
        $api = $this->makeApi([$this->geocodeResponse([])]);

        $api->getLocationDetails($location);

        /** @var Request $request */
        $request = $this->history[0]['request'];
        $this->assertSame('/geocoding/v5/mapbox.places/' . $encoded . '.json', $request->getUri()->getPath());

        parse_str($request->getUri()->getQuery(), $query);
        $this->assertSame(['access_token' => 'pk.test-key', 'country' => 'gb'], $query);
    }

    public function testGetLocationDetailsReturnsFirstFeatureWhenRelevant(): void
    {
        $api = $this->makeApi([$this->geocodeResponse([$this->feature(0.95), $this->feature(0.9, 'Londonderry')])]);

        $response = $api->getLocationDetails('London');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getContent(), true);
        $this->assertSame('place.123', $body['id']);
        $this->assertStringStartsWith('London,', $body['place_name']);
        $this->assertSame([-0.1275, 51.50722], $body['center']);
    }

    public function testGetLocationDetailsReturnsNullWhenRelevanceIsAtOrBelowThreshold(): void
    {
        $api = $this->makeApi([$this->geocodeResponse([$this->feature(0.7)])]);

        $this->assertNull($api->getLocationDetails('Somewhere vague'));
    }

    public function testGetLocationDetailsReturnsNullWhenNoFeatures(): void
    {
        $api = $this->makeApi([$this->geocodeResponse([])]);

        $this->assertNull($api->getLocationDetails('Nowhere'));
    }

    public function testGetLocationDetailsReturnsNullWhenBodyHasNoFeaturesKey(): void
    {
        $api = $this->makeApi([new Response(200, [], json_encode(['type' => 'FeatureCollection']))]);

        $this->assertNull($api->getLocationDetails('London'));
    }

    public static function errorStatusProvider(): array
    {
        return [
            'unauthorised (bad token)' => [401, ['message' => 'Not Authorized - Invalid Token']],
            'not found' => [404, ['message' => 'Not Found']],
            'rate limited' => [429, ['message' => 'Too Many Requests']],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('errorStatusProvider')]
    public function testGetLocationDetailsReturnsErrorJsonAndLogsOnHttpError(int $status, array $body): void
    {
        $api = $this->makeApi([new Response($status, ['Content-Type' => 'application/json'], json_encode($body))]);

        $response = $api->getLocationDetails('London');

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame($status, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertFalse($payload['success']);
        $this->assertSame($status, $payload['code']);
        $this->assertSame($body, json_decode($payload['error'], true));

        $this->assertTrue($this->logHandler->hasErrorThatContains('Unable to get location details for London'));
    }

    public function testGetLocationDetailsReturnsNullWhenBodyIsNotJson(): void
    {
        $api = $this->makeApi([new Response(200, [], '')]);

        $this->assertNull($api->getLocationDetails('London'));
    }

    public function testGetLocationDetailsLetsConnectionFailuresPropagate(): void
    {
        // Pins current behaviour: in Guzzle 7 ConnectException is not a RequestException, so
        // ClassKit's ConnectionController::makeRequest() doesn't catch it. Update when ClassKit does.
        $api = $this->makeApi([
            new ConnectException('Could not resolve host', new Request('GET', 'https://api.mapbox.com')),
        ]);

        $this->expectException(ConnectException::class);

        $api->getLocationDetails('London');
    }

    public function testGetLocationDetailsServesRepeatLookupsFromCache(): void
    {
        $api = $this->makeApi([$this->geocodeResponse([$this->feature(0.95)])]);

        $first = $api->getLocationDetails('London');
        $second = $api->getLocationDetails('London');

        $this->assertCount(1, $this->history);
        $this->assertInstanceOf(JsonResponse::class, $second);
        $this->assertSame($first->getContent(), $second->getContent());
    }

    public function testGetLocationDetailsCachesNoConfidentMatch(): void
    {
        $api = $this->makeApi([$this->geocodeResponse([$this->feature(0.5)])]);

        $this->assertNull($api->getLocationDetails('Somewhere vague'));
        $this->assertNull($api->getLocationDetails('Somewhere vague'));
        $this->assertCount(1, $this->history);
    }

    public function testGetLocationDetailsDoesNotCacheHttpErrors(): void
    {
        $api = $this->makeApi([
            new Response(500, [], 'Server Error'),
            $this->geocodeResponse([$this->feature(0.95)]),
        ]);

        $this->assertSame(500, $api->getLocationDetails('London')->getStatusCode());
        $this->assertSame(200, $api->getLocationDetails('London')->getStatusCode());
        $this->assertCount(2, $this->history);
    }

    public function testGetLocationDetailsSharesCacheAcrossCaseAndWhitespace(): void
    {
        $api = $this->makeApi([$this->geocodeResponse([$this->feature(0.95, 'St Albans')])]);

        $api->getLocationDetails('St Albans');
        $second = $api->getLocationDetails("  st \t ALBANS ");

        $this->assertCount(1, $this->history);
        $this->assertInstanceOf(JsonResponse::class, $second);
    }

    public function testGetLocationDetailsCachesEachLocationSeparately(): void
    {
        $api = $this->makeApi([
            $this->geocodeResponse([$this->feature(0.95)]),
            $this->geocodeResponse([$this->feature(0.95, 'Leeds')]),
        ]);

        $api->getLocationDetails('London');
        $leeds = $api->getLocationDetails('Leeds');

        $this->assertCount(2, $this->history);
        $this->assertStringStartsWith('Leeds,', json_decode($leeds->getContent(), true)['place_name']);
    }
}
