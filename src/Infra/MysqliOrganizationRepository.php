<?php

namespace Map\Infra;

use Map\Domain\AddressScope;
use Map\Domain\Organization;
use Map\Port\OrganizationRepository;

final class MysqliOrganizationRepository implements OrganizationRepository
{
    public function __construct(private Database $db)
    {
    }

    public function countByScope(AddressScope $scope): int
    {
        [$where, $params] = $this->scopeWhere($scope);
        if ($where === '') {
            return 0;
        }
        return (int) $this->db->value(
            'SELECT COUNT(DISTINCT name, address) FROM base_org WHERE ' . $where,
            $params
        );
    }

    public function findByScope(AddressScope $scope, int $offset, int $limit): array
    {
        [$where, $params] = $this->scopeWhere($scope);
        if ($where === '') {
            return [];
        }
        $sql = 'SELECT DISTINCT name, address, site, email, phone1, phone2, phone3, phone4, centerX, centerY
                FROM base_org
                WHERE ' . $where . '
                ORDER BY name ASC
                LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return array_map(Organization::fromRow(...), $this->db->all($sql, $params));
    }

    public function countByIds(array $orgIds, array $streetIds): int
    {
        [$where, $params] = $this->idsWhere($orgIds, $streetIds);
        if ($where === '') {
            return 0;
        }
        return (int) $this->db->value(
            'SELECT COUNT(DISTINCT name, address) FROM base_org WHERE ' . $where,
            $params
        );
    }

    public function findByIds(array $orgIds, array $streetIds, int $offset, int $limit): array
    {
        [$where, $params] = $this->idsWhere($orgIds, $streetIds);
        if ($where === '') {
            return [];
        }
        $sql = 'SELECT DISTINCT name, address, site, email, phone1, phone2, phone3, phone4, centerX, centerY
                FROM base_org
                WHERE ' . $where . '
                ORDER BY name ASC
                LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return array_map(Organization::fromRow(...), $this->db->all($sql, $params));
    }

    public function suggestByName(string $prefix, int $offset, int $limit): array
    {
        if ($prefix === '') {
            return [];
        }
        return $this->db->all(
            'SELECT DISTINCT o.town, o.street, o.house_num AS house, o.name, o.centerX AS X, o.centerY AS Y
             FROM base_org o
             WHERE o.name LIKE ?
             ORDER BY o.name ASC
             LIMIT ?, ?',
            [$prefix . '%', $offset, $limit]
        );
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function scopeWhere(AddressScope $scope): array
    {
        $parts = [];
        $params = [];
        if ($scope->streetIds !== []) {
            $in = Database::in($scope->streetIds);
            $parts[] = 'street_id IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        if ($scope->houseNumbers !== []) {
            $in = Database::in($scope->houseNumbers);
            $parts[] = 'TRIM(house_num) IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        return [implode(' AND ', $parts), $params];
    }

    /**
     * @param list<int> $orgIds
     * @param list<int> $streetIds
     * @return array{0: string, 1: list<mixed>}
     */
    private function idsWhere(array $orgIds, array $streetIds): array
    {
        if ($orgIds === []) {
            return ['', []];
        }
        $in = Database::in($orgIds);
        $sql = 'id IN (' . $in['sql'] . ')';
        $params = $in['params'];
        if ($streetIds !== []) {
            $streets = Database::in($streetIds);
            $sql .= ' AND street_id IN (' . $streets['sql'] . ')';
            $params = array_merge($params, $streets['params']);
        }
        return [$sql, $params];
    }
}
