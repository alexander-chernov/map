<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 14.09.13
 * Time: 13:45
 * To change this template use File | Settings | File Templates.
 */
$time = microtime();
$time = explode(' ', $time);
$time = $time[1] + $time[0];
$start = $time;
mb_internal_encoding("UTF-8");
define('YANDEX_GEO_LINK',"https://geocode-maps.yandex.ru/1.x/?format=json&geocode=");
define('YANDEX_GEO_PEOPLEMAP_LINK',"https://psearch-maps.yandex.ru/1.x/?format=json&text=");
define('OSM_GEO_LINK',"https://nominatim.openstreetmap.org/search?format=json&q=");
define('YANDEX_NOT_ACTION','0');
define('YANDEX_FOUND_CORRECT','1');
define('YANDEX_FOUND_NOT_CORRECT','2');
define('YANDEX_FOUND_FROM_OSM_NOT_CORRECT','3');
define('YANDEX_NOT_FOUND','4');
$hash = base64_encode(md5(md5('TriPorosenka')));
define('DOUBLEGIS_API_KEY',$hash);
$url = 'https://catalog.api.2gis.ru/geo/search?version=1.3&key='.$hash.'&q=';
define('DOUBLEGIS_GEO_LINK',$url);
define('_SERVER_ADDRESS', $_SERVER['HTTP_HOST'] ?? 'localhost');
//for people map
define('YANDEX_API_KEY','API_KEY');
$perPage = 100;
$perPageAjax = 5;
$perPageMap = 100;
$default_locality = 'Томск';
define('MAP_ALLOW_ADMIN', true);
define('MAP_ADMIN_TOKEN', '');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
$mysqli = new mysqli('localhost', 'DBUSER', 'DBPASSWORD','DBNAME');
if ($mysqli->connect_errno) {
    echo "Не удалось подключиться к MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
}
require_once __DIR__ . '/src/Autoload.php';
Map\Autoload::register();
require_once __DIR__ . '/functions.php';
$sql = 'set names utf8';
$res = $mysqli->query($sql);
$app = Map\App::create($mysqli, $perPage, $perPageAjax, $default_locality, _SERVER_ADDRESS, $perPageMap);

