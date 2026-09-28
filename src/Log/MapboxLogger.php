<?php

namespace Mapbox\Log;

use ClassKit\Log\Logger;

class MapboxLogger extends Logger
{
    public function __construct()
    {
        parent::__construct('mapbox');
    }
}
