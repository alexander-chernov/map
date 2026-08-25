<?php

namespace Map\Geo;

use Map\Domain\GeoPoint;
use Map\Port\Geocoder;
use Map\Port\HttpClient;

final class YandexGeocoder implements Geocoder
{
    public function __construct(
        private HttpClient $http,
        private string $endpoint,
        private string $apiKey = '',
    ) {
    }

    public function geocode(string $query): ?GeoPoint
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }
        $url = $this->endpoint . rawurlencode($query);
        if ($this->apiKey !== '' && strcasecmp($this->apiKey, 'API_KEY') !== 0) {
            $url .= '&apikey=' . rawurlencode($this->apiKey);
        }
        $body = $this->http->get($url);
        if ($body === null) {
            return null;
        }
        $data = json_decode($body, true);
        $pos = $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'] ?? null;
        if (!is_string($pos) || $pos === '') {
            return null;
        }
        $parts = preg_split('/\s+/', trim($pos)) ?: [];
        if (count($parts) < 2) {
            return null;
        }
        return new GeoPoint((float) $parts[1], (float) $parts[0]);
    }
}
