<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/helpers.php';

api_require_method('POST');

$payload = read_json_body();
$fullName = trim((string)($payload['fullName'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$goal = trim((string)($payload['goal'] ?? ''));
$phone = trim((string)($payload['phone'] ?? ''));

if ($fullName === '' || strlen($fullName) < 2) {
  json_error(400, 'Full name is required.', ['code' => 'invalid_fullname']);
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_error(400, 'A valid email is required.', ['code' => 'invalid_email']);
}

if ($goal === '' || strlen($goal) < 3) {
  json_error(400, 'Let us know about your performance goal.', ['code' => 'invalid_goal']);
}

if ($phone !== '' && !preg_match('/^\+?[0-9\s-]{7,20}$/', $phone)) {
  json_error(400, 'Provide a valid phone number.', ['code' => 'invalid_phone']);
}

/** @var PDO $pdo */
global $pdo;

try {
  store_contact_request($pdo, [
    'full_name' => $fullName,
    'email' => $email,
    'phone' => $phone !== '' ? $phone : null,
    'goal' => $goal,
  ]);

  json_response(200, [
    'status' => 'ok'
  ]);
} catch (Throwable $error) {
  json_error(500, 'Unable to submit your request now. Please try again later.', [
    'code' => 'contact_failed',
    'detail' => $error->getMessage(),
  ]);
}
