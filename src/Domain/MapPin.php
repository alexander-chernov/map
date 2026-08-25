<?php

namespace Map\Domain;

final class MapPin
{
    public const GROUP_ADDRESS = 'address';
    public const GROUP_FIRM = 'firm';
    public const GROUP_SEARCH = 'search';
    public const GROUP_STREET = 'street';
    public const GROUP_STOP = 'stop';

    public function __construct(
        public readonly string $group,
        public readonly string $preset,
        public readonly float $lat,
        public readonly float $lon,
        public readonly string $header,
        public readonly string $body,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'group' => $this->group,
            'preset' => $this->preset,
            'lat' => $this->lat,
            'lon' => $this->lon,
            'header' => $this->header,
            'body' => $this->body,
        ];
    }
}
