<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers.php';

api_require_method('POST');

$payload = read_json_body();
$identifier = trim((string)($payload['identifier'] ?? ''));
$password = (string)($payload['password'] ?? '');

if ($identifier === '' || $password === '') {
  json_error(400, 'Email/username and password are required.', ['code' => 'validation_error']);
}

/** @var PDO $pdo */
global $pdo;

$stmt = $pdo->prepare(
  "SELECT id, fullname, username, email, password_hash, role, is_active
     FROM users
    WHERE username = :username OR email = :email
    LIMIT 1"
);
$stmt->execute([
  ':username' => $identifier,
  ':email' => $identifier,
]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  json_error(401, 'Invalid credentials.', ['code' => 'invalid_credentials']);
}

if (isset($user['is_active']) && (int)$user['is_active'] === 0) {
  json_error(403, 'Account is disabled. Please contact support.', ['code' => 'inactive']);
}

if (!password_verify($password, (string)($user['password_hash'] ?? ''))) {
  json_error(401, 'Invalid credentials.', ['code' => 'invalid_credentials']);
}

$user['role'] = normalize_role($user['role'] ?? 'MEMBER');
login_store_session($user);
unset($user['password_hash']);

json_response(200, [
  'status' => 'ok',
  'user' => api_user_payload($user)
]);
