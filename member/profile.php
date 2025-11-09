<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('MEMBER','ADMIN');
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/subscriptions.php';

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

$user = $_SESSION['user'] ?? null;
$userId = (int)($user['id'] ?? 0);
if ($userId <= 0) { http_response_code(401); exit('Access denied.'); }

$canBook = false;
try { $canBook = has_class_access($pdo, $userId); } catch (Throwable $e) { $canBook = false; }

$rootDir      = dirname(__DIR__);
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';
@is_dir($uploadDirFS) || @mkdir($uploadDirFS, 0777, true);

$stmt = $pdo->prepare("SELECT fullname, email, username, avatar FROM users WHERE id=:id");
$stmt->execute([':id' => $userId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['fullname'=>'','email'=>'','username'=>'','avatar'=>null];

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF.');
    }

    if (($_POST['action'] ?? '') === 'upload_avatar') {
      if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Invalid upload.");
      }
      $tmp  = $_FILES['avatar']['tmp_name'];
      $size = (int)$_FILES['avatar']['size'];
      if ($size > 3*1024*1024) throw new RuntimeException("File too large (max 3 MB).");

      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime  = (string)$finfo->file($tmp);
      $ext   = match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>null};
      if (!$ext) throw new RuntimeException("Unsupported format (jpg/png/webp).");

      foreach (['jpg','png','webp'] as $e) { @unlink($uploadDirFS."/user_{$userId}.{$e}"); }
      $destFS = $uploadDirFS . "/user_{$userId}.{$ext}";
      if (!move_uploaded_file($tmp, $destFS)) throw new RuntimeException("Unable to save avatar.");
      @chmod($destFS, 0666);

      $stmt = $pdo->prepare("UPDATE users SET avatar=:a WHERE id=:id");
      $stmt->execute([':a'=>"user_{$userId}.{$ext}", ':id'=>$userId]);

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Photo updated.')); exit;
    }

    if (($_POST['action'] ?? '') === 'save_profile') {
      $fullname = trim((string)($_POST['fullname'] ?? ''));
      $email    = trim((string)($_POST['email'] ?? ''));
      $username = trim((string)($_POST['username'] ?? ''));
      $password = (string)($_POST['password'] ?? '');

      if ($fullname==='' || $email==='' || $username==='') {
        throw new RuntimeException('Missing required fields.');
      }

      if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET fullname=:f, email=:e, username=:u, password_hash=:ph WHERE id=:id");
        $stmt->execute([':f'=>$fullname, ':e'=>$email, ':u'=>$username, ':ph'=>$hash, ':id'=>$userId]);
      } else {
        $stmt = $pdo->prepare("UPDATE users SET fullname=:f, email=:e, username=:u WHERE id=:id");
        $stmt->execute([':f'=>$fullname, ':e'=>$email, ':u'=>$username, ':id'=>$userId]);
      }

      $_SESSION['user']['fullname'] = $fullname;
      $_SESSION['user']['email']    = $email;
      $_SESSION['user']['username'] = $username;

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Profile updated.')); exit;
    }

    throw new RuntimeException('Unknown action.');
  } catch (Throwable $e) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='.urlencode($e->getMessage())); exit;
  }
}

$avatarUrl = null;
if (!empty($me['avatar'])) {
  $avatarUrl = $uploadDirWeb . '/' . basename((string)$me['avatar']) . '?t=' . time();
} else {
  foreach (['jpg','png','webp'] as $e) {
    $p = $uploadDirFS . "/user_{$userId}.{$e}";
    if (is_file($p)) { $avatarUrl = $uploadDirWeb . "/user_{$userId}.{$e}?t=" . time(); break; }
  }
}
if (!$avatarUrl) { $avatarUrl = 'https://via.placeholder.com/120x120?text=+'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Member</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #0a0a0a;
      color: #f5f7fb;
      min-height: 100vh;
      background: radial-gradient(55% 80% at 50% 0%, rgba(220, 38, 38, 0.22), transparent 65%),
                  radial-gradient(60% 90% at 75% 15%, rgba(127, 29, 29, 0.18), transparent 70%),
                  linear-gradient(180deg, rgba(10, 10, 10, 0.98) 0%, rgba(10, 10, 10, 1) 100%);
    }
    .container { display: flex; min-height: 100vh; }
    .sidebar {
      width: 280px; background: rgba(17, 17, 17, 0.95);
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      padding: 2rem 1.5rem; position: fixed; height: 100vh; overflow-y: auto;
    }
    .logo { display: flex; align-items: center; gap: 1rem; margin-bottom: 3rem; }
    .logo-icon {
      width: 48px; height: 48px;
      background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%);
      border-radius: 12px; display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; box-shadow: 0 10px 30px rgba(220,38,38,0.4);
    }
    .logo-text h1 {
      font-size: 1.5rem; font-weight: 800;
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .logo-text p { font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.1em; }
    .nav-menu { list-style: none; margin: 2rem 0; }
    .nav-item { margin-bottom: 0.5rem; }
    .nav-link {
      display: flex; align-items: center; gap: 1rem; padding: 1rem; color: #9ca3af;
      text-decoration: none; border-radius: 12px; transition: all 0.3s; font-weight: 500;
    }
    .nav-link:hover { background: rgba(255, 255, 255, 0.05); color: #fff; }
    .nav-link.active {
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
      color: #fff; box-shadow: 0 4px 20px rgba(220,38,38,0.3);
    }
    .nav-link.locked { opacity: 0.6; }
    .nav-link ion-icon { font-size: 1.25rem; }
    .logout-btn {
      display: flex; align-items: center; gap: 1rem; padding: 1rem;
      background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px; color: #9ca3af; text-decoration: none; transition: all 0.3s;
      font-weight: 500; margin-top: 2rem;
    }
    .logout-btn:hover { background: rgba(220, 38, 38, 0.2); color: #fff; border-color: #dc2626; }
    .main-content { margin-left: 280px; flex: 1; padding: 2rem; max-width: 1000px; }
    .header { margin-bottom: 2rem; }
    .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
    .section {
      background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px; padding: 2rem; margin-bottom: 2rem;
    }
    .section-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; }
    .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; }
    .alert.ok { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
    .alert.err { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
    .avatar-block {
      display: flex; align-items: center; gap: 2rem; margin-top: 1rem;
    }
    .avatar-block img {
      width: 120px; height: 120px; border-radius: 50%; object-fit: cover;
      border: 3px solid rgba(220, 38, 38, 0.3); box-shadow: 0 10px 30px rgba(220,38,38,0.3);
    }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-top: 1rem; }
    label { display: block; color: #9ca3af; font-size: 0.9rem; font-weight: 500; margin-bottom: 0.5rem; }
    input[type="text"], input[type="email"], input[type="password"], input[type="file"] {
      width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; color: #f5f7fb;
      font-family: 'Poppins', sans-serif; transition: all 0.3s;
    }
    input:focus { outline: none; border-color: #dc2626; background: rgba(255, 255, 255, 0.08); }
    input[type="file"] { padding: 0.5rem; }
    small { color: #6b7280; font-size: 0.75rem; }
    .btn {
      background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
      color: #fff; border: none; border-radius: 10px; padding: 0.75rem 1.5rem;
      font-weight: 600; cursor: pointer; transition: all 0.3s; font-family: 'Poppins', sans-serif;
      margin-top: 0.5rem;
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4); }
    .hint { color: #6b7280; font-size: 0.85rem; margin-top: 0.5rem; }
    @media (max-width: 991px) {
      .sidebar { width: 0; opacity: 0; }
      .main-content { margin-left: 0; }
      .form-row { grid-template-columns: 1fr; }
      .avatar-block { flex-direction: column; text-align: center; }
    }
  </style>
</head>
<body>
  <div class="container">
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
          <li class="nav-item"><a href="index.php" class="nav-link"><ion-icon name="grid"></ion-icon><span>Dashboard</span></a></li>
          <li class="nav-item">
            <?php if ($canBook): ?>
              <a href="courses.php" class="nav-link"><ion-icon name="calendar"></ion-icon><span>My Classes</span></a>
            <?php else: ?>
              <a href="subscribe.php" class="nav-link locked" title="Upgrade to PLUS/PRO to unlock"><ion-icon name="lock-closed"></ion-icon><span>My Classes (Locked)</span></a>
            <?php endif; ?>
          </li>
          <li class="nav-item"><a href="subscribe.php" class="nav-link"><ion-icon name="card"></ion-icon><span>Subscription</span></a></li>
          <li class="nav-item"><a href="profile.php" class="nav-link active"><ion-icon name="person-circle"></ion-icon><span>Profile</span></a></li>
        </ul>
        <a href="/MyGym/backend/logout.php" class="logout-btn"><ion-icon name="log-out"></ion-icon><span>Logout</span></a>
      </nav>
    </aside>
    <main class="main-content">
      <div class="header">
        <h1>My Profile</h1>
        <p style="color: #9ca3af;">Manage your personal information and settings</p>
      </div>

      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- Avatar section -->
      <div class="section">
        <h2 class="section-title">Profile Picture</h2>
        <div class="avatar-block">
          <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">
          <div style="flex: 1;">
            <form method="post" enctype="multipart/form-data">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
              <input type="hidden" name="action" value="upload_avatar">
              <label>Choose a new profile picture</label>
              <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
              <button class="btn" type="submit">Upload Photo</button>
            </form>
            <p class="hint">Accepted formats: JPG, PNG, WEBP · Maximum size: 3 MB</p>
          </div>
        </div>
      </div>

      <!-- Personal info section -->
      <div class="section">
        <h2 class="section-title">Personal Information</h2>
        <form method="post">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <input type="hidden" name="action" value="save_profile">

          <div class="form-row">
            <div>
              <label>Full Name</label>
              <input type="text" name="fullname" required value="<?= htmlspecialchars($me['fullname']) ?>">
            </div>
            <div>
              <label>Username</label>
              <input type="text" name="username" required value="<?= htmlspecialchars($me['username']) ?>">
            </div>
          </div>

          <div class="form-row">
            <div>
              <label>Email Address</label>
              <input type="email" name="email" required value="<?= htmlspecialchars($me['email']) ?>">
            </div>
            <div>
              <label>New Password <small>(optional)</small></label>
              <input type="password" name="password" placeholder="Leave blank to keep current password">
            </div>
          </div>

          <button class="btn" type="submit" style="margin-top: 1.5rem;">Save Changes</button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
