<?php

namespace Map\Domain;

final class Organization
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $phones = [];
        foreach (['phone1', 'phone2', 'phone3', 'phone4'] as $key) {
            if (!empty($row[$key])) {
                $phones[] = (string) $row[$key];
            }
        }
        return new self(
            name: (string) ($row['name'] ?? ''),
            address: (string) ($row['address'] ?? ''),
            site: (string) ($row['site'] ?? ''),
            email: (string) ($row['email'] ?? ''),
            phones: $phones,
            lat: isset($row['centerX']) ? (float) $row['centerX'] : null,
            lon: isset($row['centerY']) ? (float) $row['centerY'] : null,
        );
    }

    /**
     * @param list<string> $phones
     */
    public function __construct(
        public readonly string $name,
        public readonly string $address,
        public readonly string $site,
        public readonly string $email,
        public readonly array $phones,
        public readonly ?float $lat,
        public readonly ?float $lon,
    ) {
    }
}
