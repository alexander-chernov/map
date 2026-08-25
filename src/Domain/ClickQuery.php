<?php

namespace Map\Domain;

final class ClickQuery
{
    public function __construct(
        public readonly ?GeoPoint $point,
        public readonly string $locality,
        public readonly string $street,
        public readonly string $house,
        public readonly string $district,
    ) {
    }

    /**
     * @param array<string, mixed> $get
     */
    public static function fromGet(array $get, string $defaultLocality): self
    {
        $geo = null;
        if (!empty($get['c'])) {
            $decoded = json_decode((string) $get['c']);
            if (is_object($decoded)) {
                $geo = $decoded;
            }
        }

        $district = isset($geo->district) ? trim((string) $geo->district) : '';
        $district = trim(str_replace(['микрорайон', 'район'], '', $district));

        return new self(
            point: GeoPoint::fromPair(isset($get['a']) ? (string) $get['a'] : null),
            locality: isset($geo->locality) && trim((string) $geo->locality) !== ''
                ? trim((string) $geo->locality)
                : $defaultLocality,
            street: isset($geo->street) ? trim((string) $geo->street) : '',
            house: isset($geo->house) ? trim((string) $geo->house) : '',
            district: $district,
        );
    }

    public function houseNumber(): string
    {
        if ($this->house === '') {
            return '';
        }
        $parts = explode(',', $this->house);
        return trim((string) end($parts));
    }
}
