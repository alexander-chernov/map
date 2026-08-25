<?php

namespace Map\Port;

use Map\Domain\Address;
use Map\Domain\AddressScope;
use Map\Domain\SearchQuery;

interface AddressRepository
{
    public function resolveScope(SearchQuery $query): AddressScope;

    public function countByScope(AddressScope $scope): int;

    /** @return list<Address> */
    public function findByScope(AddressScope $scope, int $offset, int $limit): array;

    public function countByTokens(array $tokens): int;

    /** @return list<Address> */
    public function findByTokens(array $tokens, int $offset, int $limit): array;
}
