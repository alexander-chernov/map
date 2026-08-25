<?php

namespace Map\Domain;

final class AddressScope
{
    /**
     * @param list<int> $streetIds
     * @param list<string> $houseNumbers
     */
    public function __construct(
        public readonly array $streetIds,
        public readonly array $houseNumbers,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->streetIds === [] && $this->houseNumbers === [];
    }
}
