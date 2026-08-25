<?php

namespace Map\Port;

interface MapGeometryRepository
{
    /**
     * @param list<string> $tokens
     * @return list<array{name: string, geo_obj: string}>
     */
    public function streetsByTokens(string $locality, array $tokens): array;

    /** @return list<array{name: string, geo_obj: string}> */
    public function routeByCode(string $code): array;

    /** @return list<array<string, mixed>> */
    public function stopsByRoute(string $code): array;

    /** @return list<array<string, mixed>> */
    public function stopByFid(int $fid): array;

    /**
     * @param list<string> $tokens
     * @return list<array<string, mixed>>
     */
    public function stopsByTokens(array $tokens, int $offset, int $limit): array;

    /** @return array{name: string, lat: float, lon: float}|null */
    public function districtCenter(string $name): ?array;
}
