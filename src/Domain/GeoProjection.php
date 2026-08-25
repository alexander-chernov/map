<?php

namespace Map\Domain;

final class GeoProjection
{
    private const R_MAJOR = 6378137.0;
    private const R_MINOR = 6356752.3142;

    public function mercX(float $lon): float
    {
        return self::R_MAJOR * deg2rad($lon);
    }

    public function mercY(float $lat): float
    {
        if ($lat > 89.5) {
            $lat = 89.5;
        }
        if ($lat < -89.5) {
            $lat = -89.5;
        }
        $temp = self::R_MINOR / self::R_MAJOR;
        $es = 1.0 - ($temp * $temp);
        $eccent = sqrt($es);
        $phi = deg2rad($lat);
        $sinphi = sin($phi);
        $con = $eccent * $sinphi;
        $com = 0.5 * $eccent;
        $con = pow((1.0 - $con) / (1.0 + $con), $com);
        $ts = tan(0.5 * ((M_PI * 0.5) - $phi)) / $con;
        return -self::R_MAJOR * log($ts);
    }

    /** @return array{x: float, y: float} */
    public function merc(float $lon, float $lat): array
    {
        return ['x' => $this->mercX($lon), 'y' => $this->mercY($lat)];
    }

    /** @return array{0: float, 1: float} lat, lon */
    public function mercatorToGeo(float $x, float $y): array
    {
        $rn = self::R_MAJOR;
        $ab = 0.00335655146887969400;
        $bb = 0.00000657187271079536;
        $cb = 0.00000001764564338702;
        $db = 0.00000000005328478445;
        $xphi = (M_PI / 2) - (2 * atan(1 / exp($y / $rn)));
        $latitude = $xphi + $ab * sin(2 * $xphi) + $bb * sin(4 * $xphi) + $cb * sin(6 * $xphi) + $db * sin(8 * $xphi);
        $longitude = $x / $rn;
        return [$latitude * 180 / M_PI, $longitude * 180 / M_PI];
    }

    public function lon2x(float $lon): float
    {
        return deg2rad($lon) * self::R_MAJOR;
    }

    public function lat2y(float $lat): float
    {
        return log(tan(M_PI_4 + deg2rad($lat) / 2.0)) * self::R_MAJOR;
    }

    public function x2lon(float $x): float
    {
        return rad2deg($x / self::R_MAJOR);
    }

    public function y2lat(float $y): float
    {
        return rad2deg(2.0 * atan(exp($y / self::R_MAJOR)) - M_PI_2);
    }

    /** @return array{0: float, 1: float}|null lat, lon */
    public function pointWktToLatLon(?string $wkt): ?array
    {
        if ($wkt === null || $wkt === '') {
            return null;
        }
        $line = str_replace(['POINT(', ')'], '', $wkt);
        $coord = preg_split('/\s+/', trim($line)) ?: [];
        if (count($coord) < 2) {
            return null;
        }
        $x = (float) $coord[0];
        $y = (float) $coord[1];
        return [round($this->y2lat($y), 6), round($this->x2lon($x), 6)];
    }

    /**
     * WKT LINESTRING / MULTILINESTRING in Web Mercator → list of lat/lon paths.
     *
     * @return list<list<array{0: float, 1: float}>>
     */
    public function lineWktToPaths(?string $wkt): array
    {
        if ($wkt === null || trim($wkt) === '') {
            return [];
        }
        $isMulti = stripos($wkt, 'MULTILINESTRING') !== false;
        if ($isMulti) {
            $body = preg_replace('/^\s*MULTILINESTRING\s*/i', '', $wkt) ?? $wkt;
            $body = trim($body);
            $body = preg_replace('/^\(\(/', '', $body) ?? $body;
            $body = preg_replace('/\)\)$/', '', $body) ?? $body;
            $chunks = explode('),(', $body);
        } else {
            $body = preg_replace('/^\s*LINESTRING\s*/i', '', $wkt) ?? $wkt;
            $body = trim($body);
            $body = trim($body, '()');
            $chunks = [$body];
        }
        $paths = [];
        foreach ($chunks as $chunk) {
            $chunk = str_replace(['(', ')'], '', $chunk);
            $path = [];
            foreach (explode(',', $chunk) as $point) {
                $coord = preg_split('/\s+/', trim($point)) ?: [];
                if (count($coord) < 2) {
                    continue;
                }
                $path[] = [
                    round($this->y2lat((float) $coord[1]), 6),
                    round($this->x2lon((float) $coord[0]), 6),
                ];
            }
            if ($path !== []) {
                $paths[] = $path;
            }
        }
        return $paths;
    }
}
