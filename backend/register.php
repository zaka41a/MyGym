<?php
declare(strict_types=1);
ini_set('display_errors','1'); error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';

/* -------- Helpers -------- */
function detect_member_value(PDO $pdo): string {
  // Regarde le type de la colonne "role" pour savoir si l'ENUM est FR (MEMBRE) ou EN (MEMBER)
  $col = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
  $type = strtoupper((string)($col['Type'] ?? ''));
  // Si l'ENUM contient MEMBRE mais pas MEMBER -> utiliser MEMBRE
  if (strpos($type, 'MEMBRE') !== false && strpos($type, 'MEMBER') === false) {
    return 'MEMBRE';
  }
  // Sinon on part sur MEMBER (par défaut)
  return 'MEMBER';
}
function clean(string $s): string { return trim($s); }

/* -------- Seulement en POST -------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  header('Location: /MyGym/frontend/login/register.html?error=method'); exit;
}

/* -------- Récupération champs (adapte aux noms de ton formulaire) -------- */
$fullname = clean($_POST['fullname'] ?? '');
$username = clean($_POST['username'] ?? '');
$email    = clean($_POST['email'] ?? '');
$pass     = (string)($_POST['pass'] ?? '');
$pass2    = (string)($_POST['pass2'] ?? '');

/* -------- Normalisation minimum si seul "username" est fourni -------- */
if ($email === '' && str_contains($username, '@')) {
  $email = $username;
  $username = strstr($email, '@', true) ?: $email; // avant le @
}

/* -------- Validations -------- */
if ($username === '' || $email === '' || $pass === '') {
  header('Location: /MyGym/frontend/login/register.html?error=empty'); exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: /MyGym/frontend/login/register.html?error=email'); exit;
}
if ($pass2 !== '' && $pass !== $pass2) {
  header('Location: /MyGym/frontend/login/register.html?error=passmatch'); exit;
}

/* -------- Unicité -------- */
$st = $pdo->prepare("SELECT 1 FROM users WHERE username=:u OR email=:e LIMIT 1");
$st->execute([':u'=>$username, ':e'=>$email]);
if ($st->fetchColumn()) {
  header('Location: /MyGym/frontend/login/register.html?error=exists'); exit;
}

/* -------- Prépare insertion -------- */
$roleMember = detect_member_value($pdo);              // -> MEMBER ou MEMBRE selon ta BDD
$hash       = password_hash($pass, PASSWORD_DEFAULT);
$fullname   = ($fullname !== '') ? $fullname : $username;

$ins = $pdo->prepare("
  INSERT INTO users (fullname, username, email, role, password_hash, is_active)
  VALUES (:f, :u, :e, :r, :ph, 1)
");

try {
  $ins->execute([
    ':f'  => $fullname,
    ':u'  => $username,
    ':e'  => $email,
    ':r'  => $roleMember,   // <<< évite l’erreur “Data truncated…”
    ':ph' => $hash,
  ]);
} catch (PDOException $e) {
  // Renvoie proprement une erreur lisible
  header('Location: /MyGym/frontend/login/register.html?error=sql'); exit;
}

/* -------- Récupère l’utilisateur et connecte -------- */
$uid = (int)$pdo->lastInsertId();
$u = $pdo->prepare("SELECT id, fullname, email, username, role, is_active FROM users WHERE id=:id");
$u->execute([':id'=>$uid]);
$user = $u->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header('Location: /MyGym/frontend/login/login.html?error=created_but_login'); exit;
}

/* -------- Stocke en session (format attendu par le site) -------- */
$_SESSION['user'] = [
  'id'       => (int)$user['id'],
  'fullname' => (string)$user['fullname'],
  'email'    => (string)$user['email'],
  'username' => (string)$user['username'],
  'role'     => normalize_role($user['role'] ?? ''), // MEMBRE -> MEMBER
];
$_SESSION['user_id']  = (int)$user['id'];
$_SESSION['username'] = (string)$user['username'];
$_SESSION['role']     = normalize_role($user['role'] ?? '');

session_regenerate_id(true);

/* -------- Redirection par rôle -------- */
$role = $_SESSION['role'];
if ($role === 'ADMIN') {
  header('Location: /MyGym/admin/index.php');
} elseif ($role === 'COACH') {
  header('Location: /MyGym/coach/index.php');
} else {
  header('Location: /MyGym/member/index.php');
}
exit;
