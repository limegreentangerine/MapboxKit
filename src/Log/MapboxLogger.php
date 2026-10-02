<?php

namespace MapboxKit\Log;

use ClassKit\Log\Logger;

class MapboxLogger extends Logger
{
    public function __construct()
    {
        parent::__construct('mapbox');
    }
}
