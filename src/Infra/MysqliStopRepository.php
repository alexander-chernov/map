<?php

namespace Map\Infra;

use Map\Domain\GeoProjection;
use Map\Domain\TransitStop;
use Map\Port\StopRepository;

final class MysqliStopRepository implements StopRepository
{
    public function __construct(
        private Database $db,
        private GeoProjection $projection,
    ) {
    }

    public function countByTokens(array $tokens): int
    {
        [$where, $params] = $this->tokenWhere($tokens);
        if ($where === '') {
            return 0;
        }
        return (int) $this->db->value(
            'SELECT COUNT(DISTINCT s.name) FROM map_station s WHERE ' . $where,
            $params
        );
    }

    public function findByTokens(array $tokens, int $offset, int $limit): array
    {
        [$where, $params] = $this->tokenWhere($tokens);
        if ($where === '') {
            return [];
        }
        $sql = 'SELECT DISTINCT fid, routes, s.name, ASText(geometry) AS geo_obj
                FROM map_station s
                WHERE ' . $where . '
                GROUP BY s.name
                ORDER BY s.name ASC
                LIMIT ?, ?';
        $params[] = $offset;
        $params[] = $limit;

        $stops = [];
        foreach ($this->db->all($sql, $params) as $row) {
            $latLon = $this->projection->pointWktToLatLon($row['geo_obj'] ?? null);
            $routes = [];
            foreach (explode(',', (string) ($row['routes'] ?? '')) as $route) {
                $route = trim($route);
                if ($route !== '') {
                    $routes[] = $route;
                }
            }
            $stops[] = new TransitStop(
                fid: (string) ($row['fid'] ?? ''),
                name: (string) ($row['name'] ?? ''),
                routes: $routes,
                lat: $latLon[0] ?? null,
                lon: $latLon[1] ?? null,
            );
        }
        return $stops;
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
            $parts[] = 's.name LIKE ?';
            $params[] = '%' . $token . '%';
        }
        return [implode(' AND ', $parts), $params];
    }
}
