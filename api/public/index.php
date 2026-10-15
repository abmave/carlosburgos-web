<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $path === '/api/health') {
    echo json_encode(['status' => 'ok', 'service' => 'carlosburgos-api', 'database' => 'not_checked']);
    exit;
}
http_response_code(404);
echo json_encode(['error' => 'not_found']);
