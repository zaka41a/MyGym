<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../subscriptions.php';

api_require_method('GET');

$sessionUser = currentUser();
if (!$sessionUser) {
  json_error(401, 'Not authenticated.', ['code' => 'unauthenticated']);
}

/** @var PDO $pdo */
global $pdo;

$stmt = $pdo->prepare(
  "SELECT id, fullname, username, email, role FROM users WHERE id = :id LIMIT 1"
);
$stmt->execute([':id' => (int)$sessionUser['id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  json_error(404, 'Account not found anymore.', ['code' => 'user_missing']);
}

$subscription = null;
try {
  $subscription = get_current_active_with_plan($pdo, (int)$user['id']);
} catch (Throwable $e) {
  $subscription = null;
}

$payload = api_user_payload(array_merge($user, [
  'membership' => $subscription['plan_name'] ?? null,
]));

json_response(200, [
  'status' => 'ok',
  'user' => $payload
]);
