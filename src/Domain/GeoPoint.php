<?php

namespace Map\Domain;

final class GeoPoint
{
    public function __construct(
        public readonly ?float $lat,
        public readonly ?float $lon,
    ) {
    }

    public static function fromPair(?string $raw): ?self
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        $parts = explode(',', $raw);
        if (count($parts) < 2) {
            return null;
        }
        return new self(
            round((float) $parts[0], 6),
            round((float) $parts[1], 6),
        );
    }

    public function asQuery(): string
    {
        if ($this->lat === null || $this->lon === null) {
            return '';
        }
        return $this->lat . ',' . $this->lon;
    }
}
