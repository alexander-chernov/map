<?php

namespace Map\Map;

use Map\Domain\GeoProjection;
use Map\Domain\MapPin;
use Map\Domain\MapPolyline;
use Map\Domain\MapQuery;
use Map\Domain\MapState;
use Map\Domain\QueryTokenizer;
use Map\Infra\Html;
use Map\Port\AddressRepository;
use Map\Port\MapGeometryRepository;
use Map\Port\MapPinRepository;
use Map\Port\SearchIndexRepository;

final class MapService
{
    public function __construct(
        private MapGeometryRepository $geometry,
        private MapPinRepository $pins,
        private AddressRepository $addresses,
        private SearchIndexRepository $index,
        private QueryTokenizer $tokenizer,
        private GeoProjection $projection,
        private MapBalloon $balloon,
    ) {
    }

    public function state(MapQuery $query, MapViewOptions $options): MapState
    {
        $polylines = [];
        $pins = [];
        $points = [];
        $overrideCenter = null;

        if ($query->showStreet) {
            [$polylines, $streetPins, $streetPoints] = $this->streetLayer($query);
            $pins = array_merge($pins, $streetPins);
            $points = array_merge($points, $streetPoints);
        } elseif ($query->hasAddressFilter() && !$query->search->hasPhrase()) {
            [$layerPins, $layerPoints] = $this->addressLayer($query);
            $pins = array_merge($pins, $layerPins);
            $points = array_merge($points, $layerPoints);
        } elseif ($query->search->hasPhrase()) {
            [$layerPins, $layerPoints, $overrideCenter] = $this->searchLayer($query);
            $pins = array_merge($pins, $layerPins);
            $points = array_merge($points, $layerPoints);
        }

        if (!$query->showStreet && $query->stopFid > 0) {
            [$stopPins, $stopPoints] = $this->stopPins(
                $this->geometry->stopByFid($query->stopFid),
                'twirl#blackIcon',
                true
            );
            $pins = array_merge($pins, $stopPins);
            $points = array_merge($points, $stopPoints);
        }

        $center = MapState::DEFAULT_CENTER;
        if ($query->search->point?->lat !== null && $query->search->point?->lon !== null) {
            $center = [$query->search->point->lat, $query->search->point->lon];
        } elseif ($overrideCenter !== null) {
            $center = $overrideCenter;
        } elseif ($points !== []) {
            $lats = array_column($points, 0);
            $lons = array_column($points, 1);
            $center = [
                round((min($lats) + max($lats)) / 2, 6),
                round((min($lons) + max($lons)) / 2, 6),
            ];
        }

        $hasPoint = $query->search->point !== null;
        $zoom = $options->admin
            ? 12
            : (($pins === [] || $hasPoint || $query->geocode !== null || $query->noAds)
                ? $options->defaultZoom
                : 12);

        $banners = [];
        if (!$query->noAds && $pins !== [] && !$options->admin) {
            foreach ($this->pins->banners() as $banner) {
                $href = Html::url($banner['url']);
                $img = Html::e($banner['img']);
                if ($href === '') {
                    continue;
                }
                $banners[] = [
                    'lat' => $banner['lat'],
                    'lon' => $banner['lon'],
                    'html' => '<a target=_blank href="' . $href . '"><img src="/images/banner/' . $img . '"></a>',
                ];
                $points[] = [$banner['lat'], $banner['lon']];
            }
        }

        $pointMarker = null;
        if ($pins === [] && ($hasPoint || $overrideCenter !== null)) {
            $pointMarker = $center;
        }

        return new MapState(
            center: $center,
            zoom: $zoom,
            width: $query->width,
            scrollZoom: $query->scrollZoom,
            mapType: $options->mapType,
            contextMenuHint: !$query->showStreet,
            polylines: $polylines,
            pins: $pins,
            banners: $banners,
            point: $pointMarker,
            lightbox: $options->lightbox,
        );
    }

    /**
     * @return array{0: list<MapPolyline>, 1: list<MapPin>, 2: list<array{0: float, 1: float}>}
     */
    private function streetLayer(MapQuery $query): array
    {
        $polylines = [];
        $pins = [];
        $points = [];
        $color = '#e23321';

        if ($query->geocode !== null && !empty($query->geocode->street)) {
            $tokens = $this->tokenizer->geoTokens((string) $query->geocode->street);
            foreach ($this->geometry->streetsByTokens($query->locality, $tokens) as $row) {
                $paths = $this->projection->lineWktToPaths($row['geo_obj']);
                if ($paths === []) {
                    continue;
                }
                $polylines[] = new MapPolyline(TransitLabel::format($row['name']), $color, $paths);
                foreach ($paths as $path) {
                    foreach ($path as $pt) {
                        $points[] = $pt;
                    }
                }
            }
        }

        if ($query->routeCode !== '') {
            foreach ($this->geometry->routeByCode($query->routeCode) as $row) {
                $paths = $this->projection->lineWktToPaths($row['geo_obj']);
                if ($paths === []) {
                    continue;
                }
                $polylines[] = new MapPolyline(TransitLabel::format($row['name']), $color, $paths);
                foreach ($paths as $path) {
                    foreach ($path as $pt) {
                        $points[] = $pt;
                    }
                }
            }
            [$routePins, $routePoints] = $this->stopPins(
                $this->geometry->stopsByRoute($query->routeCode),
                'twirl#redIcon',
                true
            );
            $pins = array_merge($pins, $routePins);
            $points = array_merge($points, $routePoints);
        }

        return [$polylines, $pins, $points];
    }

    /**
     * @return array{0: list<MapPin>, 1: list<array{0: float, 1: float}>}
     */
    private function addressLayer(MapQuery $query): array
    {
        $pins = [];
        $points = [];
        $rows = $this->pins->housesByScope($query->search, $query->objectId, 0, $query->perPage);
        foreach ($rows as $row) {
            $pin = $this->housePin($row, MapPin::GROUP_ADDRESS, 'twirl#greenIcon', true);
            if ($pin === null) {
                continue;
            }
            $pins[] = $pin;
            $points[] = [$pin->lat, $pin->lon];
        }

        $scope = $this->addresses->resolveScope($query->search);
        if (!$scope->isEmpty()) {
            foreach ($this->pins->orgsByScope($scope->streetIds, $scope->houseNumbers, 0, $query->perPage) as $row) {
                $pin = $this->housePin($row, MapPin::GROUP_FIRM, 'twirl#violetIcon', true);
                if ($pin === null) {
                    continue;
                }
                $pins[] = $pin;
                $points[] = [$pin->lat, $pin->lon];
            }
        }

        return [$pins, $points];
    }

    /**
     * @return array{0: list<MapPin>, 1: list<array{0: float, 1: float}>, 2: array{0: float, 1: float}|null}
     */
    private function searchLayer(MapQuery $query): array
    {
        $pins = [];
        $points = [];
        $override = null;
        $rawTokens = $this->tokenizer->split($query->search->phrase);
        $tokens = $query->search->tokens;

        if (count($rawTokens) === 1) {
            $district = $this->geometry->districtCenter($rawTokens[0]);
            if ($district !== null) {
                $override = [$district['lat'], $district['lon']];
                return [$pins, [[$district['lat'], $district['lon']]], $override];
            }
        }

        $hits = $tokens === [] ? ['orgIds' => [], 'streetIds' => []] : $this->index->findOrgHits($tokens);
        if ($hits['orgIds'] !== []) {
            foreach ($this->pins->orgsByIds($hits['orgIds'], $hits['streetIds'], 0, $query->perPage) as $row) {
                $streetId = (int) ($row['street_id'] ?? 0);
                $house = trim((string) ($row['house'] ?? ''));
                $meta = $streetId && $house !== '' ? $this->pins->houseMeta($streetId, $house) : null;
                $row['district'] = $meta['district'] ?? '';
                $row['massive'] = $meta['massive'] ?? '';
                $row['addr_count'] = $meta['addr_count'] ?? 0;
                $row['realty_sell'] = $meta['realty_sell'] ?? 0;
                $row['address_id'] = $meta['address_id'] ?? 0;
                $photos = $streetId ? $this->pins->photoUrls($streetId, $house) : [];
                $pin = $this->housePin($row, MapPin::GROUP_STREET, 'twirl#blueIcon', true, $photos);
                if ($pin === null) {
                    continue;
                }
                $pins[] = $pin;
                $points[] = [$pin->lat, $pin->lon];
            }
        } elseif ($tokens !== []) {
            foreach ($this->pins->housesByTokens($tokens, 0, $query->perPage) as $row) {
                $photos = $this->pins->photoUrls((int) ($row['street_id'] ?? 0), (string) ($row['house'] ?? ''));
                $pin = $this->housePin($row, MapPin::GROUP_SEARCH, 'twirl#brownIcon', false, $photos);
                if ($pin === null) {
                    continue;
                }
                $pins[] = $pin;
                $points[] = [$pin->lat, $pin->lon];
            }
        }

        if ($tokens !== []) {
            [$stopPins, $stopPoints] = $this->stopPins(
                $this->geometry->stopsByTokens($tokens, 0, $query->perPage),
                'twirl#redIcon',
                false
            );
            $pins = array_merge($pins, $stopPins);
            $points = array_merge($points, $stopPoints);
        }

        return [$pins, $points, $override];
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{0: list<MapPin>, 1: list<array{0: float, 1: float}>}
     */
    private function stopPins(array $rows, string $preset, bool $fromWkt): array
    {
        $pins = [];
        $points = [];
        foreach ($rows as $row) {
            if ($fromWkt) {
                $latLon = $this->projection->pointWktToLatLon($row['geo_obj'] ?? null);
            } else {
                $latLon = [
                    isset($row['centerX']) ? (float) $row['centerX'] : 0.0,
                    isset($row['centerY']) ? (float) $row['centerY'] : 0.0,
                ];
            }
            if ($latLon === null || ($latLon[0] == 0.0 && $latLon[1] == 0.0)) {
                continue;
            }
            $routes = [];
            foreach (explode(',', (string) ($row['routes'] ?? '')) as $code) {
                $code = trim($code);
                if ($code !== '') {
                    $routes[] = $code;
                }
            }
            [$header, $body] = $this->balloon->stop((string) ($row['name'] ?? ''), $routes);
            $pins[] = new MapPin(MapPin::GROUP_STOP, $preset, $latLon[0], $latLon[1], $header, $body);
            $points[] = $latLon;
        }
        return [$pins, $points];
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $photos
     */
    private function housePin(array $row, string $group, string $preset, bool $withAddressCount, array $photos = []): ?MapPin
    {
        if (empty($row['centerX']) || empty($row['centerY'])) {
            return null;
        }
        $lat = (float) $row['centerX'];
        $lon = (float) $row['centerY'];
        $streetId = (int) ($row['street_id'] ?? 0);
        $houseId = (int) ($row['address_id'] ?? 0);
        $fallback = trim((string) ($row['street'] ?? '') . ' ' . str_replace(' ', '_', trim((string) ($row['house'] ?? ''))));
        $link = $this->balloon->sideLink($lat, $lon, $streetId, $houseId, $fallback);
        [$header, $body] = $this->balloon->address($row, $link, $photos, $withAddressCount);
        return new MapPin($group, $preset, $lat, $lon, $header, $body);
    }
}
