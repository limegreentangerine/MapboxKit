<?php

namespace Mapbox\Api;

use Core;
use Mapbox\Log\MapboxLogger;
use Concrete\Core\Package\Package;
use ClassKit\Api\Enum\RequestMethod;
use ClassKit\Api\ConnectionController;
use Concrete\Core\Http\ResponseFactory;

class Mapbox extends ConnectionController
{
    /**
     * @var string
     */
    protected $apiKey;
    /**
     * @var array
     */
    protected $authHeader;
    /**
     * @var \Concrete\Core\Entity\Package
     */
    protected $pkg;
    /**
     * @var object
     */
    protected $config;
    /**
     * @var ResponseFactory
     */
    protected $rf;
    /**
     * @var MapboxLogger
     */
    protected $logger;

    /**
     * Api constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->pkg = Package::getByHandle('mapbox');
        $this->rf = Core::make(\Concrete\Core\Http\ResponseFactoryInterface::class);
        $this->config = $this->pkg->getController()->getFileConfig();
        $this->logger = Core::make(MapboxLogger::class)->getLogger();
        $this->setApiKey($this->config->get('mapbox.apiKey'));
        $this->setAuthHeader($this->getApiKey());
        parent::__construct(
            'https://api.mapbox.com',
            'json',
            $this->getAuthHeader(),
        );
    }

    /**
     * Formats the URL for a given endpoint, adding the Authorization header.
     *
     * @param  string $endpoint
     * @return string
     */
    protected function formatURL(string $endpoint = ''): string
    {
        $uh = \Core::make('helper/url');
        return $uh->buildQuery($endpoint, $this->getAuthHeader());
    }

    /**
     * Sets the Authorization header for the API
     *
     * @param string|null $apiKey
     */
    protected function setAuthHeader(?string $apiKey)
    {
        $this->authHeader = ['access_token' => $apiKey];

        return $this;
    }

    /**
     * Returns the Authorization header for the API
     *
     * @return array
     */
    protected function getAuthHeader()
    {
        return $this->authHeader;
    }

    /**
     * Retrieves the coordinates for a given location.
     *
     * @param  string                                          $location The location to retrieve coordinates for.
     * @return \Symfony\Component\HttpFoundation\Response|null JSON of the matched feature, a JSON error response if the request failed, or null if no confident match was found.
     */
    public function getLocationDetails(string $location = '')
    {
        $request = (object) $this->makeRequest(RequestMethod::GET->value, $this->formatURL(sprintf('/geocoding/v5/mapbox.places/%s.json', rawurlencode($location))) . '&country=gb');
        if ($request->getStatusCode() !== 200) {
            $this->logger->addError(sprintf('Unable to get location details for %s: [%d]. Details. %s. %s %s (%s)', $location, $request->getStatusCode(), $request->getBody(), __FUNCTION__, __CLASS__, __LINE__));
            return $this->rf->json([
                'success' => false,
                'error' => $request->getBody(),
                'code' => $request->getStatusCode(),
            ], $request->getStatusCode());
        }
        $body = json_decode($request->getBody());

        if (is_object($body)
            && property_exists($body, 'features') && count($body->features) > 0 && $body->features[0]->relevance > 0.7) {
            return $this->rf->json($body->features[0]);
        }

        return null;

    }

    /**
     * Get the value of apiKey
     *
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * Set the value of apiKey
     *
     * @param string $apiKey
     *
     * @return self
     */
    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }
}
