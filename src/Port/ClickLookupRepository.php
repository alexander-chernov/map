<?php

namespace Map\Port;

use Map\Domain\ClickQuery;
use Map\Domain\ClickResult;

interface ClickLookupRepository
{
    public function lookup(ClickQuery $query): ClickResult;
}
