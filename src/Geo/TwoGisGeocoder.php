<?php

namespace Map\Geo;

use Map\Domain\GeoPoint;
use Map\Port\Geocoder;
use Map\Port\HttpClient;

final class TwoGisGeocoder implements Geocoder
{
    public function __construct(
        private HttpClient $http,
        private string $endpoint,
    ) {
    }

    public function geocode(string $query): ?GeoPoint
    {
        $query = trim($query);
        if ($query === '' || $this->endpoint === '') {
            return null;
        }
        $body = $this->http->get($this->endpoint . rawurlencode($query));
        if ($body === null) {
            return null;
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return null;
        }
        $item = $data['result']['items'][0]
            ?? $data['response']['items'][0]
            ?? null;
        if (!is_array($item) && isset($data['result'][0]) && is_array($data['result'][0])) {
            $item = $data['result'][0];
        }
        if (!is_array($item)) {
            return null;
        }
        $lat = $item['lat'] ?? $item['centroid']['lat'] ?? $item['point']['lat'] ?? null;
        $lon = $item['lon'] ?? $item['centroid']['lon'] ?? $item['point']['lon'] ?? null;
        if ($lat !== null && $lon !== null) {
            return new GeoPoint((float) $lat, (float) $lon);
        }
        return $this->pointFromCentroid($item['centroid'] ?? null);
    }

    private function pointFromCentroid(mixed $centroid): ?GeoPoint
    {
        if (!is_string($centroid)) {
            return null;
        }
        if (preg_match('/POINT\s*\(\s*([-\d.]+)\s+([-\d.]+)\s*\)/i', $centroid, $m)) {
            return new GeoPoint((float) $m[2], (float) $m[1]);
        }
        $parts = preg_split('/[\s,]+/', trim($centroid)) ?: [];
        if (count($parts) < 2) {
            return null;
        }
        return new GeoPoint((float) $parts[1], (float) $parts[0]);
    }
}
