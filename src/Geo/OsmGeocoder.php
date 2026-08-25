<?php

namespace Map\Geo;

use Map\Domain\GeoPoint;
use Map\Port\Geocoder;
use Map\Port\HttpClient;

final class OsmGeocoder implements Geocoder
{
    public function __construct(
        private HttpClient $http,
        private string $endpoint,
    ) {
    }

    public function geocode(string $query): ?GeoPoint
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }
        $url = preg_replace('/format=xml/i', 'format=json', $this->endpoint) ?? $this->endpoint;
        $body = $this->http->get($url . rawurlencode($query));
        if ($body === null) {
            return null;
        }
        $data = json_decode($body, true);
        if (!is_array($data) || $data === []) {
            return null;
        }
        $first = $data[0] ?? null;
        if (!is_array($first) || !isset($first['lat'], $first['lon'])) {
            return null;
        }
        return new GeoPoint((float) $first['lat'], (float) $first['lon']);
    }
}
