<?php

namespace Map\Domain;

final class SearchQuery
{
    /**
     * @param list<string> $tokens
     */
    public function __construct(
        public readonly int $page,
        public readonly int $perPage,
        public readonly bool $expanded,
        public readonly int $streetId,
        public readonly int $districtId,
        public readonly int $houseId,
        public readonly int $massiveId,
        public readonly string $phrase,
        public readonly array $tokens,
        public readonly ?GeoPoint $point,
        public readonly string $rawPhrase,
    ) {
    }

    /**
     * @param array<string, mixed> $get
     */
    public static function fromGet(array $get, int $perPage, QueryTokenizer $tokenizer): self
    {
        $page = isset($get['page']) ? (int) $get['page'] : 0;
        if ($page < 0) {
            $page = 0;
        }

        $rawPhrase = isset($get['f']) ? trim((string) $get['f']) : '';
        $phrase = self::decodePhrase($rawPhrase);

        $point = GeoPoint::fromPair(isset($get['a']) ? (string) $get['a'] : null);

        return new self(
            page: $page,
            perPage: $perPage,
            expanded: isset($get['show']) && (string) $get['show'] === '1',
            streetId: isset($get['s']) ? (int) $get['s'] : 0,
            districtId: isset($get['d']) ? (int) $get['d'] : 0,
            houseId: isset($get['h']) ? (int) $get['h'] : 0,
            massiveId: isset($get['m']) ? (int) $get['m'] : 0,
            phrase: $phrase,
            tokens: $tokenizer->searchTokens($phrase),
            point: $point,
            rawPhrase: $rawPhrase,
        );
    }

    public function offset(): int
    {
        return $this->page * $this->perPage;
    }

    public function hasPhrase(): bool
    {
        return $this->phrase !== '';
    }

    public function hasStreetFilter(): bool
    {
        return $this->streetId > 0;
    }

    public function hasAddressFilter(): bool
    {
        return $this->streetId > 0 || $this->districtId > 0 || $this->massiveId > 0;
    }

    public function hasRequest(): bool
    {
        return $this->hasPhrase()
            || $this->hasAddressFilter()
            || $this->houseId > 0
            || $this->point !== null;
    }

    private static function decodePhrase(string $raw): string
    {
        if ($raw === '') {
            return '';
        }
        $decoded = json_decode($raw);
        return is_string($decoded) ? $decoded : $raw;
    }
}
