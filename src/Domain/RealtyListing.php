<?php

namespace Map\Domain;

final class RealtyListing
{
    /**
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            dealType: (int) ($row['k_isf_deal_type'] ?? 0),
            rooms: (string) ($row['k_isf_rooms'] ?? ''),
            typeName: (string) ($row['k_isft_name'] ?? ''),
            description: (string) ($row['k_isf_description'] ?? ''),
            price: (string) ($row['k_isf_price'] ?? ''),
            contactName: (string) ($row['k_isf_contact_name'] ?? ''),
            contacts: (string) ($row['k_isf_contacts'] ?? ''),
            registeredAt: (string) ($row['k_isf_registration_date'] ?? ''),
            street: (string) ($row['street'] ?? ''),
            house: (string) ($row['house'] ?? ''),
            district: (string) ($row['district'] ?? ''),
            massive: (string) ($row['massive'] ?? ''),
            lat: isset($row['centerX']) ? (float) $row['centerX'] : null,
            lon: isset($row['centerY']) ? (float) $row['centerY'] : null,
        );
    }

    public function __construct(
        public readonly int $dealType,
        public readonly string $rooms,
        public readonly string $typeName,
        public readonly string $description,
        public readonly string $price,
        public readonly string $contactName,
        public readonly string $contacts,
        public readonly string $registeredAt,
        public readonly string $street,
        public readonly string $house,
        public readonly string $district,
        public readonly string $massive,
        public readonly ?float $lat,
        public readonly ?float $lon,
    ) {
    }

    public function dealLabel(): string
    {
        return $this->dealType === 1 ? 'Продается' : 'Сдается';
    }
}
