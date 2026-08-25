<?php

namespace Map\Map;

use Map\Domain\ClickQuery;
use Map\Domain\ClickResult;
use Map\Port\ClickLookupRepository;

final class ClickLookupService
{
    public function __construct(private ClickLookupRepository $lookup)
    {
    }

    public function lookup(ClickQuery $query): ClickResult
    {
        return $this->lookup->lookup($query);
    }
}
