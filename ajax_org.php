<?php
require_once __DIR__ . '/config.php';

$page = isset($_GET['page']) ? (int) $_GET['page'] : 0;
$name = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
$rows = $app->autocomplete()->suggestOrganizations($name, $page);
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
