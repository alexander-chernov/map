<?php

namespace Map\Domain;

final class TransitStop
{
    /**
     * @param list<string> $routes
     */
    public function __construct(
        public readonly string $fid,
        public readonly string $name,
        public readonly array $routes,
        public readonly ?float $lat,
        public readonly ?float $lon,
    ) {
    }
}
