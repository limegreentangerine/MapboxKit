<?php

namespace MapboxKit\Tests\Config;

use MapboxKit\Config\MapboxEnv;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class MapboxEnvTest extends TestCase
{
    public static function validVersionProvider(): array
    {
        return [
            'plain' => ['3.26.0', '3.26.0'],
            'leading v' => ['v3.10.1', '3.10.1'],
            'padded' => ['  3.9.4 ', '3.9.4'],
            'prerelease' => ['3.0.0-beta.1', '3.0.0-beta.1'],
        ];
    }

    #[DataProvider('validVersionProvider')]
    public function testGlVersionAcceptsValidVersions(string $value, string $expected): void
    {
        $_ENV[MapboxEnv::GL_VERSION] = $value;

        $this->assertSame($expected, MapboxEnv::glVersion());
        $this->assertSame($expected, MapboxEnv::glVersionOrDefault());
    }

    public static function invalidVersionProvider(): array
    {
        return [
            'blank' => [''],
            'latest' => ['latest'],
            'two parts' => ['3.26'],
            'path traversal' => ['3.26.0/../../evil'],
            'quote' => ['3.26.0"><script>'],
        ];
    }

    #[DataProvider('invalidVersionProvider')]
    public function testGlVersionRejectsInvalidValuesAndFallsBackToDefault(string $value): void
    {
        $_ENV[MapboxEnv::GL_VERSION] = $value;

        $this->assertNull(MapboxEnv::glVersion());
        $this->assertSame(MapboxEnv::DEFAULT_GL_VERSION, MapboxEnv::glVersionOrDefault());
    }

    public function testGlVersionIsNullWhenUnset(): void
    {
        unset($_ENV[MapboxEnv::GL_VERSION]);

        $this->assertNull(MapboxEnv::glVersion());
    }

    public function testApiKeyIsTrimmedAndNullWhenBlank(): void
    {
        $_ENV[MapboxEnv::API_KEY] = '  pk.abc ';
        $this->assertSame('pk.abc', MapboxEnv::apiKey());

        $_ENV[MapboxEnv::API_KEY] = '   ';
        $this->assertNull(MapboxEnv::apiKey());
    }
    protected function tearDown(): void
    {
        unset($_ENV[MapboxEnv::GL_VERSION], $_ENV[MapboxEnv::API_KEY]);

        parent::tearDown();
    }
}
