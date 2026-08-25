<?php

namespace Map\Port;

use Map\Domain\PagedResult;
use Map\Search\SearchKind;

interface ResultPresenter
{
    public function render(PagedResult $result, SearchKind $kind): string;
}
