<?php

namespace Mapbox\Ajax;

use Concrete\Core\Package\Package;
use Symfony\Component\HttpFoundation\JsonResponse;

class Mapbox
{
    /**
     * How long (in seconds) browsers and shared caches may reuse the API key response.
     * A changed key in the dashboard can take up to this long to reach visitors.
     */
    public const CACHE_MAX_AGE = 3600;

    /**
     * Returns the Mapbox API key from the Mapbox package configuration.
     *
     * @return JsonResponse containing the API key
     */
    public function getApiKey(): JsonResponse
    {
        $pkg = Package::getByHandle('mapbox');
        $config = $pkg->getController()->getFileConfig();
        $apiKey = $config->get('mapbox.apiKey');

        $response = new JsonResponse([ 'apiKey' => $apiKey ]);

        if ($apiKey) {
            // The key is a public (pk.) token and identical for every visitor, so shared caches may store it
            $response->setPublic();
            $response->setMaxAge(self::CACHE_MAX_AGE);
        } else {
            // Never cache the "missing key" state, so a newly saved key takes effect immediately
            $response->headers->addCacheControlDirective('no-store');
        }

        return $response;
    }
}
