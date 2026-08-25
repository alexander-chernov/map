<?php

namespace Map\Domain;

final class Address
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $house = (string) ($row['house'] ?? $row['k_shn_house_num'] ?? '');
        return new self(
            street: (string) ($row['street'] ?? ''),
            house: $house,
            district: (string) ($row['district'] ?? ''),
            massive: (string) ($row['massive'] ?? ''),
            lat: isset($row['centerX']) ? (float) $row['centerX'] : null,
            lon: isset($row['centerY']) ? (float) $row['centerY'] : null,
        );
    }

    public function __construct(
        public readonly string $street,
        public readonly string $house,
        public readonly string $district,
        public readonly string $massive,
        public readonly ?float $lat,
        public readonly ?float $lon,
    ) {
    }
}
