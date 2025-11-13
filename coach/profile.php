<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('COACH','ADMIN');
require_once __DIR__ . '/../backend/db.php';

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

$user = $_SESSION['user'] ?? null;
$userId = (int)($user['id'] ?? 0);
if ($userId <= 0) { http_response_code(401); exit('Access denied.'); }

$rootDir      = dirname(__DIR__);
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';
if (!is_dir($uploadDirFS)) @mkdir($uploadDirFS, 0755, true);

$stmt = $pdo->prepare("SELECT fullname, email, username, avatar FROM users WHERE id=:id");
$stmt->execute([':id' => $userId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['fullname'=>'','email'=>'','username'=>'','avatar'=>''];

$completionFields = 0;
foreach (['fullname','email','username'] as $field) {
  if (!empty($me[$field])) $completionFields++;
}
$profileCompletion = (int)round(($completionFields / 3) * 100);

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF.');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'upload_avatar') {
      if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException("Invalid upload.");
      }
      $tmp  = $_FILES['avatar']['tmp_name'];
      $size = (int)$_FILES['avatar']['size'];
      if ($size > 3*1024*1024) throw new RuntimeException("File too large (max 3 MB).");

      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime  = (string)$finfo->file($tmp);
      $ext   = match($mime){
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default => null
      };
      if (!$ext) throw new RuntimeException("Unsupported format (jpg/png/webp).");

      foreach (['jpg','png','webp'] as $e) { @unlink($uploadDirFS."/user_{$userId}.{$e}"); }

      $filename = "user_{$userId}.{$ext}";
      if (!move_uploaded_file($tmp, $uploadDirFS.'/'.$filename)) {
        throw new RuntimeException("Unable to save avatar.");
      }

      $st = $pdo->prepare("UPDATE users SET avatar=:a WHERE id=:id");
      $st->execute([':a'=>$filename, ':id'=>$userId]);

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Photo updated.'));
      exit;
    }

    if ($action === 'save_profile') {
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

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Profile updated.'));
      exit;
    }

    throw new RuntimeException('Unknown action.');
  } catch (Throwable $e) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='.urlencode($e->getMessage()));
    exit;
  }
}

$avatarUrl = null;
$hasAvatar = false;
if (!empty($me['avatar'])) {
  $avatarUrl = $uploadDirWeb . '/' . basename((string)$me['avatar']) . '?t=' . time();
  $hasAvatar = true;
} else {
  foreach (['jpg','png','webp'] as $e) {
    $p = $uploadDirFS . "/user_{$userId}.{$e}";
    if (is_file($p)) {
      $avatarUrl = $uploadDirWeb . "/user_{$userId}.{$e}?t=" . time();
      $hasAvatar = true;
      break;
    }
  }
}
if (!$avatarUrl) { $avatarUrl = 'https://via.placeholder.com/120x120?text=+'; }
$avatarStatusText = $hasAvatar ? 'Custom avatar uploaded' : 'Add a photo for your coaching profile.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Coach</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <?php include __DIR__ . '/../shared/coach-styles.php'; ?>
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <div class="logo">
        <svg width="180" height="50" viewBox="0 0 220 60" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g transform="translate(5, 15)">
            <rect x="0" y="5" width="6" height="20" rx="1.5" fill="url(#gradientCoach1)"/>
            <rect x="6" y="8" width="2" height="14" rx="0.5" fill="#4338ca"/>
            <rect x="8" y="12" width="34" height="6" rx="3" fill="url(#gradientCoach1)"/>
            <rect x="42" y="8" width="2" height="14" rx="0.5" fill="#4338ca"/>
            <rect x="44" y="5" width="6" height="20" rx="1.5" fill="url(#gradientCoach1)"/>
          </g>
          <text x="65" y="32" font-family="system-ui, -apple-system, 'Segoe UI', Arial, sans-serif" font-size="28" font-weight="900" fill="url(#textGradientCoach)" letter-spacing="2">MyGym</text>
          <text x="65" y="46" font-family="system-ui, -apple-system, 'Segoe UI', Arial, sans-serif" font-size="10" font-weight="600" fill="#94a3b8" letter-spacing="3">COACH PORTAL</text>
          <defs>
            <linearGradient id="gradientCoach1" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#6366f1"/>
              <stop offset="100%" stop-color="#4f46e5"/>
            </linearGradient>
            <linearGradient id="textGradientCoach" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#6366f1"/>
              <stop offset="50%" stop-color="#8b5cf6"/>
              <stop offset="100%" stop-color="#6366f1"/>
            </linearGradient>
          </defs>
        </svg>
      </div>
      <nav>
        <ul class="nav-menu">
          <li class="nav-item"><a href="index.php" class="nav-link"><ion-icon name="grid"></ion-icon><span>Dashboard</span></a></li>
          <li class="nav-item"><a href="courses.php" class="nav-link"><ion-icon name="calendar"></ion-icon><span>My Classes</span></a></li>
          <li class="nav-item"><a href="members.php" class="nav-link"><ion-icon name="people"></ion-icon><span>My Members</span></a></li>
          <li class="nav-item"><a href="profile.php" class="nav-link active"><ion-icon name="person-circle"></ion-icon><span>Profile</span></a></li>
        </ul>
        <a href="/MyGym/backend/logout.php" class="logout-btn"><ion-icon name="log-out"></ion-icon><span>Logout</span></a>
      </nav>
    </aside>
    <main class="main-content">
      <div class="header">
        <div>
          <h1>My Profile</h1>
          <p style="color:#9ca3af;">Keep your coaching identity aligned with the brand.</p>
        </div>
        <div class="header-date">
          <ion-icon name="calendar-outline"></ion-icon>
          <span><?= date('l, F j') ?></span>
        </div>
      </div>

      <?php if ($ok): ?><div class="alert alert-success"><ion-icon name="checkmark-circle"></ion-icon><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-error"><ion-icon name="alert-circle"></ion-icon><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- Profile Hero Section -->
      <div class="profile-hero">
        <div class="profile-hero-avatar">
          <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($me['fullname']) ?>">
          <div class="avatar-badge"><?= $profileCompletion ?>%</div>
        </div>
        <div class="profile-hero-info">
          <h2><?= htmlspecialchars($me['fullname'] ?: 'Your Name') ?></h2>
          <p class="profile-role"><ion-icon name="shield-checkmark"></ion-icon> Coach</p>
          <div class="profile-stats-inline">
            <div class="stat-inline">
              <ion-icon name="mail"></ion-icon>
              <span><?= htmlspecialchars($me['email'] ?: 'Not set') ?></span>
            </div>
            <div class="stat-inline">
              <ion-icon name="person"></ion-icon>
              <span>@<?= htmlspecialchars($me['username'] ?: 'username') ?></span>
            </div>
          </div>
        </div>
        <div class="profile-completion-ring">
          <svg width="120" height="120">
            <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(99, 102, 241, 0.2)" stroke-width="8"/>
            <circle cx="60" cy="60" r="54" fill="none" stroke="url(#ringGradient)" stroke-width="8"
                    stroke-dasharray="339.292" stroke-dashoffset="<?= 339.292 - (339.292 * $profileCompletion / 100) ?>"
                    stroke-linecap="round" transform="rotate(-90 60 60)"/>
            <defs>
              <linearGradient id="ringGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#6366f1"/>
                <stop offset="100%" stop-color="#8b5cf6"/>
              </linearGradient>
            </defs>
          </svg>
          <div class="ring-text">
            <span class="ring-number"><?= $profileCompletion ?></span>
            <span class="ring-label">Complete</span>
          </div>
        </div>
      </div>

      <!-- Avatar Upload Section -->
      <div class="fluid-section">
        <div class="section-header-minimal">
          <ion-icon name="image"></ion-icon>
          <h3>Update Profile Picture</h3>
        </div>
        <form method="post" enctype="multipart/form-data" class="upload-form">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <input type="hidden" name="action" value="upload_avatar">
          <div class="upload-area">
            <ion-icon name="cloud-upload"></ion-icon>
            <p>Drag & drop or click to upload</p>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required id="avatarInput">
            <span class="upload-hint">JPG, PNG, WEBP • Max 3 MB</span>
          </div>
          <button class="btn btn-upload" type="submit">
            <ion-icon name="checkmark-circle"></ion-icon>
            Upload Photo
          </button>
        </form>
      </div>

      <!-- Personal Info Section -->
      <div class="fluid-section">
        <div class="section-header-minimal">
          <ion-icon name="person-circle"></ion-icon>
          <h3>Personal Information</h3>
        </div>
        <form method="post" class="modern-form">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <input type="hidden" name="action" value="save_profile">

          <div class="input-group">
            <label>
              <ion-icon name="person"></ion-icon>
              Full Name
            </label>
            <input type="text" name="fullname" required value="<?= htmlspecialchars($me['fullname']) ?>" placeholder="Enter your full name">
          </div>

          <div class="input-group">
            <label>
              <ion-icon name="at"></ion-icon>
              Username
            </label>
            <input type="text" name="username" required value="<?= htmlspecialchars($me['username']) ?>" placeholder="Choose a username">
          </div>

          <div class="input-group">
            <label>
              <ion-icon name="mail"></ion-icon>
              Email Address
            </label>
            <input type="email" name="email" required value="<?= htmlspecialchars($me['email']) ?>" placeholder="your@email.com">
          </div>

          <div class="input-group">
            <label>
              <ion-icon name="lock-closed"></ion-icon>
              New Password
              <span class="optional-badge">Optional</span>
            </label>
            <input type="password" name="password" placeholder="Leave blank to keep current password">
          </div>

          <button class="btn btn-primary" type="submit">
            <ion-icon name="save"></ion-icon>
            Save Changes
          </button>
        </form>
      </div>
    </main>
  </div>
</body>
</html>
