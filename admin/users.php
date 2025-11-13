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
  <?php include __DIR__ . '/../shared/admin-styles.php'; ?>
  <style>
    /* Enhanced Stats Cards with Icons */
    .stat-card .stat-icon {
      font-size: 2.5rem;
      opacity: 0.3;
    }

    /* Different card styles for roles */
    .stat-admins {
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.1), rgba(153, 27, 27, 0.05));
      border: 1px solid rgba(220, 38, 38, 0.2);
    }

    .stat-admins .stat-icon {
      color: #dc2626;
    }

    .stat-coaches {
      background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));
      border: 1px solid rgba(59, 130, 246, 0.2);
    }

    .stat-coaches .stat-icon {
      color: #3b82f6;
    }

    /* Avatar in table */
    .user-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid rgba(220, 38, 38, 0.2);
    }

    .no-avatar {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.1), rgba(220, 38, 38, 0.2));
      border: 2px solid rgba(220, 38, 38, 0.3);
      color: #dc2626;
      font-weight: 600;
      font-size: 14px;
    }

    /* User info cell styling */
    .user-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .user-details {
      display: flex;
      flex-direction: column;
      gap: 2px;
    }

    .user-name {
      font-weight: 600;
      color: #e5e7eb;
    }

    .user-username {
      font-size: 0.875rem;
      color: #9ca3af;
    }

    /* Modal-style form */
    .form-modal {
      background: rgba(17, 24, 39, 0.6);
      backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 2rem;
      border: 1px solid rgba(220, 38, 38, 0.2);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.5);
      animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
      padding-bottom: 1rem;
      border-bottom: 1px solid rgba(220, 38, 38, 0.2);
    }

    .form-modal-title {
      font-size: 1.5rem;
      font-weight: 700;
      background: linear-gradient(135deg, #dc2626, #ef4444);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    /* Enhanced button for add user */
    .btn-add-user {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: linear-gradient(135deg, #dc2626, #991b1b);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 12px;
      text-decoration: none;
      font-weight: 600;
      border: none;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
    }

    .btn-add-user:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 25px rgba(220, 38, 38, 0.4);
    }

    /* Table enhancements */
    table tbody tr {
      transition: all 0.3s ease;
    }

    table tbody tr:hover {
      background: rgba(220, 38, 38, 0.05);
      transform: translateX(4px);
    }

    /* Badge enhancements */
    .badge {
      padding: 6px 14px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }

    .badge::before {
      content: '';
      width: 6px;
      height: 6px;
      border-radius: 50%;
      display: inline-block;
    }

    .badge-admin::before {
      background: #dc2626;
      box-shadow: 0 0 6px #dc2626;
    }

    .badge-coach::before {
      background: #3b82f6;
      box-shadow: 0 0 6px #3b82f6;
    }

    .badge-member::before {
      background: #10b981;
      box-shadow: 0 0 6px #10b981;
    }

    /* Form collapse/expand animation */
    .form-section {
      overflow: hidden;
      transition: max-height 0.4s ease, opacity 0.4s ease;
    }

    .form-section.collapsed {
      max-height: 0;
      opacity: 0;
    }

    .form-section.expanded {
      max-height: 1000px;
      opacity: 1;
    }

    /* Action buttons group */
    .action-buttons {
      display: flex;
      gap: 8px;
      justify-content: flex-end;
      flex-wrap: wrap;
    }

    /* Enhanced delete button */
    .btn-delete {
      background: rgba(107, 114, 128, 0.2) !important;
      border: 1px solid rgba(107, 114, 128, 0.3);
      color: #9ca3af !important;
      transition: all 0.3s ease;
    }

    .btn-delete:hover {
      background: rgba(239, 68, 68, 0.2) !important;
      border-color: rgba(239, 68, 68, 0.5);
      color: #ef4444 !important;
    }

    /* Section header enhancements */
    .section-header-flex {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 1.5rem;
    }

    .users-count {
      background: rgba(220, 38, 38, 0.1);
      color: #dc2626;
      padding: 4px 12px;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 600;
      border: 1px solid rgba(220, 38, 38, 0.2);
    }

    /* Smooth fade-in animation for page load */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .stats-grid {
      animation: fadeInUp 0.5s ease-out;
    }

    .section {
      animation: fadeInUp 0.6s ease-out;
    }

    /* Individual stat card animations with delay */
    .stat-card:nth-child(1) {
      animation: fadeInUp 0.5s ease-out 0.1s both;
    }

    .stat-card:nth-child(2) {
      animation: fadeInUp 0.5s ease-out 0.2s both;
    }

    .stat-card:nth-child(3) {
      animation: fadeInUp 0.5s ease-out 0.3s both;
    }

    .stat-card:nth-child(4) {
      animation: fadeInUp 0.5s ease-out 0.4s both;
    }

    /* Input focus effects */
    input:focus, select:focus {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15);
    }

    /* Hover effect for user rows */
    table tbody tr {
      cursor: pointer;
    }

    /* Enhanced badge success/inactive */
    .badge-success {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .badge-inactive {
      background: rgba(107, 114, 128, 0.2);
      color: #6b7280;
      border: 1px solid rgba(107, 114, 128, 0.3);
    }

    /* Improved scrollbar for table */
    div[style*="overflow-x:auto"]::-webkit-scrollbar {
      height: 8px;
    }

    div[style*="overflow-x:auto"]::-webkit-scrollbar-track {
      background: rgba(17, 24, 39, 0.3);
      border-radius: 10px;
    }

    div[style*="overflow-x:auto"]::-webkit-scrollbar-thumb {
      background: linear-gradient(135deg, #dc2626, #991b1b);
      border-radius: 10px;
    }

    div[style*="overflow-x:auto"]::-webkit-scrollbar-thumb:hover {
      background: linear-gradient(135deg, #ef4444, #dc2626);
    }

    /* Enhanced alert styling */
    .alert {
      animation: slideDown 0.3s ease-out;
      margin-bottom: 1.5rem;
    }

    .alert-success {
      background: rgba(16, 185, 129, 0.1);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.3);
      padding: 1rem 1.5rem;
      border-radius: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-success::before {
      content: '✓';
      display: flex;
      align-items: center;
      justify-content: center;
      width: 24px;
      height: 24px;
      background: rgba(16, 185, 129, 0.2);
      border-radius: 50%;
      font-weight: bold;
    }

    .alert-error {
      background: rgba(239, 68, 68, 0.1);
      color: #ef4444;
      border: 1px solid rgba(239, 68, 68, 0.3);
      padding: 1rem 1.5rem;
      border-radius: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .alert-error::before {
      content: '✕';
      display: flex;
      align-items: center;
      justify-content: center;
      width: 24px;
      height: 24px;
      background: rgba(239, 68, 68, 0.2);
      border-radius: 50%;
      font-weight: bold;
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

      <!-- Modern User Stats -->
      <div class="users-stats-grid">
        <div class="users-stat-card" style="--card-gradient: linear-gradient(135deg, #dc2626, #991b1b);">
          <div class="users-stat-icon-wrapper">
            <ion-icon name="people"></ion-icon>
          </div>
          <div class="users-stat-content">
            <div class="users-stat-value"><?= count($users) ?></div>
            <div class="users-stat-label">Total Users</div>
          </div>
        </div>

        <div class="users-stat-card" style="--card-gradient: linear-gradient(135deg, #7c3aed, #6d28d9);">
          <div class="users-stat-icon-wrapper">
            <ion-icon name="fitness"></ion-icon>
          </div>
          <div class="users-stat-content">
            <div class="users-stat-value"><?= $coachs ?></div>
            <div class="users-stat-label">Coaches</div>
          </div>
        </div>

        <div class="users-stat-card" style="--card-gradient: linear-gradient(135deg, #059669, #047857);">
          <div class="users-stat-icon-wrapper">
            <ion-icon name="person"></ion-icon>
          </div>
          <div class="users-stat-content">
            <div class="users-stat-value"><?= $members ?></div>
            <div class="users-stat-label">Members</div>
          </div>
        </div>
      </div>

      <!-- Professional User Form -->
      <div class="users-form-card">
        <div class="users-form-header">
          <div class="users-form-title-wrapper">
            <div class="users-form-icon" style="background: linear-gradient(135deg, #dc2626, #991b1b);">
              <ion-icon name="<?= $editUser ? 'create' : 'person-add' ?>"></ion-icon>
            </div>
            <div>
              <h2 class="users-form-title"><?= $editUser ? 'Edit User' : 'Create New User' ?></h2>
              <p class="users-form-subtitle"><?= $editUser ? 'Update user information and permissions' : 'Add a new member to your system' ?></p>
            </div>
          </div>
          <?php if ($editUser): ?>
            <a class="users-cancel-btn" href="users.php">
              <ion-icon name="close"></ion-icon>
              <span>Cancel</span>
            </a>
          <?php else: ?>
            <div class="users-form-badge">
              <ion-icon name="sparkles-outline"></ion-icon>
              <span>Quick Add</span>
            </div>
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
            <label>
              <ion-icon name="person" style="vertical-align: middle; margin-right: 4px;"></ion-icon>
              Full Name
            </label>
            <input name="fullname" required value="<?= htmlspecialchars($editUser['fullname'] ?? '') ?>" placeholder="John Doe">
          </div>
          <div>
            <label>
              <ion-icon name="at" style="vertical-align: middle; margin-right: 4px;"></ion-icon>
              Username
            </label>
            <input name="username" required value="<?= htmlspecialchars($editUser['username'] ?? '') ?>" placeholder="johndoe">
          </div>
          <div>
            <label>
              <ion-icon name="mail" style="vertical-align: middle; margin-right: 4px;"></ion-icon>
              Email
            </label>
            <input type="email" name="email" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>" placeholder="john@example.com">
          </div>
          <div>
            <label>
              <ion-icon name="shield" style="vertical-align: middle; margin-right: 4px;"></ion-icon>
              Role
            </label>
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
            <label>
              <ion-icon name="lock-closed" style="vertical-align: middle; margin-right: 4px;"></ion-icon>
              Password
              <?= $editUser ? '<small style="text-transform:none;font-weight:400;color:#9ca3af;margin-left:8px">(leave empty to keep current)</small>' : '' ?>
            </label>
            <input type="password" name="password" <?= $editUser ? '' : 'required' ?> placeholder="<?= $editUser ? 'Enter new password to change' : 'Enter password' ?>">
          </div>

          <div class="full" style="display: flex; gap: 12px; margin-top: 1rem;">
            <button class="users-submit-btn" type="submit">
              <span class="btn-shine"></span>
              <ion-icon name="<?= $editUser ? 'checkmark' : 'add' ?>" style="vertical-align: middle; font-size: 1.25rem; margin-right: 6px;"></ion-icon>
              <span><?= $editUser ? 'Save Changes' : 'Add User' ?></span>
            </button>
            <?php if (!$editUser): ?>
              <button type="reset" class="users-reset-btn">
                <ion-icon name="refresh" style="vertical-align: middle;"></ion-icon>
                <span>Reset</span>
              </button>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <!-- Modern Users List -->
      <div class="users-list-section">
        <div class="users-list-header">
          <div class="users-list-title-wrapper">
            <ion-icon name="list-outline"></ion-icon>
            <div>
              <h2>Users Directory</h2>
              <p>Manage all system users and their roles</p>
            </div>
          </div>
          <div class="users-total-badge">
            <ion-icon name="people"></ion-icon>
            <span><?= count($users) ?> Users</span>
          </div>
        </div>
        <div class="users-table-wrapper">
          <table class="users-table">
            <thead>
              <tr>
                <td style="width:80px">ID</td>
                <td>User</td>
                <td>Email</td>
                <td style="width:140px">Role</td>
                <td style="width:110px;text-align:center">Status</td>
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
                $isActive = (int)($u['is_active'] ?? 1);
                $initials = strtoupper(substr($u['fullname'], 0, 1));
              ?>
              <tr>
                <td style="color:#9ca3af;font-weight:600;">#<?= (int)$u['id'] ?></td>
                <td>
                  <div class="user-info">
                    <?php if ($photo && !str_contains($photo, 'placeholder')): ?>
                      <img class="user-avatar" src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($u['fullname']) ?>">
                    <?php else: ?>
                      <span class="no-avatar"><?= $initials ?></span>
                    <?php endif; ?>
                    <div class="user-details">
                      <div class="user-name"><?= htmlspecialchars($u['fullname']) ?></div>
                      <div class="user-username">@<?= htmlspecialchars($u['username']) ?></div>
                    </div>
                  </div>
                </td>
                <td style="color:#9ca3af;">
                  <ion-icon name="mail-outline" style="vertical-align: middle; margin-right: 4px;"></ion-icon>
                  <?= htmlspecialchars($u['email']) ?>
                </td>
                <td>
                  <span class="badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                </td>
                <td style="text-align:center">
                  <?php if ($isActive): ?>
                    <span class="badge badge-success" style="font-size:0.7rem;padding:4px 10px;">ACTIVE</span>
                  <?php else: ?>
                    <span class="badge badge-inactive" style="font-size:0.7rem;padding:4px 10px;">INACTIVE</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="action-buttons">
                    <a class="btn btn-ghost btn-sm" href="users.php?edit=<?= (int)$u['id'] ?>">
                      <ion-icon name="create" style="vertical-align: middle;"></ion-icon>
                      Edit
                    </a>
                    <form method="post" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <button class="btn btn-sm btn-delete" type="submit">
                        <ion-icon name="trash" style="vertical-align: middle;"></ion-icon>
                        Delete
                      </button>
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
