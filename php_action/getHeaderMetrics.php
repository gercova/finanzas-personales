<?php
/**
 * php_action/getHeaderMetrics.php
 * Returns current user's monthly income and today's expenses for the SPA navbar.
 */
require_once __DIR__ . '/core.php';

header('Content-Type: application/json; charset=utf-8');

$userId  = (int)$_SESSION['userId'];
$metrics = fetchHeaderMetrics($connect, $userId);

echo json_encode(array_merge(['success' => true], $metrics), JSON_UNESCAPED_UNICODE);
