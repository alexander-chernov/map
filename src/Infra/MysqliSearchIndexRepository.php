<?php

namespace Map\Infra;

use Map\Port\SearchIndexRepository;
use Map\Port\SearchIndexWriter;

final class MysqliSearchIndexRepository implements SearchIndexRepository, SearchIndexWriter
{
    public function __construct(private Database $db)
    {
    }

    public function findOrgHits(array $tokens): array
    {
        $rows = $this->lookup($tokens);
        if ($rows === [] && count($tokens) > 1) {
            $rows = $this->lookup(array_slice($tokens, 0, -1));
        }
        return $this->collectIds($rows);
    }

    public function findStreetIds(array $tokens): array
    {
        $rows = $this->lookup($tokens);
        $ids = [];
        foreach ($rows as $row) {
            if (!empty($row['street_id'])) {
                $ids[] = (int) $row['street_id'];
            }
        }
        return array_values(array_unique($ids));
    }

    public function suggestWords(array $tokens, int $offset, int $limit): array
    {
        if ($tokens === []) {
            return [];
        }
        if (count($tokens) === 1) {
            return array_map('strval', $this->db->column(
                'SELECT DISTINCT s.word AS id FROM search_index s WHERE s.word LIKE ? ORDER BY s.word ASC LIMIT ?, ?',
                [$tokens[0] . '%', $offset, $limit]
            ));
        }

        $last = $tokens[count($tokens) - 1];
        $prefix = implode(' ', array_slice($tokens, 0, -1)) . ' ';
        $sql = 'SELECT CONCAT(?, s.word) AS id FROM search_index s WHERE s.word LIKE ?';
        $params = [$prefix, $last . '%'];
        foreach (array_slice($tokens, 0, -1) as $token) {
            $sql .= ' AND s.string LIKE ?';
            $params[] = '%' . $token . '%';
        }
        $sql .= ' LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;

        $words = [];
        foreach ($this->db->all($sql, $params) as $row) {
            $words[] = (string) $row['id'];
        }
        return $words;
    }

    public function truncate(): void
    {
        $this->db->execute('TRUNCATE TABLE search_index');
    }

    public function insertOrgTerm(string $word, string $haystack, int $orgId, int $streetId): void
    {
        $this->db->execute(
            'INSERT INTO search_index (word, string, base_org_id, street_id) VALUES (?, ?, ?, ?)',
            [$word, $haystack, $orgId, $streetId]
        );
    }

    public function insertStreetTerm(string $word, string $haystack, int $streetId): void
    {
        $this->db->execute(
            'INSERT INTO search_index (word, string, street_id) VALUES (?, ?, ?)',
            [$word, $haystack, $streetId]
        );
    }

    public function updateOrgCoords(int $orgId, float $lat, float $lon): void
    {
        $this->db->execute(
            'UPDATE base_org SET centerX = ?, centerY = ? WHERE id = ?',
            [$lat, $lon, $orgId]
        );
    }

    public function organizationsForIndex(): array
    {
        return $this->db->all(
            'SELECT
                b.id,
                b.street,
                b.house_num,
                b.category,
                b.subcategory,
                b.name,
                b.description,
                b.street_id,
                h.centerX AS h_X,
                h.centerY AS h_Y
             FROM base_org b
             LEFT JOIN k_streets_house_nums h
                ON h.k_shn_street_id = b.street_id AND TRIM(b.house_num) = TRIM(h.k_shn_house_num)
             ORDER BY b.id ASC'
        );
    }

    public function addressesForIndex(): array
    {
        return $this->db->all(
            'SELECT
                d.k_d_name AS district,
                m.k_tm_name AS massive,
                s.k_s_name AS street,
                h.k_shn_house_num AS house,
                h.k_shn_street_id
             FROM k_streets_house_nums h
             LEFT JOIN k_streets s ON h.k_shn_street_id = s.k_s_id
             LEFT JOIN k_districts d ON h.k_shn_district_id = d.k_d_id
             LEFT JOIN k_towns_massives m ON h.k_shn_massive_id = m.k_tm_id
             WHERE s.k_s_name NOT LIKE \'%##%\'
             ORDER BY h.k_shn_id ASC'
        );
    }

    /**
     * @param list<string> $tokens
     * @return list<array<string, mixed>>
     */
    private function lookup(array $tokens): array
    {
        if ($tokens === []) {
            return [];
        }
        $last = $tokens[count($tokens) - 1];
        $sql = 'SELECT DISTINCT s.base_org_id, s.street_id FROM search_index s WHERE s.word LIKE ?';
        $params = [$last . '%'];
        foreach (array_slice($tokens, 0, -1) as $token) {
            $sql .= ' AND s.string LIKE ?';
            $params[] = '%' . $token . '%';
        }
        return $this->db->all($sql, $params);
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return array{orgIds: list<int>, streetIds: list<int>}
     */
    private function collectIds(array $rows): array
    {
        $orgIds = [];
        $streetIds = [];
        foreach ($rows as $row) {
            if (!empty($row['base_org_id'])) {
                $orgIds[] = (int) $row['base_org_id'];
            }
            if (!empty($row['street_id'])) {
                $streetIds[] = (int) $row['street_id'];
            }
        }
        return [
            'orgIds' => array_values(array_unique($orgIds)),
            'streetIds' => array_values(array_unique($streetIds)),
        ];
    }
}
