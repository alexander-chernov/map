<?php

require_once __DIR__ . '/config.php';

use Map\Map\MapViewOptions;

if (!$app->adminGate()->allow()) {
    http_response_code(403);
    exit('Forbidden');
}

echo $app->renderMap($_GET, MapViewOptions::admin());
