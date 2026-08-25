<?php

namespace Map\Port;

use Map\Domain\AddressScope;
use Map\Domain\Organization;

interface OrganizationRepository
{
    public function countByScope(AddressScope $scope): int;

    /** @return list<Organization> */
    public function findByScope(AddressScope $scope, int $offset, int $limit): array;

    /** @param list<int> $orgIds @param list<int> $streetIds */
    public function countByIds(array $orgIds, array $streetIds): int;

    /**
     * @param list<int> $orgIds
     * @param list<int> $streetIds
     * @return list<Organization>
     */
    public function findByIds(array $orgIds, array $streetIds, int $offset, int $limit): array;

    /** @return list<array<string, mixed>> */
    public function suggestByName(string $prefix, int $offset, int $limit): array;
}
