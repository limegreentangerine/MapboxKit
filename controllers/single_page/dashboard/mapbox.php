<?php

namespace Concrete\Package\MapboxKit\Controller\SinglePage\Dashboard;

use MapboxKit\Config\MapboxEnv;
use Concrete\Core\Page\Controller\DashboardPageController;

/**
 * Read-only: the Mapbox API key lives in the site's .env file, so this page only reports whether it is populated.
 */
class Mapbox extends DashboardPageController
{
    public function view()
    {
        $populated = MapboxEnv::apiKey() !== null;

        $this->set('envName', MapboxEnv::API_KEY);
        $this->set('populated', $populated);
        $this->set('display', $populated ? str_repeat('•', 8) : '');
    }
}
