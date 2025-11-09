<?php
declare(strict_types=1);

$allowedOrigins = [
  'http://localhost',
  'http://localhost:5173',
  'http://127.0.0.1',
  'http://127.0.0.1:5173',
];

if (isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins, true)) {
  header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
  header('Access-Control-Allow-Credentials: true');
}

header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
  http_response_code(204);
  exit;
}

function json_response(int $status, array $payload = []): void {
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

function json_error(int $status, string $message, array $extra = []): void {
  $payload = array_merge([
    'status' => 'error',
    'message' => $message,
  ], $extra);
  json_response($status, $payload);
}

function read_json_body(): array {
  $raw = file_get_contents('php://input');
  if ($raw === false || $raw === '') {
    return [];
  }
  $data = json_decode($raw, true);
  if (!is_array($data)) {
    json_error(400, 'Invalid JSON payload.');
  }
  return $data;
}

function api_require_method(string ...$methods): void {
  $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
  $allowed = array_map('strtoupper', $methods);
  if (!in_array($method, $allowed, true)) {
    header('Allow: ' . implode(', ', $allowed));
    json_error(405, 'Method not allowed.');
  }
}
