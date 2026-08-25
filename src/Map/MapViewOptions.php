<?php

namespace Map\Map;

final class MapViewOptions
{
    public function __construct(
        public readonly string $mapType = 'yandex#map',
        public readonly bool $admin = false,
        public readonly bool $lightbox = true,
        public readonly int $defaultZoom = 15,
    ) {
    }

    public static function public(): self
    {
        return new self();
    }

    public static function admin(): self
    {
        return new self('yandex#hybrid', true, false, 12);
    }
}
