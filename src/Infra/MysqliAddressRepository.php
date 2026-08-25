<?php

namespace Map\Infra;

use Map\Domain\Address;
use Map\Domain\AddressScope;
use Map\Domain\SearchQuery;
use Map\Port\AddressRepository;

final class MysqliAddressRepository implements AddressRepository
{
    public function __construct(private Database $db)
    {
    }

    public function resolveScope(SearchQuery $query): AddressScope
    {
        $streetIds = [];
        $houseNumbers = [];

        if ($query->massiveId || $query->districtId || $query->streetId) {
            $sql = 'SELECT DISTINCT k_shn_street_id FROM k_streets_house_nums WHERE 1=1';
            $params = [];
            if ($query->massiveId) {
                $sql .= ' AND k_shn_massive_id = ?';
                $params[] = $query->massiveId;
            }
            if ($query->districtId) {
                $sql .= ' AND k_shn_district_id = ?';
                $params[] = $query->districtId;
            }
            if ($query->streetId) {
                $sql .= ' AND k_shn_street_id = ?';
                $params[] = $query->streetId;
            }
            foreach ($this->db->column($sql, $params) as $id) {
                $streetIds[] = (int) $id;
            }
        }

        if ($query->houseId) {
            foreach ($this->db->column(
                'SELECT DISTINCT k_shn_house_num FROM k_streets_house_nums WHERE k_shn_id = ?',
                [$query->houseId]
            ) as $num) {
                $houseNumbers[] = trim((string) $num);
            }
        }

        return new AddressScope($streetIds, $houseNumbers);
    }

    public function countByScope(AddressScope $scope): int
    {
        [$where, $params] = $this->scopeWhere($scope, 'k_shn_street_id', 'k_shn_house_num');
        if ($where === '') {
            return 0;
        }
        $sql = 'SELECT COUNT(DISTINCT k_shn_house_num) FROM k_streets_house_nums WHERE ' . $where;
        return (int) $this->db->value($sql, $params);
    }

    public function findByScope(AddressScope $scope, int $offset, int $limit): array
    {
        [$where, $params] = $this->scopeWhere($scope, 'n.k_shn_street_id', 'n.k_shn_house_num');
        if ($where === '') {
            return [];
        }
        $sql = 'SELECT DISTINCT
                    n.k_shn_house_num,
                    n.centerX,
                    n.centerY,
                    s.k_s_name AS street,
                    m.k_tm_name AS massive,
                    d.k_d_name AS district
                FROM k_streets_house_nums n
                LEFT JOIN k_streets s ON s.k_s_id = n.k_shn_street_id
                LEFT JOIN k_towns_massives m ON m.k_tm_id = n.k_shn_massive_id
                LEFT JOIN k_districts d ON d.k_d_id = n.k_shn_district_id
                WHERE ' . $where . '
                LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return array_map(Address::fromRow(...), $this->db->all($sql, $params));
    }

    public function countByTokens(array $tokens): int
    {
        [$where, $params] = $this->tokenWhere($tokens);
        if ($where === '') {
            return 0;
        }
        $sql = 'SELECT COUNT(h.k_shn_house_num)
                FROM k_streets_house_nums h
                LEFT JOIN k_streets s ON h.k_shn_street_id = s.k_s_id
                LEFT JOIN k_districts d ON h.k_shn_district_id = d.k_d_id
                LEFT JOIN k_towns_massives m ON h.k_shn_massive_id = m.k_tm_id
                WHERE s.k_s_name NOT LIKE \'%##%\' AND ' . $where;
        return (int) $this->db->value($sql, $params);
    }

    public function findByTokens(array $tokens, int $offset, int $limit): array
    {
        [$where, $params] = $this->tokenWhere($tokens);
        if ($where === '') {
            return [];
        }
        $sql = 'SELECT DISTINCT
                    d.k_d_name AS district,
                    m.k_tm_name AS massive,
                    s.k_s_name AS street,
                    h.k_shn_house_num AS house,
                    h.centerX,
                    h.centerY
                FROM k_streets_house_nums h
                LEFT JOIN k_streets s ON h.k_shn_street_id = s.k_s_id
                LEFT JOIN k_districts d ON h.k_shn_district_id = d.k_d_id
                LEFT JOIN k_towns_massives m ON h.k_shn_massive_id = m.k_tm_id
                WHERE s.k_s_name NOT LIKE \'%##%\' AND ' . $where . '
                ORDER BY s.k_s_name, h.k_shn_house_num ASC
                LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return array_map(Address::fromRow(...), $this->db->all($sql, $params));
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function scopeWhere(AddressScope $scope, string $streetCol, string $houseCol): array
    {
        $parts = [];
        $params = [];
        if ($scope->streetIds !== []) {
            $in = Database::in($scope->streetIds);
            $parts[] = $streetCol . ' IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        if ($scope->houseNumbers !== []) {
            $in = Database::in($scope->houseNumbers);
            $parts[] = 'TRIM(' . $houseCol . ') IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        return [implode(' AND ', $parts), $params];
    }

    /**
     * @param list<string> $tokens
     * @return array{0: string, 1: list<mixed>}
     */
    private function tokenWhere(array $tokens): array
    {
        $parts = [];
        $params = [];
        foreach ($tokens as $token) {
            $parts[] = "CONCAT(IFNULL(d.k_d_name,' '),' ',IFNULL(m.k_tm_name,' '),' ',s.k_s_name,' ',TRIM(h.k_shn_house_num)) LIKE ?";
            $params[] = '%' . $token . '%';
        }
        return [implode(' AND ', $parts), $params];
    }
}
