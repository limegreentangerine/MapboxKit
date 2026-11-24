# Mapbox for Concrete CMS

[![SystemTests](https://github.com/limegreentangerine/MapboxKit/actions/workflows/SystemTests.yml/badge.svg)](https://github.com/limegreentangerine/MapboxKit/actions/workflows/SystemTests.yml)

[![CodeStandards](https://github.com/limegreentangerine/MapboxKit/actions/workflows/CodeStandards.yml/badge.svg)](https://github.com/limegreentangerine/MapboxKit/actions/workflows/CodeStandards.yml)

Mapbox is a Concrete CMS package that adds an editable Mapbox GL JS map block and a PHP helper for looking up locations (UK-only by default) with the Mapbox Geocoding API.

## Requirements

- Concrete CMS 9.5 or later
- PHP 8.4 or later
- The `class_kit` Concrete CMS package (required by this package)
- A Mapbox public access token

The map block loads Mapbox GL JS from Mapbox's CDN (3.26.0 unless `MAPBOX_GL_JS_VERSION` is set).

## Installation

Install the package's Composer dependencies, including `limegreentangerine/class_kit`, using the Composer repository configured for your project. Install the package in Concrete CMS using your site's normal package deployment process. Its Concrete package handle is `mapbox`.

After installation, add a Mapbox public access token to the site's `.env` file (see [Configuration](#configuration)). You can create tokens in your [Mapbox account](https://account.mapbox.com/). Use a public (`pk.`) token intended for browser use; the map block exposes the token to site visitors. Apply appropriate URL restrictions and scopes in Mapbox.

## Configuration

All settings are read from the site's `.env` file. **Dashboard > Mapbox** is read-only: it shows the current value of each setting (the API key is masked) and cannot change them.

| Variable                | Default         | Purpose                                                                                                                                                                          |
| ----------------------- | --------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `MAPBOX_API_KEY`        | none (required) | The Mapbox public (`pk.`) access token used by the map block and the geocoding helper.                                                                                           |
| `MAPBOX_GL_JS_VERSION`  | `3.26.0`        | The Mapbox GL JS version the map block loads, e.g. `3.26.0` (a leading `v` is accepted). An invalid value falls back to the default. Match Mapbox Studio styles to this version. |
| `MAPBOX_GLOBAL_GEOCODE` | `false`         | Set to `true` (or `1`, `yes`, `on`) to let the geocoding helper search worldwide instead of only in the UK.                                                                      |

```dotenv
MAPBOX_API_KEY=pk.your-token
MAPBOX_GL_JS_VERSION=3.26.0
MAPBOX_GLOBAL_GEOCODE=false
```

The browser fetches the key from `/ajax/mapbox`, which is publicly cached for up to an hour, so a changed key can take that long to reach visitors.

## Map block

Add the **Mapbox** block to a page and configure:

- Centre latitude and longitude, zoom, and pitch
- A Mapbox style URL or style identifier (custom styles can be created in [Mapbox Studio](https://studio.mapbox.com/))
- Whether visitors can interact with the map
- Whether to show navigation controls and where to place them
- Whether to show 3D buildings and their extrusion colour
- Any number of markers, each with a latitude, longitude, and optional colour

The block obtains the configured token from `/ajax/mapbox`, then renders the map in the browser. A missing token is reported in the browser console and displayed on the map in the page.

## PHP geocoding helper

The `MapboxKit\Api\Mapbox` class provides `getLocationDetails(string $location)`. In a Concrete CMS context, resolve it through the application container:

```php
$mapbox = \Core::make(\MapboxKit\Api\Mapbox::class);
$result = $mapbox->getLocationDetails('London');
```

By default the helper queries the Mapbox Geocoding API with the country restricted to `gb`. Set `MAPBOX_GLOBAL_GEOCODE=true` to search worldwide instead. Cached results are keyed by location only, so after changing this setting, previously looked-up locations may return their old result for up to an hour. The helper returns a JSON response for the first feature when its relevance is greater than `0.7`, or `null` when there is no confident match. An API error is returned as a JSON response with the upstream status code. Successful features and no-match results are cached for one hour by default.

## Development

Install the development dependencies, then run:

```bash
composer test
composer check
```

`composer test` runs the PHPUnit suite. `composer check` runs PHPStan and prints findings; the configured Composer script does not fail when PHPStan reports findings.

## License

This package is proprietary. See [LICENSE](LICENSE) for the terms.
