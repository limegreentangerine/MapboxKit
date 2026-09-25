# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A ConcreteCMS 9.5+ package (`type: concrete5-package`, handle `mapbox`, PHP 8.4) that provides a Mapbox GL block plus a dashboard page for storing the Mapbox API key. It is built on LGT's private `limegreentangerine/class_kit` package (installed from `packages.limegreentangerine.net`, which needs Cloudflare Access headers), and declares `class_kit` as a required ConcreteCMS package dependency.

This repo is only the package; it runs inside a ConcreteCMS site. `vendor/concrete5/core` is a dev dependency for static analysis and tests only.

## Commands

All run from the package root (scope Composer/npm here; never run them unscoped from the parent `Composer-C5-Packages` directory).

```bash
composer install              # also runs `npm install` (post-install hook, for prettier)
composer test                 # PHPUnit 11, tests/ (currently empty)
vendor/bin/phpunit --filter SomeTest   # single test (prefix with php -d error_reporting="E_ALL & ~E_DEPRECATED" to match `composer test`)
composer check                # PHPStan level 4; always exits 0 (`|| true`), so read the output
composer format               # php-cs-fixer fix + prettier --write
composer format:check         # what CI runs
```

CI (`.github/workflows/`): `CodeStandards.yml` runs `format:check` + `check`; `SystemTests.yml` runs `composer test`. Both need the `CF_ACCESS_CLIENT_ID` / `CF_ACCESS_CLIENT_SECRET` repo secrets (the README names them differently and describes workflows that don't exist; the YAML is authoritative).

When adding new top-level PHP folders, add them to the `in([...])` list in `.php-cs-fixer.dist.php` and to `paths` in `phpstan.neon`. Currently php-cs-fixer covers `blocks`, `controllers`, `src`, `tests`, `controller.php`; PHPStan only covers `src` and `tests`. `phpstan-bootstrap.php` stubs `Core`, `Events`, and `File` facades.

## Code style

- PHP: PSR-12 + PER-CS with house rules: imports sorted **by length**, `ordered_class_elements` puts private/protected methods **before** public ones, long `<?php echo` tags (no `<?=`), vertically aligned phpdoc.
- JS/CSS: Prettier with tabs (width 4), single quotes, no trailing commas, 100 cols.

## Architecture

Two namespaces coexist, following ConcreteCMS conventions:

- `Concrete\Package\Mapbox\...` — ConcreteCMS-discovered classes resolved by directory convention: `controller.php` (package), `blocks/mapbox/controller.php`, `controllers/single_pages/dashboard/mapbox.php`.
- `Mapbox\...` → `src/` — the package's own classes (autoloaded via both `composer.json` PSR-4 and `$pkgAutoloaderRegistries`).

**Package controller** (`controller.php`) extends `ClassKit\Package\PackageController` and uses ClassKit's `BlockTrait`/`PageTrait`. `installOrUpgrade()` calls `autoInstallBlocks()` (installs everything under `blocks/`) and `addSinglePage('/dashboard/mapbox', ...)`. Routes and events go in `registerRoutes()` / `registerEvents()`; the uninstall confirmation UI is `elements/dashboard/uninstall.php`.

**API key flow** — the key is stored in the package _file config_ (`$pkg->getFileConfig()`, key `mapbox.apiKey`), not the database:

1. Dashboard single page (`controllers/single_pages/dashboard/mapbox.php` + `single_pages/dashboard/mapbox.php`) validates the CSRF token and saves it.
2. `registerRoutes()` exposes `GET /ajax/mapbox` → `Mapbox\Ajax\Mapbox::getApiKey()` returning `{apiKey}`.
3. The block's `view.js` fetches `/ajax/mapbox` on `DOMContentLoaded` and sets `mapboxgl.accessToken`. Because this is public, the key must be a public (`pk.`) Mapbox token.

**Mapbox block** (`blocks/mapbox/`): tables in `db.xml` — `btMapbox` (one row per block: centre, zoom, pitch, style URL, controls, 3D buildings, extrusion colour) and `btMapboxMarkers` (repeatable markers keyed by `bID`, hand-managed with raw SQL in `save()`/`duplicate()`/`delete()`). `view()` serialises settings + markers to JSON into a `data-config` attribute on `.block__lgt-mapbox`; `view.js` initialises one `mapboxgl.Map` per element. Mapbox GL JS/CSS (pinned v3.21.0) is loaded from the CDN in `on_start()`. The map isn't rendered in edit mode. Block output is cached (`btCacheBlockOutput`).

**Server-side API** (`Mapbox\Api\Mapbox`) extends `ClassKit\Api\ConnectionController` against `https://api.mapbox.com`, passing the key as an `access_token` query param. `getLocationDetails()` geocodes (restricted to `country=gb`, relevance > 0.7) and returns a JSON response. Errors are logged via `Mapbox\Log\MapboxLogger` (a ClassKit `Logger` channel named `mapbox`).

## Known inconsistencies (left over from the package template — confirm with the user before fixing)

- User-facing strings in `view.js` / `Ajax\Mapbox` still refer to the "LGT Toolkit" dashboard.
