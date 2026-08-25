<?php

namespace Map\Infra;

use Map\Domain\AddressScope;
use Map\Domain\RealtyListing;
use Map\Port\RealtyRepository;

final class MysqliRealtyRepository implements RealtyRepository
{
    public function __construct(private Database $db)
    {
    }

    public function countByScope(AddressScope $scope): int
    {
        [$where, $params] = $this->scopeWhere($scope, 'n');
        if ($where === '') {
            return 0;
        }
        $sql = 'SELECT COUNT(DISTINCT n.centerX, n.centerY)
                FROM k_immovables_sell s
                LEFT JOIN k_immovables_sell_types t ON t.k_isft_id = s.k_isf_immovable_type
                LEFT JOIN k_streets_house_nums n ON n.k_shn_id = s.k_isf_address
                WHERE ' . $where . ' AND s.k_isf_end_date > NOW()';
        return (int) $this->db->value($sql, $params);
    }

    public function findByScope(AddressScope $scope, int $offset, int $limit): array
    {
        [$where, $params] = $this->scopeWhere($scope, 'n');
        if ($where === '') {
            return [];
        }
        $sql = 'SELECT DISTINCT
                    s.k_isf_deal_type,
                    s.k_isf_address,
                    s.k_isf_rooms,
                    t.k_isft_name,
                    s.k_isf_contacts,
                    s.k_isf_contact_name,
                    s.k_isf_description,
                    s.k_isf_price,
                    s.k_isf_registration_date,
                    n.centerX,
                    n.centerY
                FROM k_immovables_sell s
                LEFT JOIN k_immovables_sell_types t ON t.k_isft_id = s.k_isf_immovable_type
                LEFT JOIN k_streets_house_nums n ON n.k_shn_id = s.k_isf_address
                WHERE ' . $where . ' AND s.k_isf_end_date > NOW()
                ORDER BY k_isf_registration_date DESC
                LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return array_map(RealtyListing::fromRow(...), $this->db->all($sql, $params));
    }

    public function countByStreetIds(array $streetIds): int
    {
        if ($streetIds === []) {
            return 0;
        }
        $in = Database::in($streetIds);
        $sql = 'SELECT COUNT(DISTINCT n.centerX, n.centerY)
                FROM k_immovables_sell s
                LEFT JOIN k_immovables_sell_types t ON t.k_isft_id = s.k_isf_immovable_type
                LEFT JOIN k_streets_house_nums n ON n.k_shn_id = s.k_isf_address
                WHERE n.k_shn_street_id IN (' . $in['sql'] . ') AND s.k_isf_end_date > NOW()';
        return (int) $this->db->value($sql, $in['params']);
    }

    public function findByStreetIds(array $streetIds, int $offset, int $limit): array
    {
        if ($streetIds === []) {
            return [];
        }
        $in = Database::in($streetIds);
        $sql = 'SELECT DISTINCT
                    s.k_isf_deal_type,
                    s.k_isf_address,
                    s.k_isf_rooms,
                    t.k_isft_name,
                    s.k_isf_contacts,
                    s.k_isf_contact_name,
                    s.k_isf_description,
                    s.k_isf_price,
                    s.k_isf_registration_date,
                    h.centerX,
                    h.centerY,
                    d.k_d_name AS district,
                    m.k_tm_name AS massive,
                    st.k_s_name AS street,
                    h.k_shn_house_num AS house
                FROM k_immovables_sell s
                LEFT JOIN k_immovables_sell_types t ON t.k_isft_id = s.k_isf_immovable_type
                LEFT JOIN k_streets_house_nums h ON h.k_shn_id = s.k_isf_address
                LEFT JOIN k_streets st ON h.k_shn_street_id = st.k_s_id
                LEFT JOIN k_districts d ON h.k_shn_district_id = d.k_d_id
                LEFT JOIN k_towns_massives m ON h.k_shn_massive_id = m.k_tm_id
                WHERE h.k_shn_street_id IN (' . $in['sql'] . ') AND s.k_isf_end_date > NOW()
                ORDER BY k_isf_registration_date DESC
                LIMIT ?, ?';
        $params = array_merge($in['params'], [$offset, $limit]);
        return array_map(RealtyListing::fromRow(...), $this->db->all($sql, $params));
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function scopeWhere(AddressScope $scope, string $alias): array
    {
        $parts = [];
        $params = [];
        if ($scope->streetIds !== []) {
            $in = Database::in($scope->streetIds);
            $parts[] = $alias . '.k_shn_street_id IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        if ($scope->houseNumbers !== []) {
            $in = Database::in($scope->houseNumbers);
            $parts[] = 'TRIM(' . $alias . '.k_shn_house_num) IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        return [implode(' AND ', $parts), $params];
    }
}
