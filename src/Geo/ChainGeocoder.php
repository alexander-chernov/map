<?php

namespace Map\Geo;

use Map\Domain\GeoPoint;
use Map\Port\Geocoder;

final class ChainGeocoder implements Geocoder
{
    /** @param list<Geocoder> $geocoders */
    public function __construct(private array $geocoders)
    {
    }

    public function geocode(string $query): ?GeoPoint
    {
        foreach ($this->geocoders as $geocoder) {
            $point = $geocoder->geocode($query);
            if ($point !== null && $point->lat !== null && $point->lon !== null) {
                return $point;
            }
        }
        return null;
    }
}
