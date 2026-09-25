<?php

namespace Mapbox\Ajax;

use Concrete\Core\Package\Package;
use Symfony\Component\HttpFoundation\JsonResponse;

class Mapbox
{
    /**
     * Returns the Mapbox API key from the LGT Toolkit package configuration.
     *
     * @return JsonResponse containing the API key
     */
    public function getApiKey(): JsonResponse
    {
        $pkg = Package::getByHandle('mapbox');
        $config = $pkg->getController()->getFileConfig();
        return new JsonResponse([ 'apiKey' => $config->get('mapbox.apiKey') ]);
    }
}
