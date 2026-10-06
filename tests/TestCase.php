<?php

namespace MapboxKit\Tests;

use Stash\Pool;
use Monolog\Logger;
use Stash\Driver\Ephemeral;
use MapboxKit\Config\MapboxEnv;
use Monolog\Handler\TestHandler;
use Concrete\Core\Utility\Service\Url;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Support\Facade\Facade;
use Concrete\Core\Application\Application;
use Concrete\Core\Cache\Level\ExpensiveCache;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Concrete\Core\Http\ResponseFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Boots a bare ConcreteCMS container behind the facades so package classes
 * that use `Core::make()` / `Package::getByHandle()` can be unit tested.
 */
abstract class TestCase extends BaseTestCase
{
    protected Application $app;

    protected TestHandler $logHandler;

    /**
     * In-memory stand-in for the ExpensiveCache pool, fresh for each test.
     */
    protected Pool $cachePool;

    /**
     * Makes the MAPBOX_API_KEY environment variable return $apiKey (unset when null).
     */
    protected function bindApiKey(?string $apiKey): void
    {
        if ($apiKey === null) {
            unset($_ENV[MapboxEnv::API_KEY]);
        } else {
            $_ENV[MapboxEnv::API_KEY] = $apiKey;
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application();
        $this->app->instance('app', $this->app);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication($this->app);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $responseFactory->method('json')->willReturnCallback(
            fn($data, $code = 200, array $headers = []) => new JsonResponse($data, $code, $headers),
        );
        $this->app->instance(ResponseFactoryInterface::class, $responseFactory);

        $this->app->instance('helper/url', new Url());

        $this->logHandler = new TestHandler();
        $loggerFactory = $this->createMock(LoggerFactory::class);
        $loggerFactory->method('createLogger')->willReturnCallback(
            fn(string $channel) => new Logger($channel, [$this->logHandler]),
        );
        $this->app->instance(LoggerFactory::class, $loggerFactory);

        // A mock skips ExpensiveCache::init(), which reads config through the Config facade
        $this->cachePool = new Pool(new Ephemeral());
        $expensiveCache = $this->createMock(ExpensiveCache::class);
        $expensiveCache->method('getPool')->willReturn($this->cachePool);
        $this->app->instance(ExpensiveCache::class, $expensiveCache);
    }

    protected function tearDown(): void
    {
        unset($_ENV[MapboxEnv::API_KEY]);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }
}
