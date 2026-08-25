<?php
require_once __DIR__ . '/config.php';

$page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
$term = isset($_GET['term']) ? trim((string) $_GET['term']) : '';
$words = $app->autocomplete()->suggestTerms($term, $page);
echo json_encode(array_values($words), JSON_UNESCAPED_UNICODE);
