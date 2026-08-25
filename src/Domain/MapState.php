<?php

namespace Map\Domain;

final class MapState
{
    public const DEFAULT_CENTER = [56.496581, 84.963502];

    /**
     * @param array{0: float, 1: float} $center
     * @param list<MapPolyline> $polylines
     * @param list<MapPin> $pins
     * @param list<array{lat: float, lon: float, html: string}> $banners
     * @param array{0: float, 1: float}|null $point
     */
    public function __construct(
        public readonly array $center,
        public readonly int $zoom,
        public readonly int $width,
        public readonly bool $scrollZoom,
        public readonly string $mapType,
        public readonly bool $contextMenuHint,
        public readonly array $polylines,
        public readonly array $pins,
        public readonly array $banners,
        public readonly ?array $point,
        public readonly bool $lightbox,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'center' => $this->center,
            'zoom' => $this->zoom,
            'width' => $this->width,
            'scrollZoom' => $this->scrollZoom,
            'mapType' => $this->mapType,
            'contextMenuHint' => $this->contextMenuHint,
            'polylines' => array_map(static fn (MapPolyline $line) => $line->toArray(), $this->polylines),
            'pins' => array_map(static fn (MapPin $pin) => $pin->toArray(), $this->pins),
            'banners' => $this->banners,
            'point' => $this->point,
            'lightbox' => $this->lightbox,
        ];
    }
}
