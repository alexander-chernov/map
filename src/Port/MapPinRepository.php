<?php

namespace Map\Port;

use Map\Domain\SearchQuery;

interface MapPinRepository
{
    /** @return list<array<string, mixed>> */
    public function housesByScope(SearchQuery $query, int $objectId, int $offset, int $limit): array;

    /**
     * @param list<int> $streetIds
     * @param list<string> $houseNumbers
     * @return list<array<string, mixed>>
     */
    public function orgsByScope(array $streetIds, array $houseNumbers, int $offset, int $limit): array;

    /**
     * @param list<int> $orgIds
     * @param list<int> $streetIds
     * @return list<array<string, mixed>>
     */
    public function orgsByIds(array $orgIds, array $streetIds, int $offset, int $limit): array;

    /**
     * @param list<string> $tokens
     * @return list<array<string, mixed>>
     */
    public function housesByTokens(array $tokens, int $offset, int $limit): array;

    /** @return array<string, mixed>|null */
    public function houseMeta(int $streetId, string $houseNumber): ?array;

    /** @return list<string> */
    public function photoUrls(int $streetId, string $houseNumber): array;

    /** @return list<string> */
    public function photoUrlsByHouseId(int $houseId): array;

    /** @return list<array{lat: float, lon: float, url: string, img: string}> */
    public function banners(): array;
}
