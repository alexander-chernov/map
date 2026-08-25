<?php

namespace Map\Domain;

final class MapQuery
{
    public function __construct(
        public readonly SearchQuery $search,
        public readonly bool $showStreet,
        public readonly string $routeCode,
        public readonly int $stopFid,
        public readonly int $objectId,
        public readonly int $width,
        public readonly bool $noAds,
        public readonly bool $scrollZoom,
        public readonly ?object $geocode,
        public readonly int $perPage,
        public readonly string $locality,
    ) {
    }

    /**
     * @param array<string, mixed> $get
     */
    public static function fromGet(
        array $get,
        int $perPage,
        int $perPageAjax,
        string $locality,
        QueryTokenizer $tokenizer,
    ): self {
        $width = isset($get['w']) ? (int) $get['w'] : 0;
        $geocode = null;
        if (!empty($get['c'])) {
            $decoded = json_decode((string) $get['c']);
            if (is_object($decoded)) {
                $geocode = $decoded;
            }
        }

        return new self(
            search: SearchQuery::fromGet($get, $perPageAjax, $tokenizer),
            showStreet: isset($get['street']) && (string) $get['street'] === 'on',
            routeCode: isset($get['rt']) ? trim((string) $get['rt']) : '',
            stopFid: isset($get['bs']) ? (int) $get['bs'] : 0,
            objectId: isset($get['o']) ? (int) $get['o'] : 0,
            width: $width > 0 ? $width : 800,
            noAds: isset($get['noadv']) && (string) $get['noadv'] === '1',
            scrollZoom: !isset($get['frame']) || (string) $get['frame'] !== '1',
            geocode: $geocode,
            perPage: $perPage,
            locality: $locality,
        );
    }

    public function hasAddressFilter(): bool
    {
        $s = $this->search;
        return $s->streetId > 0 || $s->districtId > 0 || $s->houseId > 0 || $s->massiveId > 0;
    }
}
