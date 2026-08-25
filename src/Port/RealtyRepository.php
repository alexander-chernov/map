<?php

namespace Map\Port;

use Map\Domain\AddressScope;
use Map\Domain\RealtyListing;

interface RealtyRepository
{
    public function countByScope(AddressScope $scope): int;

    /** @return list<RealtyListing> */
    public function findByScope(AddressScope $scope, int $offset, int $limit): array;

    /** @param list<int> $streetIds */
    public function countByStreetIds(array $streetIds): int;

    /**
     * @param list<int> $streetIds
     * @return list<RealtyListing>
     */
    public function findByStreetIds(array $streetIds, int $offset, int $limit): array;
}
