<?php

namespace MapboxKit\Tests;

use Stash\Pool;
use Monolog\Logger;
use Stash\Driver\Ephemeral;
use Monolog\Handler\TestHandler;
use Concrete\Core\Utility\Service\Url;
use Concrete\Core\Logging\LoggerFactory;
use Concrete\Core\Support\Facade\Facade;
use Concrete\Core\Package\PackageService;
use Concrete\Core\Application\Application;
use Concrete\Core\Cache\Level\ExpensiveCache;
use Concrete\Core\Config\Repository\Repository;
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
     * Makes `Package::getByHandle('mapbox')->getFileConfig()->get('mapbox.apiKey')` return $apiKey.
     */
    protected function bindApiKey(?string $apiKey): void
    {
        $config = $this->createMock(Repository::class);
        $config->method('get')->willReturnCallback(
            fn(string $key, $default = null) => $key === 'mapbox.apiKey' ? $apiKey : $default,
        );

        // Stands in for both the package entity (getController()) and the package controller (getFileConfig()).
        $package = new class($config) {
            public function __construct(private Repository $config) {}

            public function getController(): self
            {
                return $this;
            }

            public function getFileConfig(): Repository
            {
                return $this->config;
            }
        };

        $packageService = $this->createMock(PackageService::class);
        $packageService->method('getByHandle')->willReturnMap([['mapbox', $package]]);
        $this->app->instance(PackageService::class, $packageService);
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
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }
}
