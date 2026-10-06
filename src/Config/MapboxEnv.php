<?php

namespace MapboxKit\Config;

/**
 * The package's settings. They live in the site's .env file, not the database or file config.
 */
class MapboxEnv
{
    public const API_KEY = 'MAPBOX_API_KEY';

    /**
     * The Mapbox API key from the environment, or null when it is unset or blank.
     */
    public static function apiKey(): ?string
    {
        $value = $_ENV[self::API_KEY] ?? $_SERVER[self::API_KEY] ?? getenv(self::API_KEY);

        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
