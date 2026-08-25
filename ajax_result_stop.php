<?php
require_once __DIR__ . '/config.php';

use Map\Domain\SearchQuery;
use Map\Search\SearchKind;

$query = SearchQuery::fromGet($_GET, $perPageAjax, $app->tokenizer());
echo $app->htmlList()->render($app->search()->stops($query), SearchKind::Stop);
