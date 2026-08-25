<?php

namespace Map\Infra;

use Map\Port\MapGeometryRepository;

final class MysqliMapGeometryRepository implements MapGeometryRepository
{
    public function __construct(private Database $db)
    {
    }

    public function streetsByTokens(string $locality, array $tokens): array
    {
        if ($tokens === []) {
            return [];
        }
        $sql = "SELECT ASText(geometry) AS geo_obj, s.k_s_name AS street_name
                FROM k_streets s
                LEFT JOIN k_towns t ON t.k_t_id = s.k_s_town AND t.k_t_name = ?
                LEFT JOIN map_streets m ON m.k_s_id = s.k_s_id
                WHERE s.k_s_name NOT LIKE '%##%'
                AND ASText(geometry) IS NOT NULL";
        $params = [$locality];
        foreach ($tokens as $token) {
            $sql .= ' AND s.k_s_name LIKE ?';
            $params[] = '%' . $token . '%';
        }
        $rows = [];
        foreach ($this->db->all($sql, $params) as $row) {
            $rows[] = [
                'name' => (string) ($row['street_name'] ?? ''),
                'geo_obj' => (string) ($row['geo_obj'] ?? ''),
            ];
        }
        return $rows;
    }

    public function routeByCode(string $code): array
    {
        if ($code === '') {
            return [];
        }
        $rows = [];
        foreach ($this->db->all(
            'SELECT ASText(s.geometry) AS geo_obj, s.name FROM map_routes s WHERE name = ? GROUP BY s.name',
            [$code]
        ) as $row) {
            $rows[] = [
                'name' => (string) ($row['name'] ?? ''),
                'geo_obj' => (string) ($row['geo_obj'] ?? ''),
            ];
        }
        return $rows;
    }

    public function stopsByRoute(string $code): array
    {
        if ($code === '') {
            return [];
        }
        return $this->db->all(
            'SELECT DISTINCT fid, routes, s.name, ASText(geometry) AS geo_obj
             FROM map_station s
             WHERE s.routes LIKE ?
             GROUP BY s.name',
            ['%' . $code . '%']
        );
    }

    public function stopByFid(int $fid): array
    {
        if ($fid <= 0) {
            return [];
        }
        return $this->db->all(
            'SELECT DISTINCT fid, routes, s.name, ASText(geometry) AS geo_obj
             FROM map_station s
             WHERE fid = ?',
            [$fid]
        );
    }

    public function stopsByTokens(array $tokens, int $offset, int $limit): array
    {
        $sql = 'SELECT DISTINCT fid, routes, s.name, centerX, centerY
                FROM map_station s
                WHERE IFNULL(centerX, 0) > 0 AND IFNULL(centerY, 0) > 0';
        $params = [];
        foreach ($tokens as $token) {
            $sql .= ' AND s.name LIKE ?';
            $params[] = '%' . $token . '%';
        }
        $sql .= ' GROUP BY s.name ORDER BY s.name ASC LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;
        return $this->db->all($sql, $params);
    }

    public function districtCenter(string $name): ?array
    {
        if ($name === '') {
            return null;
        }
        $row = $this->db->all(
            'SELECT k_d_name, centerX, centerY FROM k_districts WHERE k_d_name LIKE ? LIMIT 1',
            ['%' . $name . '%']
        );
        if ($row === []) {
            return null;
        }
        return [
            'name' => (string) $row[0]['k_d_name'],
            'lat' => (float) $row[0]['centerX'],
            'lon' => (float) $row[0]['centerY'],
        ];
    }
}
