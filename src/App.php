<?php

namespace Map;

use Map\Domain\ClickQuery;
use Map\Domain\GeoProjection;
use Map\Domain\MapQuery;
use Map\Domain\QueryTokenizer;
use Map\Domain\SearchQuery;
use Map\Geo\ChainGeocoder;
use Map\Geo\NullGeocoder;
use Map\Geo\OsmGeocoder;
use Map\Geo\TwoGisGeocoder;
use Map\Geo\YandexGeocoder;
use Map\Infra\AdminGate;
use Map\Infra\FileHttpClient;
use Map\Infra\Database;
use Map\Port\Geocoder;
use Map\Infra\MysqliAddressRepository;
use Map\Infra\MysqliClickLookupRepository;
use Map\Infra\MysqliMapGeometryRepository;
use Map\Infra\MysqliMapPinRepository;
use Map\Infra\MysqliOrganizationRepository;
use Map\Infra\MysqliRealtyRepository;
use Map\Infra\MysqliSearchIndexRepository;
use Map\Infra\MysqliStopRepository;
use Map\Map\ClickLookupService;
use Map\Map\MapBalloon;
use Map\Map\MapService;
use Map\Map\MapViewOptions;
use Map\Map\YandexMapView;
use Map\Search\AutocompleteService;
use Map\Search\IndexBuilder;
use Map\Search\HtmlListPresenter;
use Map\Search\SearchService;
use mysqli;

final class App
{
    public function __construct(
        private Database $db,
        private int $perPage,
        private int $perPageAjax,
        private string $locality = 'Томск',
        private string $photoHost = 'localhost',
        private int $perPageMap = 100,
    ) {
    }

    public static function create(
        mysqli $mysqli,
        int $perPage,
        int $perPageAjax,
        string $locality = 'Томск',
        string $photoHost = 'localhost',
        int $perPageMap = 100,
    ): self {
        return new self(new Database($mysqli), $perPage, $perPageAjax, $locality, $photoHost, $perPageMap);
    }

    public function tokenizer(): QueryTokenizer
    {
        return new QueryTokenizer();
    }

    public function locality(): string
    {
        return $this->locality;
    }

    public function perPageMap(): int
    {
        return $this->perPageMap;
    }

    public function search(): SearchService
    {
        $db = $this->db;
        return new SearchService(
            new MysqliOrganizationRepository($db),
            new MysqliAddressRepository($db),
            new MysqliRealtyRepository($db),
            new MysqliStopRepository($db, new GeoProjection()),
            new MysqliSearchIndexRepository($db),
        );
    }

    public function htmlList(): HtmlListPresenter
    {
        return new HtmlListPresenter();
    }

    public function autocomplete(): AutocompleteService
    {
        return new AutocompleteService(
            new MysqliSearchIndexRepository($this->db),
            new MysqliOrganizationRepository($this->db),
            $this->tokenizer(),
            $this->perPage,
        );
    }

    public function map(): MapService
    {
        $db = $this->db;
        $projection = new GeoProjection();
        return new MapService(
            new MysqliMapGeometryRepository($db),
            new MysqliMapPinRepository($db),
            new MysqliAddressRepository($db),
            new MysqliSearchIndexRepository($db),
            $this->tokenizer(),
            $projection,
            new MapBalloon($this->photoHost),
        );
    }

    public function mapView(): YandexMapView
    {
        return new YandexMapView();
    }

    public function clickLookup(): ClickLookupService
    {
        return new ClickLookupService(
            new MysqliClickLookupRepository(
                $this->db,
                $this->tokenizer(),
                new MapBalloon($this->photoHost),
            )
        );
    }

    public function searchQuery(array $get): SearchQuery
    {
        return SearchQuery::fromGet($get, $this->perPageAjax, $this->tokenizer());
    }

    public function mapQuery(array $get): MapQuery
    {
        return MapQuery::fromGet(
            $get,
            $this->perPageMap,
            $this->perPageAjax,
            $this->locality,
            $this->tokenizer(),
        );
    }

    public function clickQuery(array $get): ClickQuery
    {
        return ClickQuery::fromGet($get, $this->locality);
    }

    public function geocoder(bool $enabled = false): Geocoder
    {
        if (!$enabled) {
            return new NullGeocoder();
        }
        $http = new FileHttpClient();
        return new ChainGeocoder([
            new YandexGeocoder(
                $http,
                defined('YANDEX_GEO_LINK') ? YANDEX_GEO_LINK : 'https://geocode-maps.yandex.ru/1.x/?format=json&geocode=',
                defined('YANDEX_API_KEY') ? (string) YANDEX_API_KEY : '',
            ),
            new OsmGeocoder(
                $http,
                defined('OSM_GEO_LINK') ? OSM_GEO_LINK : 'https://nominatim.openstreetmap.org/search?format=json&q=',
            ),
            new TwoGisGeocoder(
                $http,
                defined('DOUBLEGIS_GEO_LINK') ? DOUBLEGIS_GEO_LINK : '',
            ),
        ]);
    }

    public function indexBuilder(bool $geocodeMissing = false): IndexBuilder
    {
        return new IndexBuilder(
            new MysqliSearchIndexRepository($this->db),
            $this->tokenizer(),
            $this->geocoder($geocodeMissing),
            $this->locality,
        );
    }

    public function adminGate(): AdminGate
    {
        return new AdminGate();
    }

    public function renderMap(array $get, ?MapViewOptions $options = null): string
    {
        $options ??= MapViewOptions::public();
        return $this->mapView()->render(
            $this->map()->state($this->mapQuery($get), $options),
            $options
        );
    }
}
