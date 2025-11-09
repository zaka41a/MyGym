<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  header('Location: /MyGym/login.php?error=login_required'); exit;
}

$identifier = trim($_POST['username'] ?? ''); // username OU email
$pass       = (string)($_POST['pass'] ?? '');

if ($identifier === '' || $pass === '') {
  header('Location: /MyGym/login.php?error=empty'); exit;
}

$sql = "SELECT id, fullname, username, email, password_hash, role, is_active
        FROM users
        WHERE username = :u OR email = :e
        LIMIT 1";
$st = $pdo->prepare($sql);
$st->execute([':u'=>$identifier, ':e'=>$identifier]); // <-- deux paramètres distincts

$u = $st->fetch(PDO::FETCH_ASSOC);

if (!$u) {
  header('Location: /MyGym/login.php?error=notfound'); exit;
}
if (isset($u['is_active']) && (int)$u['is_active'] === 0) {
  header('Location: /MyGym/login.php?error=disabled'); exit;
}
if (!password_verify($pass, (string)$u['password_hash'])) {
  header('Location: /MyGym/login.php?error=wrongpass'); exit;
}

$u['role'] = normalize_role($u['role'] ?? 'MEMBER');
login_store_session($u);

if ($u['role'] === 'ADMIN') {
  header('Location: /MyGym/admin/index.php');
} elseif ($u['role'] === 'COACH') {
  header('Location: /MyGym/coach/index.php');
} else {
  header('Location: /MyGym/member/index.php');
}
exit;
