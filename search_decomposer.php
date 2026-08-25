<?php

require_once __DIR__ . '/src/Autoload.php';
Map\Autoload::register();
Map\Infra\CliGuard::assert();

require_once __DIR__ . '/config.php';

$argv = $argv ?? [];
$geocode = in_array('--geocode', $argv, true);
$updateCoords = !in_array('--no-coords', $argv, true);

$stats = $app->indexBuilder($geocode)->rebuild($updateCoords, $geocode);
echo $stats->summary();
