<?php

namespace Map\Port;

use Map\Domain\GeoPoint;

interface Geocoder
{
    public function geocode(string $query): ?GeoPoint;
}
