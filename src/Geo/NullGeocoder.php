<?php

namespace Map\Geo;

use Map\Domain\GeoPoint;
use Map\Port\Geocoder;

final class NullGeocoder implements Geocoder
{
    public function geocode(string $query): ?GeoPoint
    {
        return null;
    }
}
