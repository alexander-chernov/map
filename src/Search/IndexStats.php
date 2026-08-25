<?php

namespace Map\Search;

final class IndexStats
{
    public function __construct(
        public readonly int $organizations,
        public readonly int $orgTerms,
        public readonly int $addresses,
        public readonly int $addressTerms,
        public readonly int $coordsUpdated,
        public readonly int $geocoded,
    ) {
    }

    public function summary(): string
    {
        return sprintf(
            "Indexed %d organizations (%d terms), %d addresses (%d terms). Coords updated: %d, geocoded: %d.\n",
            $this->organizations,
            $this->orgTerms,
            $this->addresses,
            $this->addressTerms,
            $this->coordsUpdated,
            $this->geocoded
        );
    }
}
