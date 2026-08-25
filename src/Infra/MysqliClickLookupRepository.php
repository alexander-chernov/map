<?php

namespace Map\Infra;

use Map\Domain\ClickQuery;
use Map\Domain\ClickResult;
use Map\Domain\QueryTokenizer;
use Map\Map\MapBalloon;
use Map\Port\ClickLookupRepository;

final class MysqliClickLookupRepository implements ClickLookupRepository
{
    public function __construct(
        private Database $db,
        private QueryTokenizer $tokenizer,
        private MapBalloon $balloon,
    ) {
    }

    public function lookup(ClickQuery $query): ClickResult
    {
        $streetTokens = $this->tokenizer->geoTokens($query->street);
        $houseNumber = $query->houseNumber();

        if ($query->street !== '' && $streetTokens !== []) {
            return $this->byStreet($query, $streetTokens, $houseNumber);
        }
        if ($query->district !== '') {
            return $this->byDistrict($query);
        }
        return new ClickResult(
            house: $houseNumber,
            lat: $query->point?->lat,
            lon: $query->point?->lon,
        );
    }

    /**
     * @param list<string> $streetTokens
     */
    private function byStreet(ClickQuery $query, array $streetTokens, string $houseNumber): ClickResult
    {
        $streets = $this->namedIds(
            'SELECT s.k_s_id AS id, s.k_s_name AS name,
                    (SELECT COUNT(k_shn_id) FROM k_streets_house_nums WHERE k_shn_street_id = s.k_s_id) AS cnt
             FROM k_streets s
             LEFT JOIN k_towns t ON t.k_t_id = s.k_s_town AND t.k_t_name = ?
             WHERE s.k_s_name NOT LIKE \'%##%\'',
            $query->locality,
            $streetTokens,
            's.k_s_name'
        );

        $districts = ['ids' => [], 'count' => 0, 'name' => ''];
        $massives = ['ids' => [], 'count' => 0, 'name' => ''];
        if ($query->district !== '') {
            $districts = $this->areaIds(
                'SELECT d.k_d_id AS id, d.k_d_name AS name,
                        (SELECT COUNT(k_shn_id) FROM k_streets_house_nums WHERE k_shn_district_id = d.k_d_id) AS cnt
                 FROM k_districts d
                 LEFT JOIN k_towns t ON t.k_t_id = d.k_d_town AND t.k_t_name = ?
                 WHERE k_d_name LIKE ?',
                $query->locality,
                $query->district
            );
            $massives = $this->areaIds(
                'SELECT m.k_tm_id AS id, m.k_tm_name AS name,
                        (SELECT COUNT(k_shn_id) FROM k_streets_house_nums WHERE k_shn_massive_id = m.k_tm_id) AS cnt
                 FROM k_towns_massives m
                 LEFT JOIN k_towns t ON t.k_t_id = m.k_tm_town_id AND t.k_t_name = ?
                 WHERE k_tm_name LIKE ?',
                $query->locality,
                $query->district
            );
        }

        $houseIds = $this->houseIds($streets['ids'], $houseNumber, $districts['ids'], $massives['ids']);
        $houseIn = $houseIds === [] ? ['sql' => '0', 'params' => []] : Database::in($houseIds);
        $streetIn = $streets['ids'] === [] ? ['sql' => '0', 'params' => []] : Database::in($streets['ids']);

        $sql = 'SELECT
                    MAX(k_s_id) AS k_s_id,
                    MAX(k_shn_id) AS k_shn_id,
                    MAX(k_d_id) AS k_d_id,
                    MAX(k_tm_id) AS k_tm_id,
                    MAX(d.k_d_name) AS district,
                    MAX(m.k_tm_name) AS massive,
                    (SELECT COUNT(k_shn_id) FROM k_streets_house_nums WHERE k_shn_street_id = h.k_shn_street_id) AS street_count,
                    (SELECT COUNT(k_isf_id) FROM k_immovables_sell WHERE k_isf_address IN (' . $houseIn['sql'] . ') AND k_isf_end_date > NOW()) AS realty_sell,
                    (SELECT COUNT(DISTINCT name) FROM base_org WHERE 1=1';
        $params = $houseIn['params'];
        if ($streets['ids'] !== []) {
            $sql .= ' AND street_id IN (' . $streetIn['sql'] . ')';
            $params = array_merge($params, $streetIn['params']);
        }
        if ($houseNumber !== '') {
            $sql .= ' AND TRIM(house_num) = h.k_shn_house_num';
        }
        $sql .= ') AS org_count
            FROM k_streets_house_nums h
            LEFT JOIN k_districts d ON d.k_d_id = h.k_shn_district_id
            LEFT JOIN k_towns_massives m ON m.k_tm_id = h.k_shn_massive_id
            LEFT JOIN k_streets s ON s.k_s_id = h.k_shn_street_id
            LEFT JOIN k_towns t ON s.k_s_town = t.k_t_id
            WHERE t.k_t_name = ?';
        $params[] = $query->locality;

        if ($streets['ids'] !== []) {
            $sql .= ' AND k_shn_street_id IN (' . $streetIn['sql'] . ')';
            $params = array_merge($params, $streetIn['params']);
        }
        if ($houseNumber !== '') {
            $sql .= ' AND TRIM(h.k_shn_house_num) = ?';
            $params[] = $houseNumber;
        }
        if ($streets['ids'] === [] && $districts['ids'] !== []) {
            $dIn = Database::in($districts['ids']);
            $sql .= ' AND h.k_shn_district_id IN (' . $dIn['sql'] . ')';
            $params = array_merge($params, $dIn['params']);
        }
        if ($streets['ids'] === [] && $massives['ids'] !== []) {
            $mIn = Database::in($massives['ids']);
            $sql .= ' AND h.k_shn_massive_id IN (' . $mIn['sql'] . ')';
            $params = array_merge($params, $mIn['params']);
        }
        $sql .= ' GROUP BY t.k_t_id';

        return $this->hydrate($this->db->all($sql, $params), $query, $houseNumber, $streets, $districts, $massives, $streetTokens);
    }

    private function byDistrict(ClickQuery $query): ClickResult
    {
        $districts = $this->areaIds(
            'SELECT d.k_d_id AS id, d.k_d_name AS name,
                    (SELECT COUNT(k_shn_id) FROM k_streets_house_nums WHERE k_shn_district_id = d.k_d_id) AS cnt
             FROM k_districts d
             LEFT JOIN k_towns t ON t.k_t_id = d.k_d_town AND t.k_t_name = ?
             WHERE k_d_name LIKE ?',
            $query->locality,
            $query->district
        );
        $massives = $this->areaIds(
            'SELECT m.k_tm_id AS id, m.k_tm_name AS name,
                    (SELECT COUNT(k_shn_id) FROM k_streets_house_nums WHERE k_shn_massive_id = m.k_tm_id) AS cnt
             FROM k_towns_massives m
             LEFT JOIN k_towns t ON t.k_t_id = m.k_tm_town_id AND t.k_t_name = ?
             WHERE k_tm_name LIKE ?',
            $query->locality,
            $query->district
        );

        $sql = 'SELECT
                    MAX(k_s_id) AS k_s_id,
                    MAX(k_shn_id) AS k_shn_id,
                    MAX(k_d_id) AS k_d_id,
                    MAX(k_tm_id) AS k_tm_id,
                    MAX(d.k_d_name) AS district,
                    MAX(m.k_tm_name) AS massive,
                    COUNT(k_shn_id) AS address_count,
                    (SELECT COUNT(k_isf_id) FROM k_immovables_sell WHERE k_isf_address = h.k_shn_id AND k_isf_end_date > NOW()) AS realty_sell,
                    (SELECT COUNT(DISTINCT name) FROM base_org WHERE street_id = h.k_shn_street_id) AS org_count
                FROM k_streets_house_nums h
                LEFT JOIN k_districts d ON d.k_d_id = h.k_shn_district_id
                LEFT JOIN k_towns_massives m ON m.k_tm_id = h.k_shn_massive_id
                LEFT JOIN k_streets s ON s.k_s_id = h.k_shn_street_id
                LEFT JOIN k_towns t ON s.k_s_town = t.k_t_id
                WHERE t.k_t_name = ?';
        $params = [$query->locality];
        if ($districts['ids'] !== []) {
            $in = Database::in($districts['ids']);
            $sql .= ' AND h.k_shn_district_id IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        if ($massives['ids'] !== []) {
            $in = Database::in($massives['ids']);
            $sql .= ' AND h.k_shn_massive_id IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        $sql .= ' GROUP BY t.k_t_id';

        return $this->hydrate($this->db->all($sql, $params), $query, '', ['ids' => [], 'count' => 0, 'name' => ''], $districts, $massives, []);
    }

    /**
     * @param list<string> $tokens
     * @return array{ids: list<int>, count: int, name: string}
     */
    private function namedIds(string $sql, string $locality, array $tokens, string $nameCol): array
    {
        $params = [$locality];
        foreach ($tokens as $token) {
            $sql .= ' AND ' . $nameCol . ' LIKE ?';
            $params[] = '%' . $token . '%';
        }
        return $this->collectNamed($this->db->all($sql, $params));
    }

    /**
     * @return array{ids: list<int>, count: int, name: string}
     */
    private function areaIds(string $sql, string $locality, string $name): array
    {
        return $this->collectNamed($this->db->all($sql, [$locality, '%' . $name . '%']));
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{ids: list<int>, count: int, name: string}
     */
    private function collectNamed(array $rows): array
    {
        $ids = [];
        $count = 0;
        $name = '';
        foreach ($rows as $row) {
            $ids[] = (int) $row['id'];
            $count += (int) ($row['cnt'] ?? 0);
            $name = (string) ($row['name'] ?? '');
        }
        return ['ids' => $ids, 'count' => $count, 'name' => $name];
    }

    /**
     * @param list<int> $streetIds
     * @param list<int> $districtIds
     * @param list<int> $massiveIds
     * @return list<int>
     */
    private function houseIds(array $streetIds, string $houseNumber, array $districtIds, array $massiveIds): array
    {
        $sql = 'SELECT k_shn_id AS id FROM k_streets_house_nums h WHERE 1=1';
        $params = [];
        if ($streetIds !== []) {
            $in = Database::in($streetIds);
            $sql .= ' AND k_shn_street_id IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        if ($houseNumber !== '') {
            $sql .= ' AND TRIM(h.k_shn_house_num) = ?';
            $params[] = $houseNumber;
        }
        if ($districtIds !== []) {
            $in = Database::in($districtIds);
            $sql .= ' AND h.k_shn_district_id IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        if ($massiveIds !== []) {
            $in = Database::in($massiveIds);
            $sql .= ' AND h.k_shn_massive_id IN (' . $in['sql'] . ')';
            $params = array_merge($params, $in['params']);
        }
        $ids = [];
        foreach ($this->db->column($sql, $params) as $id) {
            $ids[] = (int) $id;
        }
        return $ids;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @param array{ids: list<int>, count: int, name: string} $streets
     * @param array{ids: list<int>, count: int, name: string} $districts
     * @param array{ids: list<int>, count: int, name: string} $massives
     * @param list<string> $streetTokens
     */
    private function hydrate(
        array $rows,
        ClickQuery $query,
        string $houseNumber,
        array $streets,
        array $districts,
        array $massives,
        array $streetTokens,
    ): ClickResult {
        $streetIds = [];
        $houseIds = [];
        $districtIds = [];
        $massiveIds = [];
        $realty = 0;
        $orgs = 0;
        $addressCount = 0;
        $streetCount = $streets['count'];
        $districtName = $districts['name'];
        $massiveName = $massives['name'];
        $photos = [];

        foreach ($rows as $row) {
            $sid = (int) ($row['k_s_id'] ?? 0);
            $hid = (int) ($row['k_shn_id'] ?? 0);
            $did = (int) ($row['k_d_id'] ?? 0);
            $mid = (int) ($row['k_tm_id'] ?? 0);
            if ($sid && !in_array($sid, $streetIds, true)) {
                $streetIds[] = $sid;
            }
            if ($hid && !in_array($hid, $houseIds, true)) {
                $houseIds[] = $hid;
                $photos = array_merge($photos, $this->db->column(
                    'SELECT k_shp_url FROM k_street_house_photos WHERE k_shp_parent = ?',
                    [$hid]
                ));
            }
            if ($did && !in_array($did, $districtIds, true)) {
                $districtIds[] = $did;
            }
            if ($mid && !in_array($mid, $massiveIds, true)) {
                $massiveIds[] = $mid;
            }
            $districtName = (string) ($row['district'] ?? $districtName);
            $massiveName = (string) ($row['massive'] ?? $massiveName);
            $realty = (int) ($row['realty_sell'] ?? $realty);
            $orgs = (int) ($row['org_count'] ?? $orgs);
            $streetCount = (int) ($row['street_count'] ?? $streetCount);
            $addressCount = (int) ($row['address_count'] ?? $addressCount);
        }

        $ambiguous = 0;
        $link = '';
        if ($massives['count'] === 1 && $massiveIds !== []) {
            $link .= '&m=' . $massiveIds[0];
        } else {
            $ambiguous = 1;
        }
        if ($districts['count'] === 1 && $districtIds !== []) {
            $link .= '&d=' . $districtIds[0];
        } else {
            $ambiguous = 1;
        }
        if ($streets['count'] === 1 && $streetIds !== []) {
            $link .= '&s=' . $streetIds[0];
        } else {
            $ambiguous = 1;
        }
        if ($addressCount === 1 && $houseIds !== []) {
            $link .= '&h=' . $houseIds[0];
        } elseif ($houseIds !== [] && count($houseIds) === 1 && $addressCount === 0) {
            $link .= '&h=' . $houseIds[0];
        } else {
            $ambiguous = 1;
        }
        if ($ambiguous === 1 && $streetTokens !== []) {
            $link .= '&f=' . rawurlencode(implode(' ', $streetTokens) . ', ' . $houseNumber);
        }

        $fotoUrls = [];
        foreach ($photos as $url) {
            if ($url) {
                $fotoUrls[] = (string) $url;
            }
        }

        return new ClickResult(
            link: $link,
            district: $districtName,
            massive: $massiveName,
            districtCount: $districts['count'],
            massiveCount: $massives['count'],
            streetCount: $streetCount,
            addressCount: $addressCount > 0 ? $addressCount : count($houseIds),
            realtyCount: $realty,
            orgCount: $orgs,
            house: $houseNumber,
            foto: $this->balloon->photosHtml($fotoUrls),
            lat: $query->point?->lat,
            lon: $query->point?->lon,
        );
    }
}
