<?php

namespace Map\Infra;

use mysqli;
use mysqli_result;
use RuntimeException;

final class Database
{
    public function __construct(private mysqli $mysqli)
    {
    }

    public function mysqli(): mysqli
    {
        return $this->mysqli;
    }

    /**
     * @param list<mixed> $params
     * @return list<array<string, mixed>>
     */
    public function all(string $sql, array $params = []): array
    {
        $result = $this->run($sql, $params);
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * @param list<mixed> $params
     * @return list<mixed>
     */
    public function column(string $sql, array $params = []): array
    {
        $values = [];
        foreach ($this->all($sql, $params) as $row) {
            $values[] = array_values($row)[0];
        }
        return $values;
    }

    /**
     * @param list<mixed> $params
     */
    public function value(string $sql, array $params = []): mixed
    {
        $rows = $this->all($sql, $params);
        if ($rows === []) {
            return null;
        }
        return array_values($rows[0])[0];
    }

    /**
     * @param list<int|string> $values
     * @return array{sql: string, params: list<mixed>}
     */
    public static function in(array $values): array
    {
        if ($values === []) {
            return ['sql' => 'NULL', 'params' => []];
        }
        $placeholders = implode(',', array_fill(0, count($values), '?'));
        return ['sql' => $placeholders, 'params' => array_values($values)];
    }

    /**
     * @param list<mixed> $params
     */
    public function execute(string $sql, array $params = []): void
    {
        if ($params === []) {
            $result = $this->mysqli->query($sql);
            if ($result === false) {
                throw new RuntimeException('SQL error: ' . $this->mysqli->error);
            }
            if ($result instanceof mysqli_result) {
                $result->free();
            }
            return;
        }

        $stmt = $this->mysqli->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('SQL prepare: ' . $this->mysqli->error);
        }
        $types = '';
        foreach ($params as $param) {
            $types .= match (true) {
                is_int($param) => 'i',
                is_float($param) => 'd',
                default => 's',
            };
        }
        $stmt->bind_param($types, ...$this->refs($params));
        if (!$stmt->execute()) {
            throw new RuntimeException('SQL execute: ' . $stmt->error);
        }
        $stmt->close();
    }

    /**
     * @param list<mixed> $params
     * @return list<mixed>
     */
    private function refs(array &$params): array
    {
        $refs = [];
        foreach ($params as $key => $_) {
            $refs[$key] = &$params[$key];
        }
        return $refs;
    }

    /**
     * @param list<mixed> $params
     */
    private function run(string $sql, array $params): mysqli_result
    {
        if ($params === []) {
            $result = $this->mysqli->query($sql);
            if ($result === false) {
                throw new RuntimeException('SQL error: ' . $this->mysqli->error);
            }
            return $result;
        }

        $stmt = $this->mysqli->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('SQL prepare: ' . $this->mysqli->error);
        }

        $types = '';
        foreach ($params as $param) {
            $types .= match (true) {
                is_int($param) => 'i',
                is_float($param) => 'd',
                default => 's',
            };
        }
        $stmt->bind_param($types, ...$this->refs($params));
        if (!$stmt->execute()) {
            throw new RuntimeException('SQL execute: ' . $stmt->error);
        }
        $result = $stmt->get_result();
        if ($result === false) {
            $stmt->close();
            throw new RuntimeException('SQL result: ' . $this->mysqli->error);
        }
        return $result;
    }
}
