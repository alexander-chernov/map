<?php

namespace Map\Domain;

final class MapPolyline
{
    /**
     * @param list<list<array{0: float, 1: float}>> $paths
     */
    public function __construct(
        public readonly string $name,
        public readonly string $color,
        public readonly array $paths,
    ) {
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'color' => $this->color,
            'paths' => $this->paths,
        ];
    }
}
