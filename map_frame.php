<?php

require_once __DIR__ . '/config.php';

use Map\Map\MapViewOptions;

echo $app->renderMap($_GET, MapViewOptions::public());
