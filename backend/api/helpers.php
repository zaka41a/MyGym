<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

function api_user_payload(array $user): array {
  $fullName = (string)($user['fullName'] ?? $user['fullname'] ?? '');
  $email = (string)($user['email'] ?? '');
  $username = (string)($user['username'] ?? '');
  $role = normalize_role((string)($user['role'] ?? 'MEMBER'));

  return [
    'id' => (int)($user['id'] ?? 0),
    'fullName' => $fullName,
    'email' => $email,
    'username' => $username,
    'role' => $role,
    'membership' => $user['membership'] ?? null,
    'goal' => $user['goal'] ?? null,
  ];
}

function detect_member_role(PDO $pdo): string {
  $column = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
  $type = strtoupper((string)($column['Type'] ?? ''));
  if (strpos($type, 'MEMBRE') !== false && strpos($type, 'MEMBER') === false) {
    return 'MEMBRE';
  }
  return 'MEMBER';
}

function ensure_unique_username(PDO $pdo, string $preferred): string {
  $base = strtolower(preg_replace('/[^a-z0-9_]/i', '', $preferred));
  if ($base === '') {
    $base = 'member';
  }
  $base = substr($base, 0, 24);
  if (strlen($base) < 3) {
    $base = str_pad($base, 3, 'x');
  }

  $candidate = $base;
  $suffix = 1;
  $stmt = $pdo->prepare('SELECT 1 FROM users WHERE username = :u LIMIT 1');

  while (true) {
    $stmt->execute([':u' => $candidate]);
    if (!$stmt->fetchColumn()) {
      return $candidate;
    }
    $candidate = $base . $suffix;
    $suffix++;
    if ($suffix > 9999) {
      throw new RuntimeException('Unable to generate a unique username');
    }
  }
}

function store_contact_request(PDO $pdo, array $data): void {
  $sqlCreate = <<<SQL
    CREATE TABLE IF NOT EXISTS contact_requests (
      id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
      full_name VARCHAR(120) NOT NULL,
      email VARCHAR(190) NOT NULL,
      phone VARCHAR(40) NULL,
      goal TEXT NOT NULL,
      created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
SQL;

  $pdo->exec($sqlCreate);

  $stmt = $pdo->prepare(
    "INSERT INTO contact_requests (full_name, email, phone, goal) VALUES (:full_name, :email, :phone, :goal)"
  );
  $stmt->execute([
    ':full_name' => $data['full_name'],
    ':email' => $data['email'],
    ':phone' => $data['phone'],
    ':goal' => $data['goal'],
  ]);
}
