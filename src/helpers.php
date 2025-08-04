<?php

use Destefano\Guachiman\Logger\ActivityLogger;

if (! function_exists('activity')) {
    function activity(): ActivityLogger
    {
        return app(ActivityLogger::class);
    }
}
