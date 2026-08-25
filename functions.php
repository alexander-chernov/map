<?php

use Map\Domain\GeoProjection;
use Map\Domain\QueryTokenizer;

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

function map_projection(): GeoProjection
{
    static $projection;
    return $projection ??= new GeoProjection();
}

function merc_x($lon) {
    return map_projection()->mercX((float) $lon);
}

function merc_y($lat) {
    return map_projection()->mercY((float) $lat);
}
function merc($x,$y) {
    return map_projection()->merc((float) $x, (float) $y);
}
function mercatorToGeo ($x,$y) {
    return map_projection()->mercatorToGeo((float) $x, (float) $y);
}
function lon2x($lon) {
    return map_projection()->lon2x((float) $lon);
}
function lat2y($lat) {
    return map_projection()->lat2y((float) $lat);
}
function x2lon($x) {
    return map_projection()->x2lon((float) $x);
}
function y2lat($y) {
    return map_projection()->y2lat((float) $y);
}
function strip_string($q){
    return (new QueryTokenizer())->split((string) $q);
}
function cartesian($input) {
    $result = array();
    foreach ($input as $key => $values) {
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
