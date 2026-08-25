<?php

namespace Map\Port;

interface SearchIndexWriter
{
    public function truncate(): void;

    public function insertOrgTerm(string $word, string $haystack, int $orgId, int $streetId): void;

    public function insertStreetTerm(string $word, string $haystack, int $streetId): void;

    public function updateOrgCoords(int $orgId, float $lat, float $lon): void;

    /** @return list<array<string, mixed>> */
    public function organizationsForIndex(): array;

    /** @return list<array<string, mixed>> */
    public function addressesForIndex(): array;
}
