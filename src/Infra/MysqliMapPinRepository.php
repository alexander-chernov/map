<?php

namespace Map\Infra;

use Map\Domain\SearchQuery;
use Map\Port\MapPinRepository;

final class MysqliMapPinRepository implements MapPinRepository
{
    public function __construct(private Database $db)
    {
    }

    public function housesByScope(SearchQuery $query, int $objectId, int $offset, int $limit): array
    {
        $sql = 'SELECT
                    s.k_s_name AS street,
                    n.k_shn_street_id AS street_id,
                    n.k_shn_id AS address_id,
                    n.k_shn_house_num AS house,
                    t.k_t_name AS town,
                    m.k_tm_name AS massive,
                    d.k_d_name AS district,
                    n.centerX,
                    n.centerY,
                    (SELECT COUNT(k_shn_id) FROM k_streets_house_nums WHERE k_shn_id = n.k_shn_id) AS addr_count,
                    (SELECT COUNT(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = n.k_shn_id AND k_isf_end_date > NOW()) AS realty_sell,
                    (SELECT COUNT(DISTINCT name) FROM base_org WHERE street_id = n.k_shn_street_id AND TRIM(house_num) = n.k_shn_house_num) AS org_count
                FROM k_streets_house_nums n
                LEFT JOIN k_streets s ON s.k_s_id = n.k_shn_street_id
                LEFT JOIN k_towns t ON t.k_t_id = s.k_s_town
                LEFT JOIN k_towns_massives m ON m.k_tm_id = n.k_shn_massive_id
                LEFT JOIN k_districts d ON d.k_d_id = n.k_shn_district_id
                WHERE 1=1';
        $params = [];
        if ($query->streetId) {
            $sql .= ' AND n.k_shn_street_id = ?';
            $params[] = $query->streetId;
        }
        if ($query->districtId) {
            $sql .= ' AND n.k_shn_district_id = ?';
            $params[] = $query->districtId;
        }
        if ($query->houseId) {
            $sql .= ' AND n.k_shn_id = ?';
            $params[] = $query->houseId;
        }
        if ($query->massiveId) {
            $sql .= ' AND n.k_shn_massive_id = ?';
            $params[] = $query->massiveId;
        }
        if ($objectId) {
            $sql .= ' AND n.k_shn_object_id = ?';
            $params[] = $objectId;
        }
        $sql .= ' GROUP BY n.k_shn_house_num LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return $this->db->all($sql, $params);
    }

    public function orgsByScope(array $streetIds, array $houseNumbers, int $offset, int $limit): array
    {
        if ($streetIds === [] && $houseNumbers === []) {
            return [];
        }
        $sql = 'SELECT DISTINCT
                    o.name,
                    o.address,
                    o.street_id,
                    s.k_s_name AS street,
                    n.k_shn_id AS address_id,
                    n.k_shn_house_num AS house,
                    t.k_t_name AS town,
                    m.k_tm_name AS massive,
                    d.k_d_name AS district,
                    n.centerX,
                    n.centerY,
                    (SELECT COUNT(k_shn_id) FROM k_streets_house_nums WHERE k_shn_id = n.k_shn_id) AS addr_count,
                    (SELECT COUNT(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = n.k_shn_id AND k_isf_end_date > NOW()) AS realty_sell,
                    (SELECT COUNT(DISTINCT name) FROM base_org WHERE street_id = n.k_shn_street_id AND TRIM(house_num) = n.k_shn_house_num) AS org_count
                FROM base_org o
                LEFT JOIN k_streets_house_nums n ON n.k_shn_street_id = o.street_id AND o.house_num = n.k_shn_house_num
                LEFT JOIN k_streets s ON s.k_s_id = n.k_shn_street_id
                LEFT JOIN k_towns t ON t.k_t_id = s.k_s_town
                LEFT JOIN k_towns_massives m ON m.k_tm_id = n.k_shn_massive_id
                LEFT JOIN k_districts d ON d.k_d_id = n.k_shn_district_id
                WHERE 1=1';
        $params = [];
        if ($streetIds !== []) {
            $in = Database::in($streetIds);
            $sql .= ' AND street_id IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        if ($houseNumbers !== []) {
            $in = Database::in($houseNumbers);
            $sql .= ' AND TRIM(house_num) IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        $sql .= ' GROUP BY n.k_shn_house_num LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return $this->db->all($sql, $params);
    }

    public function orgsByIds(array $orgIds, array $streetIds, int $offset, int $limit): array
    {
        if ($orgIds === []) {
            return [];
        }
        $in = Database::in($orgIds);
        $sql = 'SELECT DISTINCT
                    b.town, b.street, b.house_num AS house, b.centerX, b.centerY, b.street_id,
                    (SELECT COUNT(DISTINCT name) FROM base_org WHERE street_id = b.street_id AND TRIM(house_num) = TRIM(b.house_num)) AS org_count
                FROM base_org b
                WHERE IFNULL(b.centerX, 0) > 0 AND IFNULL(b.centerY, 0) > 0
                AND id IN (' . $in['sql'] . ')';
        $params = $in['params'];
        if ($streetIds !== []) {
            $streets = Database::in($streetIds);
            $sql .= ' AND street_id IN (' . $streets['sql'] . ')';
            $params = array_merge($params, $streets['params']);
        }
        $sql .= ' LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return $this->db->all($sql, $params);
    }

    public function housesByTokens(array $tokens, int $offset, int $limit): array
    {
        if ($tokens === []) {
            return [];
        }
        $sql = 'SELECT DISTINCT
                    d.k_d_name AS district,
                    m.k_tm_name AS massive,
                    s.k_s_name AS street,
                    h.k_shn_street_id AS street_id,
                    h.k_shn_id AS address_id,
                    h.k_shn_house_num AS house,
                    h.centerX,
                    h.centerY,
                    (SELECT COUNT(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = h.k_shn_id AND k_isf_end_date > NOW()) AS realty_sell,
                    (SELECT COUNT(DISTINCT name) FROM base_org WHERE street_id = h.k_shn_street_id AND TRIM(house_num) = TRIM(h.k_shn_house_num)) AS org_count,
                    t.k_t_name AS town
                FROM k_streets_house_nums h
                LEFT JOIN k_streets s ON h.k_shn_street_id = s.k_s_id
                LEFT JOIN k_districts d ON h.k_shn_district_id = d.k_d_id
                LEFT JOIN k_towns_massives m ON h.k_shn_massive_id = m.k_tm_id
                LEFT JOIN k_towns t ON t.k_t_id = m.k_tm_town_id AND t.k_t_id = d.k_d_town AND t.k_t_id = s.k_s_town
                WHERE s.k_s_name NOT LIKE \'%##%\'
                AND IFNULL(h.centerX, 0) > 0
                AND IFNULL(h.centerY, 0) > 0';
        $params = [];
        foreach ($tokens as $token) {
            $sql .= " AND CONCAT(IFNULL(d.k_d_name,' '),' ',IFNULL(m.k_tm_name,' '),' ',s.k_s_name,' ',TRIM(h.k_shn_house_num)) LIKE ?";
            $params[] = '%' . $token . '%';
        }
        $sql .= ' ORDER BY s.k_s_name, h.k_shn_house_num ASC LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return $this->db->all($sql, $params);
    }

    public function houseMeta(int $streetId, string $houseNumber): ?array
    {
        $rows = $this->db->all(
            'SELECT
                (SELECT COUNT(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = n.k_shn_id AND k_isf_end_date > NOW()) AS realty_sell,
                n.k_shn_id AS address_id,
                (SELECT COUNT(k_shn_id) FROM k_streets_house_nums WHERE k_shn_id = n.k_shn_id) AS addr_count,
                m.k_tm_name AS massive,
                d.k_d_name AS district
             FROM k_streets_house_nums n
             LEFT JOIN k_towns_massives m ON m.k_tm_id = n.k_shn_massive_id
             LEFT JOIN k_districts d ON d.k_d_id = n.k_shn_district_id
             WHERE n.k_shn_street_id = ? AND TRIM(n.k_shn_house_num) = ?
             LIMIT 1',
            [$streetId, $houseNumber]
        );
        return $rows[0] ?? null;
    }

    public function photoUrls(int $streetId, string $houseNumber): array
    {
        $urls = [];
        foreach ($this->db->all(
            'SELECT f.k_shp_url
             FROM k_streets_house_nums n
             LEFT JOIN k_street_house_photos f ON n.k_shn_id = f.k_shp_parent
             WHERE n.k_shn_street_id = ? AND TRIM(n.k_shn_house_num) = ?',
            [$streetId, $houseNumber]
        ) as $row) {
            if (!empty($row['k_shp_url'])) {
                $urls[] = (string) $row['k_shp_url'];
            }
        }
        return $urls;
    }

    public function photoUrlsByHouseId(int $houseId): array
    {
        if ($houseId <= 0) {
            return [];
        }
        $urls = [];
        foreach ($this->db->column(
            'SELECT k_shp_url FROM k_street_house_photos WHERE k_shp_parent = ?',
            [$houseId]
        ) as $url) {
            if ($url) {
                $urls[] = (string) $url;
            }
        }
        return $urls;
    }

    public function banners(): array
    {
        $out = [];
        foreach ($this->db->all('SELECT url, img, centerX, centerY FROM banner') as $row) {
            $out[] = [
                'lat' => (float) $row['centerX'],
                'lon' => (float) $row['centerY'],
                'url' => (string) $row['url'],
                'img' => (string) $row['img'],
            ];
        }
        return $out;
    }
}
