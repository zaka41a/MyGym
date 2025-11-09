<?php
declare(strict_types=1);

/**
 * Admin — User management (with Photo column)
 */

require_once __DIR__ . '/../backend/auth.php';
requireRole('ADMIN');

require_once __DIR__ . '/../backend/db.php';

// CSRF token
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf'];

// Helper functions
function sanitize_email(string $e): string { return filter_var(trim($e), FILTER_SANITIZE_EMAIL) ?: ''; }
function sanitize_text(string $t): string  { return trim($t); }

function role_for_db(PDO $pdo, string $roleInput): string {
  static $expectsFr = null;
  $r = strtoupper(trim($roleInput));
  if ($r === 'MEMBRE') $r = 'MEMBER';
  if (!in_array($r, ['ADMIN','COACH','MEMBER'], true)) $r = 'MEMBER';

  if ($expectsFr === null) {
    $col  = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetch(PDO::FETCH_ASSOC);
    $type = strtoupper((string)($col['Type'] ?? ''));
    $expectsFr = (strpos($type, 'MEMBRE') !== false) && (strpos($type, 'MEMBER') === false);
  }
  if ($expectsFr && $r === 'MEMBER') $r = 'MEMBRE';
  return $r;
}

function role_for_ui(string $dbRole): string {
  $r = strtoupper(trim($dbRole));
  if ($r === 'MEMBRE') $r = 'MEMBER';
  if (!in_array($r, ['ADMIN','COACH','MEMBER'], true)) $r = 'MEMBER';
  return $r;
}

function avatar_url_for_user(array $u): ?string {
  $rootFS      = dirname(__DIR__);
  $uploadFS    = $rootFS . '/uploads/avatars';
  $uploadWeb   = '/MyGym/uploads/avatars';

  // Check avatar column first
  if (!empty($u['avatar'])) {
    return $uploadWeb . '/' . basename((string)$u['avatar']) . '?t=' . time();
  }

  // Check for user_ID.ext files
  $id = (int)($u['id'] ?? 0);
  if ($id > 0) {
    foreach (['jpg','png','webp','jpeg'] as $ext) {
      $p = $uploadFS . "/user_{$id}.{$ext}";
      if (is_file($p)) {
        return $uploadWeb . "/user_{$id}.{$ext}?t=" . time();
      }
    }
  }

  // Return placeholder if no avatar found
  return 'https://via.placeholder.com/40x40?text=U';
}

// POST actions
$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

$hasAvatarCol = (bool)$pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'")->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
      $fullname = sanitize_text($_POST['fullname'] ?? '');
      $username = sanitize_text($_POST['username'] ?? '');
      $email    = sanitize_email($_POST['email'] ?? '');
      $roleUi   = sanitize_text($_POST['role'] ?? 'MEMBER');
      $password = (string)($_POST['password'] ?? '');

      if ($fullname==='' || $username==='' || $email==='' || $password==='') {
        throw new RuntimeException('All fields are required.');
      }
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid email.');
      }

      $st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
      $st->execute([$username, $email]);
      if ((int)$st->fetchColumn() > 0) {
        throw new RuntimeException('Username or email already in use.');
      }

      $roleDb = role_for_db($pdo, $roleUi);
      $hash   = password_hash($password, PASSWORD_DEFAULT);

      $stmt = $pdo->prepare("
        INSERT INTO users (fullname, username, email, role, password_hash, is_active)
        VALUES (:f,:u,:e,:r,:ph,1)
      ");
      $stmt->execute([':f'=>$fullname, ':u'=>$username, ':e'=>$email, ':r'=>$roleDb, ':ph'=>$hash]);

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('User created.'));
      exit;
    }

    if ($action === 'update') {
      $id       = (int)($_POST['id'] ?? 0);
      $fullname = sanitize_text($_POST['fullname'] ?? '');
      $username = sanitize_text($_POST['username'] ?? '');
      $email    = sanitize_email($_POST['email'] ?? '');
      $roleUi   = sanitize_text($_POST['role'] ?? 'MEMBER');
      $password = (string)($_POST['password'] ?? '');

      if ($id<=0 || $fullname==='' || $username==='' || $email==='') {
        throw new RuntimeException('Missing fields.');
      }
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Invalid email.');
      }

      $roleDb = role_for_db($pdo, $roleUi);

      if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
          UPDATE users
             SET fullname=:f, username=:u, email=:e, role=:r, password_hash=:ph
           WHERE id=:id
        ");
        $stmt->execute([':f'=>$fullname, ':u'=>$username, ':e'=>$email, ':r'=>$roleDb, ':ph'=>$hash, ':id'=>$id]);
      } else {
        $stmt = $pdo->prepare("
          UPDATE users
             SET fullname=:f, username=:u, email=:e, role=:r
           WHERE id=:id
        ");
        $stmt->execute([':f'=>$fullname, ':u'=>$username, ':e'=>$email, ':r'=>$roleDb, ':id'=>$id]);
      }

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('User updated.'));
      exit;
    }

    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id <= 0) throw new RuntimeException('Invalid ID.');

      $stmt = $pdo->prepare("DELETE FROM users WHERE id=:id");
      $stmt->execute([':id'=>$id]);

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('User deleted.'));
      exit;
    }

    throw new RuntimeException('Unknown action.');
  } catch (Throwable $e) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='.urlencode($e->getMessage()));
    exit;
  }
}

// Load users
if ($hasAvatarCol) {
  $stmt = $pdo->query("SELECT id, fullname, username, email, role, is_active, avatar FROM users ORDER BY id DESC");
} else {
  $stmt = $pdo->query("SELECT id, fullname, username, email, role, is_active, NULL AS avatar FROM users ORDER BY id DESC");
}
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$editUser = null;
if (isset($_GET['edit'])) {
  $eid = (int)$_GET['edit'];
  foreach ($users as $u) {
    if ((int)$u['id'] === $eid) { $editUser = $u; break; }
  }
}

// KPIs
$admins = $coachs = $members = 0;
foreach ($users as $u) {
  $ru = role_for_ui((string)$u['role']);
  if ($ru==='ADMIN')  $admins++;
  if ($ru==='COACH')  $coachs++;
  if ($ru==='MEMBER') $members++;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Users</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #0a0a0a;
      color: #f5f7fb;
      min-height: 100vh;
      background: radial-gradient(55% 80% at 50% 0%, rgba(220, 38, 38, 0.22), transparent 65%),
                  radial-gradient(60% 90% at 75% 15%, rgba(127, 29, 29, 0.18), transparent 70%),
                  linear-gradient(180deg, rgba(10, 10, 10, 0.98) 0%, rgba(10, 10, 10, 1) 100%);
    }

    .container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar - same as index.php */
    .sidebar {
      width: 280px;
      background: rgba(17, 17, 17, 0.95);
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      padding: 2rem 1.5rem;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 3rem;
    }

    .logo-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      box-shadow: 0 10px 30px rgba(220,38,38,0.4);
    }

    .logo-text h1 {
      font-size: 1.5rem;
      font-weight: 800;
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .logo-text p {
      font-size: 0.75rem;
      color: #9ca3af;
      text-transform: uppercase;
      letter-spacing: 0.1em;
    }

    .nav-menu {
      list-style: none;
      margin: 2rem 0;
    }

    .nav-item {
      margin-bottom: 0.5rem;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      color: #9ca3af;
      text-decoration: none;
      border-radius: 12px;
      transition: all 0.3s;
      font-weight: 500;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.05);
      color: #fff;
    }

    .nav-link.active {
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
      color: #fff;
      box-shadow: 0 4px 20px rgba(220,38,38,0.3);
    }

    .nav-link ion-icon {
      font-size: 1.25rem;
    }

    .logout-btn {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      color: #9ca3af;
      text-decoration: none;
      transition: all 0.3s;
      font-weight: 500;
      margin-top: 2rem;
    }

    .logout-btn:hover {
      background: rgba(220, 38, 38, 0.2);
      color: #fff;
      border-color: #dc2626;
    }

    /* Main Content */
    .main-content {
      margin-left: 280px;
      flex: 1;
      padding: 2rem;
    }

    .header {
      margin-bottom: 2rem;
    }

    .header h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 1.5rem;
      margin-bottom: 2rem;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 1.5rem;
      transition: all 0.3s;
    }

    .stat-card:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(220, 38, 38, 0.4);
      transform: translateY(-2px);
    }

    .stat-value {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
    }

    .stat-label {
      color: #9ca3af;
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Section/Panel */
    .section {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }

    /* Alerts */
    .alert {
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
    }

    .alert-success {
      background: rgba(16, 185, 129, 0.2);
      border: 1px solid rgba(16, 185, 129, 0.4);
      color: #10b981;
    }

    .alert-error {
      background: rgba(239, 68, 68, 0.2);
      border: 1px solid rgba(239, 68, 68, 0.4);
      color: #ef4444;
    }

    /* Form */
    .form-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
    }

    .form-grid .full {
      grid-column: 1 / -1;
    }

    label {
      display: block;
      font-weight: 600;
      font-size: 0.875rem;
      color: #9ca3af;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    input, select {
      width: 100%;
      padding: 0.75rem;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 8px;
      color: #fff;
      outline: none;
      transition: all 0.3s;
    }

    input:focus, select:focus {
      border-color: #dc2626;
      background: rgba(255, 255, 255, 0.08);
    }

    /* Buttons */
    .btn {
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      color: #fff;
      border: 0;
      border-radius: 8px;
      padding: 0.65rem 1.2rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
    }

    .btn-ghost {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
    }

    .btn-ghost:hover {
      background: rgba(255, 255, 255, 0.05);
      border-color: #dc2626;
    }

    .btn-sm {
      padding: 0.4rem 0.8rem;
      font-size: 0.85rem;
    }

    /* Table */
    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead td {
      font-weight: 600;
      color: #9ca3af;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    tbody tr {
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      transition: all 0.2s;
    }

    tbody tr:hover {
      background: rgba(220, 38, 38, 0.05);
    }

    td {
      padding: 1rem 0.75rem;
      vertical-align: middle;
    }

    .thumb {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid rgba(255, 255, 255, 0.2);
    }

    .no-avatar {
      display: inline-block;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.05);
      border: 1px dashed rgba(255, 255, 255, 0.2);
    }

    .badge {
      padding: 0.375rem 0.875rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: inline-block;
    }

    .badge-admin {
      background: rgba(220, 38, 38, 0.2);
      color: #dc2626;
    }

    .badge-coach {
      background: rgba(255, 255, 255, 0.2);
      color: #fff;
    }

    .badge-member {
      background: rgba(156, 163, 175, 0.2);
      color: #9ca3af;
    }

    @media (max-width: 991px) {
      .sidebar {
        width: 0;
        opacity: 0;
      }
      .main-content {
        margin-left: 0;
      }
      .stats-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <svg width="180" height="50" viewBox="0 0 220 60" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g transform="translate(5, 15)">
            <rect x="0" y="5" width="6" height="20" rx="1.5" fill="url(#gradient1)"/>
            <rect x="6" y="8" width="2" height="14" rx="0.5" fill="#7f1d1d"/>
            <rect x="8" y="12" width="34" height="6" rx="3" fill="url(#gradient1)"/>
            <rect x="42" y="8" width="2" height="14" rx="0.5" fill="#7f1d1d"/>
            <rect x="44" y="5" width="6" height="20" rx="1.5" fill="url(#gradient1)"/>
          </g>
          <text x="65" y="32" font-family="system-ui, -apple-system, 'Segoe UI', Arial, sans-serif" font-size="28" font-weight="900" fill="url(#textGradient)" letter-spacing="2">MyGym</text>
          <text x="65" y="46" font-family="system-ui, -apple-system, 'Segoe UI', Arial, sans-serif" font-size="10" font-weight="600" fill="#9ca3af" letter-spacing="3">PERFORMANCE CLUB</text>
          <defs>
            <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#dc2626"/>
              <stop offset="100%" stop-color="#991b1b"/>
            </linearGradient>
            <linearGradient id="textGradient" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#dc2626"/>
              <stop offset="50%" stop-color="#ef4444"/>
              <stop offset="100%" stop-color="#dc2626"/>
            </linearGradient>
          </defs>
        </svg>
      </div>

      <nav>
        <ul class="nav-menu">
          <li class="nav-item">
            <a href="index.php" class="nav-link">
              <ion-icon name="grid"></ion-icon>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="users.php" class="nav-link active">
              <ion-icon name="people"></ion-icon>
              <span>Users</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="courses.php" class="nav-link">
              <ion-icon name="barbell"></ion-icon>
              <span>Activities & Classes</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="subscriptions.php" class="nav-link">
              <ion-icon name="card"></ion-icon>
              <span>Subscriptions</span>
            </a>
          </li>
        </ul>

        <a href="/MyGym/backend/logout.php" class="logout-btn">
          <ion-icon name="log-out"></ion-icon>
          <span>Logout</span>
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <h1>User Management</h1>
        <p style="color: #9ca3af;">Manage system users and permissions.</p>
      </div>

      <!-- Alerts -->
      <?php if ($ok): ?>
        <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value"><?= count($users) ?></div>
          <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= $admins ?></div>
          <div class="stat-label">Admins</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= $coachs ?></div>
          <div class="stat-label">Coaches</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= $members ?></div>
          <div class="stat-label">Members</div>
        </div>
      </div>

      <!-- Form -->
      <div class="section">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1.5rem">
          <h2 class="section-title" style="margin:0"><?= $editUser ? 'Edit User' : 'Add User' ?></h2>
          <?php if ($editUser): ?>
            <a class="btn btn-ghost" href="users.php">Cancel</a>
          <?php endif; ?>
        </div>

        <form method="post" class="form-grid">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <?php if ($editUser): ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
          <?php else: ?>
            <input type="hidden" name="action" value="create">
          <?php endif; ?>

          <div>
            <label>Full Name</label>
            <input name="fullname" required value="<?= htmlspecialchars($editUser['fullname'] ?? '') ?>">
          </div>
          <div>
            <label>Username</label>
            <input name="username" required value="<?= htmlspecialchars($editUser['username'] ?? '') ?>">
          </div>
          <div>
            <label>Email</label>
            <input type="email" name="email" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
          </div>
          <div>
            <label>Role</label>
            <select name="role">
              <?php
                $currentUi = role_for_ui($editUser['role'] ?? 'MEMBER');
                $options = ['ADMIN'=>'ADMIN','COACH'=>'COACH','MEMBER'=>'MEMBER'];
                foreach ($options as $val => $label) {
                  $sel = ($currentUi === $val) ? 'selected' : '';
                  echo "<option value=\"$val\" $sel>$label</option>";
                }
              ?>
            </select>
          </div>
          <div class="full">
            <label>Password <?= $editUser ? '<small style="text-transform:none;font-weight:400">(leave empty to keep current)</small>' : '' ?></label>
            <input type="password" name="password" <?= $editUser ? '' : 'required' ?>>
          </div>

          <div class="full">
            <button class="btn" type="submit"><?= $editUser ? 'Save Changes' : 'Add User' ?></button>
          </div>
        </form>
      </div>

      <!-- Users List -->
      <div class="section">
        <h2 class="section-title">Users List</h2>
        <div style="overflow-x:auto">
          <table>
            <thead>
              <tr>
                <td style="width:70px">ID</td>
                <td style="width:70px;text-align:center">Photo</td>
                <td>Name</td>
                <td>Username</td>
                <td>Email</td>
                <td style="width:120px">Role</td>
                <td style="width:220px;text-align:right">Actions</td>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
              <?php
                $ru = role_for_ui((string)$u['role']);
                $photo = avatar_url_for_user($u);
                $badgeClass = $ru==='ADMIN' ? 'badge-admin' : ($ru==='COACH' ? 'badge-coach' : 'badge-member');
                $badgeText  = $ru==='ADMIN' ? 'ADMIN' : ($ru==='COACH' ? 'COACH' : 'MEMBER');
              ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td style="text-align:center">
                  <?php if ($photo): ?>
                    <img class="thumb" src="<?= htmlspecialchars($photo) ?>" alt="avatar">
                  <?php else: ?>
                    <span class="no-avatar"></span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['fullname']) ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td>
                  <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                </td>
                <td style="text-align:right">
                  <div style="display:flex;gap:8px;justify-content:flex-end;flex-wrap:wrap">
                    <a class="btn btn-ghost btn-sm" href="users.php?edit=<?= (int)$u['id'] ?>">Edit</a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete this user?');">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <button class="btn btn-sm" type="submit" style="background:#666">Delete</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
