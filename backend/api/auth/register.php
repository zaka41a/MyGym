<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../helpers.php';

api_require_method('POST');

$payload = read_json_body();
$fullName = trim((string)($payload['fullName'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$password = (string)($payload['password'] ?? '');
$goal = trim((string)($payload['goal'] ?? ''));

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_error(400, 'A valid email is required.', ['code' => 'invalid_email']);
}

if (strlen($password) < 6) {
  json_error(400, 'Password must be at least 6 characters.', ['code' => 'weak_password']);
}

if ($fullName === '') {
  $fullName = strstr($email, '@', true) ?: $email;
}

/** @var PDO $pdo */
global $pdo;

try {
  $pdo->beginTransaction();

  $roleMember = detect_member_role($pdo);
  $desiredUsername = $fullName !== '' ? $fullName : ($email !== '' ? strstr($email, '@', true) : 'member');
  $username = ensure_unique_username($pdo, (string)$desiredUsername);
  $hash = password_hash($password, PASSWORD_DEFAULT);

  $insert = $pdo->prepare(
    "INSERT INTO users (fullname, username, email, role, password_hash, is_active)
     VALUES (:fullname, :username, :email, :role, :hash, 1)"
  );
  $insert->execute([
    ':fullname' => $fullName,
    ':username' => $username,
    ':email' => $email,
    ':role' => $roleMember,
    ':hash' => $hash,
  ]);

  $userId = (int)$pdo->lastInsertId();

  $select = $pdo->prepare(
    "SELECT id, fullname, username, email, role, is_active FROM users WHERE id=:id LIMIT 1"
  );
  $select->execute([':id' => $userId]);
  $user = $select->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    $pdo->rollBack();
    json_error(500, 'User created but could not be retrieved.', ['code' => 'register_fetch_failed']);
  }

  login_store_session($user);
  $pdo->commit();

  if ($goal !== '') {
    try {
      store_contact_request($pdo, [
        'full_name' => $fullName,
        'email' => $email,
        'phone' => null,
        'goal' => $goal,
      ]);
    } catch (Throwable $e) {
      error_log('[contact_request_error] ' . $e->getMessage());
    }
  }

  json_response(200, [
    'status' => 'ok',
    'user' => api_user_payload($user)
  ]);
} catch (Throwable $error) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }

  if ($error instanceof PDOException) {
    $info = $error->errorInfo;
    if (is_array($info) && isset($info[1]) && (int)$info[1] === 1062) {
      json_error(409, 'Email already exists.', ['code' => 'email_exists']);
    }
  }

  json_error(500, 'Unable to register at this time.', [
    'code' => 'register_failed',
    'detail' => $error->getMessage(),
  ]);
}
