<?php

namespace Map\Domain;

final class ClickResult
{
    public function __construct(
        public readonly string $link = '',
        public readonly string $district = '',
        public readonly string $massive = '',
        public readonly int $districtCount = 0,
        public readonly int $massiveCount = 0,
        public readonly int $streetCount = 0,
        public readonly int $addressCount = 0,
        public readonly int $realtyCount = 0,
        public readonly int $orgCount = 0,
        public readonly string $house = '',
        public readonly string $foto = '',
        public readonly ?float $lat = null,
        public readonly ?float $lon = null,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'link' => $this->link,
            'center' => $this->lat !== null && $this->lon !== null ? $this->lat . ',' . $this->lon : '',
            'X' => $this->lat,
            'Y' => $this->lon,
            'district' => $this->district,
            'massive' => $this->massive,
            'district_count' => $this->districtCount,
            'massive_count' => $this->massiveCount,
            'street_count' => $this->streetCount,
            'count' => $this->realtyCount,
            'count_f' => $this->orgCount,
            'count_address' => $this->addressCount,
            'house' => $this->house,
            'foto' => $this->foto,
        ];
    }
}
