<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Alexander A. Chernov
 * Date: 20.10.13
 * Time: 18:44
 * To change this template use File | Settings | File Templates.
 */
function mb_str_replace($needle, $replacement, $haystack)
{
    $needle_len = mb_strlen($needle);
    $replacement_len = mb_strlen($replacement);
    $pos = mb_strpos($haystack, $needle);
    while ($pos !== false)
    {
        $haystack = mb_substr($haystack, 0, $pos) . $replacement
                . mb_substr($haystack, $pos + $needle_len);
        $pos = mb_strpos($haystack, $needle, $pos + $replacement_len);
    }
    return $haystack;
}
function merc_x($lon) {
	$r_major = 6378137.000;
	return $r_major * deg2rad($lon);
}

function merc_y($lat) {
	if ($lat > 89.5) $lat = 89.5;
	if ($lat < -89.5) $lat = -89.5;
	$r_major = 6378137.000;
    $r_minor = 6356752.3142;
    $temp = $r_minor / $r_major;
	$es = 1.0 - ($temp * $temp);
    $eccent = sqrt($es);
    $phi = deg2rad($lat);
    $sinphi = sin($phi);
    $con = $eccent * $sinphi;
    $com = 0.5 * $eccent;
	$con = pow((1.0-$con)/(1.0+$con), $com);
	$ts = tan(0.5 * ((M_PI*0.5) - $phi))/$con;
    $y = - $r_major * log($ts);
    return $y;
}
function merc($x,$y) {
    return array('x'=>merc_x($x),'y'=>merc_y($y));
}
function mercatorToGeo ($x,$y) {
    $Rn = 6378137; // Экваториальный радиус
    $e = 0.0818191908426; // Эксцентриситет
    // Предвычисленные коэффициенты согласно WGS84
    $ab = 0.00335655146887969400;
    $bb = 0.00000657187271079536;
    $cb = 0.00000001764564338702;
    $db = 0.00000000005328478445;
    $xphi = (M_PI/2) - (2 * atan(1 / exp($y/$Rn)));
    $latitude = $xphi + $ab * sin(2 * $xphi) + $bb * sin(4 * $xphi) + $cb * sin(6 * $xphi) + $db * sin(8 * $xphi);
    $longitude = $x/$Rn;
    $lon = $longitude * 180 / M_PI;
    $lat = $latitude * 180 / M_PI;
    $res = array ($lat,$lon);
    return $res;
}
function lon2x($lon) {
    return deg2rad($lon) * 6378137.0;
}
function lat2y($lat) {
    return log(tan(M_PI_4 + deg2rad($lat) / 2.0)) * 6378137.0;
}
function x2lon($x) {
    return rad2deg($x / 6378137.0);
}
function y2lat($y) {
    return rad2deg(2.0 * atan(exp($y / 6378137.0)) - M_PI_2);
}
function strip_string($q){
    $items = array();
    if (strpos($q," ")) {
        preg_match_all ('/[а-яА-Яa-zA-Z0-9]+/u', $q, $items, PREG_PATTERN_ORDER);
        foreach ($items[0] as $item) {
            $res[] = $item;
        }
        $items = $res;
    } else {
        preg_match_all ('/[а-яА-Яa-zA-Z0-9]+/u', $q, $res, PREG_PATTERN_ORDER);
        $items = $res[0];
    }
    return $items;
}
function cartesian($input) {
    $result = array();
    while (list($key, $values) = each($input)) {
        if (empty($values)) {
            continue;
        }
        if (empty($result)) {
            foreach($values as $value) {
                $result[] = array($key => $value);
            }
        }
        else {
            $append = array();
            foreach($result as &$product) {
                $product[$key] = array_shift($values);
                $copy = $product;
                foreach($values as $item) {
                    $copy[$key] = $item;
                    $append[] = $copy;
                }
                array_unshift($values, $product[$key]);
            }
            $result = array_merge($result, $append);
        }
    }
    return $result;
}
