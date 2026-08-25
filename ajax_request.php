<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode(
    $app->clickLookup()->lookup($app->clickQuery($_GET))->toArray(),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
