<?php
declare(strict_types=1);

/**
 * Auth pour MyGym (sessions + rôles)
 * - $_SESSION['user'] = ['id','fullname','email','username','role']
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? '') === '443');
  session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => $https,
    'httponly' => true,
    'samesite' => 'Lax',
  ]);
  session_start();
}

function normalize_role(?string $role): string {
  $r = strtoupper(trim((string)$role));
  return $r === 'MEMBRE' ? 'MEMBER' : $r;
}

function currentUser(): ?array { return $_SESSION['user'] ?? null; }
function isLoggedIn(): bool { return !empty($_SESSION['user']['id']); }

function requireLogin(?string $redirectTo = null): void {
  if (!isLoggedIn()) {
    if ($redirectTo) { header('Location: '.$redirectTo); exit; }
    http_response_code(401); exit('Accès refusé.');
  }
}

function requireRole(string ...$roles): void {
  requireLogin();
  $uRole = normalize_role($_SESSION['user']['role'] ?? '');
  $asked = array_map(fn($r) => normalize_role($r), $roles);
  if (!in_array($uRole, $asked, true)) { http_response_code(403); exit('Accès refusé.'); }
}

/** Enregistre proprement la session après login */
function login_store_session(array $u): void {
  $_SESSION['user'] = [
    'id'       => (int)$u['id'],
    'fullname' => (string)$u['fullname'],
    'email'    => (string)$u['email'],
    'username' => (string)$u['username'],
    'role'     => normalize_role($u['role'] ?? 'MEMBER'),
  ];
  // Compat éventuelle
  $_SESSION['user_id']  = (int)$u['id'];
  $_SESSION['username'] = (string)$u['username'];
  $_SESSION['role']     = normalize_role($u['role'] ?? 'MEMBER');

  session_regenerate_id(true);
}

function logout(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
  }
  session_destroy();
}
