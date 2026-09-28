# Mapbox for Concrete CMS

[![SystemTests](https://github.com/limegreentangerine/MapboxKit/actions/workflows/SystemTests.yml/badge.svg)](https://github.com/limegreentangerine/MapboxKit/actions/workflows/SystemTests.yml)

[![CodeStandards](https://github.com/limegreentangerine/MapboxKit/actions/workflows/CodeStandards.yml/badge.svg)](https://github.com/limegreentangerine/MapboxKit/actions/workflows/CodeStandards.yml)

Mapbox is a Concrete CMS package that adds an editable Mapbox GL JS map block and a PHP helper for looking up UK locations with the Mapbox Geocoding API.

## Requirements

- Concrete CMS 9.5 or later
- PHP 8.4 or later
- The `class_kit` Concrete CMS package (required by this package)
- A Mapbox public access token

The map block loads Mapbox GL JS 3.21.0 from Mapbox's CDN.

## Installation

Install the package's Composer dependencies, including `limegreentangerine/class_kit`, using the Composer repository configured for your project. Install the package in Concrete CMS using your site's normal package deployment process. Its Concrete package handle is `mapbox`.

After installation, open **Dashboard > Mapbox**, enter a Mapbox public access token, and save the settings. You can create tokens in your [Mapbox account](https://account.mapbox.com/). Use a public (`pk.`) token intended for browser use; the map block exposes the token to site visitors. Apply appropriate URL restrictions and scopes in Mapbox.

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

The `Mapbox\Api\Mapbox` class provides `getLocationDetails(string $location)`. In a Concrete CMS context, resolve it through the application container:

```php
$mapbox = \Core::make(\Mapbox\Api\Mapbox::class);
$result = $mapbox->getLocationDetails('London');
```

The helper queries the Mapbox Geocoding API with the country restricted to `gb`. It returns a JSON response for the first feature when its relevance is greater than `0.7`, or `null` when there is no confident match. An API error is returned as a JSON response with the upstream status code. Successful features and no-match results are cached for one hour by default.

## Development

Install the development dependencies, then run:

```bash
composer test
composer check
```

`composer test` runs the PHPUnit suite. `composer check` runs PHPStan and prints findings; the configured Composer script does not fail when PHPStan reports findings.

## License

This package is proprietary. See [LICENSE](LICENSE) for the terms.
