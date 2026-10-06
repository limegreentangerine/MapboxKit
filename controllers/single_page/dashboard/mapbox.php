<?php

namespace Concrete\Package\MapboxKit\Controller\SinglePage\Dashboard;

use MapboxKit\Config\MapboxEnv;
use Concrete\Core\Page\Controller\DashboardPageController;

/**
 * Read-only: the Mapbox settings live in the site's .env file, so this page only reports which are populated.
 */
class Mapbox extends DashboardPageController
{
    public function view()
    {
        $apiKey = MapboxEnv::apiKey();
        $glVersion = MapboxEnv::glVersion();

        $this->set('settings', [
            'apiKey' => [
                'env' => MapboxEnv::API_KEY,
                'label' => t('API Key'),
                'populated' => $apiKey !== null,
                // The key is a secret, so only report that it is set
                'display' => $apiKey !== null ? str_repeat('•', 8) : '',
                'default' => null,
            ],
            'glVersion' => [
                'env' => MapboxEnv::GL_VERSION,
                'label' => t('Mapbox GL JS Version'),
                'populated' => $glVersion !== null,
                'display' => $glVersion ?? '',
                'default' => MapboxEnv::DEFAULT_GL_VERSION,
            ],
        ]);
    }
}
