<?php

namespace MapboxKit\Config;

/**
 * The package's settings. They live in the site's .env file, not the database or file config.
 */
class MapboxEnv
{
    public const API_KEY = 'MAPBOX_API_KEY';
    public const GL_VERSION = 'MAPBOX_GL_JS_VERSION';

    /**
     * Used when MAPBOX_GL_JS_VERSION is unset or is not a valid version.
     */
    public const DEFAULT_GL_VERSION = '3.26.0';

    private static function get(string $name): ?string
    {
        $value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * The Mapbox API key from the environment, or null when it is unset or blank.
     */
    public static function apiKey(): ?string
    {
        return self::get(self::API_KEY);
    }

    /**
     * The Mapbox GL JS version (e.g. 3.26.0) from the environment, or null when it is unset or not a valid version.
     * A leading "v" is accepted and stripped. The value ends up in a URL, so anything else is rejected.
     */
    public static function glVersion(): ?string
    {
        $value = self::get(self::GL_VERSION);

        if ($value === null || !preg_match('/^v?(\d+\.\d+\.\d+(?:-[0-9A-Za-z.]+)?)$/', $value, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * The Mapbox GL JS version to load: the configured one, or the default.
     */
    public static function glVersionOrDefault(): string
    {
        return self::glVersion() ?? self::DEFAULT_GL_VERSION;
    }
}
