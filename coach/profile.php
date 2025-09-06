<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../backend/auth.php';
requireRole('COACH','ADMIN'); // FR: Vérifie que l’utilisateur est coach ou admin
require_once __DIR__ . '/../backend/db.php';

/* FR: Protection CSRF */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

$user = $_SESSION['user'] ?? null;
$userId = (int)($user['id'] ?? 0);
if ($userId <= 0) { http_response_code(401); exit('Access denied.'); } // traduit

/* FR: Préparation des dossiers d’upload */
$rootDir      = dirname(__DIR__);                 
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';
if (!is_dir($uploadDirFS)) @mkdir($uploadDirFS, 0755, true);

/* FR: Récupération infos utilisateur depuis la BDD */
$stmt = $pdo->prepare("SELECT fullname, email, username, avatar FROM users WHERE id=:id");
$stmt->execute([':id' => $userId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['fullname'=>'','email'=>'','username'=>'','avatar'=>''];

/* FR: Messages de succès/erreur */
$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

/* FR: Gestion des formulaires POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF.');
    }

    $action = (string)($_POST['action'] ?? '');

    // FR: Upload d’un nouvel avatar
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

    // FR: Mise à jour des informations de profil
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

      // FR: Mise à jour de la session (affichage topbar)
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

/* FR: Détermination de l’URL de l’avatar */
$avatarUrl = null;
if (!empty($me['avatar'])) {
  $avatarUrl = $uploadDirWeb . '/' . basename((string)$me['avatar']) . '?t=' . time();
} else {
  foreach (['jpg','png','webp'] as $e) {
    $p = $uploadDirFS . "/user_{$userId}.{$e}";
    if (is_file($p)) { $avatarUrl = $uploadDirWeb . "/user_{$userId}.{$e}?t=" . time(); break; }
  }
}
if (!$avatarUrl) { $avatarUrl = 'https://via.placeholder.com/108x108?text=+'; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile — Coach</title> <!-- traduit -->

  <!-- FR: Librairie d’icônes -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <!-- FR: Styles internes (CSS) -->
  <style>
    /* FR: Palette, layout, responsivité */
    @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap");
    :root{--primary:#e50914;--primary-600:#cc0812;--border:#e9e9e9;--gray:#f5f5f5;--shadow:0 7px 25px rgba(0,0,0,.08)}
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Ubuntu",system-ui,Segoe UI,Roboto,sans-serif}
    body{background:var(--gray)}
    .container{position:relative;width:100%}
    .navigation{position:fixed;width:300px;height:100%;background:var(--primary);box-shadow:var(--shadow)}
    .navigation ul{position:absolute;inset:0}
    .navigation li{list-style:none}
    .navigation a{display:flex;height:60px;align-items:center;color:#fff;text-decoration:none;padding-left:10px}
    .navigation .icon{min-width:50px;text-align:center}
    .navigation li:hover,.navigation li.active{background:var(--primary-600)}
    .main{position:absolute;left:300px;width:calc(100% - 300px);min-height:100vh;background:#fff}
    .topbar{height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid var(--border)}
    .wrap{max-width:900px;margin:0 auto;padding:20px}
    .panel{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow);margin-top:18px}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    label{font-weight:600}
    input{width:100%;margin-top:6px;padding:10px;border:1px solid var(--border);border-radius:8px}
    .btn{background:var(--primary);color:#fff;border:0;border-radius:8px;padding:.45rem .9rem;font-weight:700;cursor:pointer}
    .alert{padding:10px;border-radius:8px;margin:10px 0}
    .ok{background:#e8f5e9;border:1px solid #c8e6c9}
    .err{background:#fdecea;border:1px solid #f5c6cb}
    .avatarBlock{display:flex;align-items:center;gap:16px}
    .avatarBlock img{width:108px;height:108px;border-radius:50%;object-fit:cover;border:2px solid #eee}
    @media (max-width:900px){.main{left:0;width:100%}.row{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="container">
  <!-- FR: Barre latérale de navigation -->
  <div class="navigation">
    <ul>
      <li><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li><a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">My Courses</span></a></li> <!-- traduit -->
      <li><a href="members.php"><span class="icon"><ion-icon name="people-outline"></ion-icon></span><span class="title">My Members</span></a></li> <!-- traduit -->
      <li class="active"><a href="profile.php"><span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span><span class="title">Profile</span></a></li> <!-- traduit -->
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Logout</span></a></li> <!-- traduit -->
    </ul>
  </div>

  <!-- FR: Partie principale -->
  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div style="font-weight:700;color:#e50914">My Profile</div> <!-- traduit -->
    </div>

    <div class="wrap">
      <!-- FR: Messages succès/erreur -->
      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- FR: Bloc Avatar -->
      <div class="panel">
        <h2 style="margin-top:0">Profile Picture</h2> <!-- traduit -->
        <div class="avatarBlock" style="margin-top:8px">
          <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
            <input type="hidden" name="action" value="upload_avatar">
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
            <button class="btn" type="submit" style="margin-left:8px">Upload</button> <!-- traduit -->
          </form>
        </div>
        <p style="color:#666;margin-top:8px">Accepted formats: JPG, PNG, WEBP · Max 3 MB.</p> <!-- traduit -->
      </div>

      <!-- FR: Bloc Informations personnelles -->
      <div class="panel">
        <h2 style="margin-top:0">Personal Information</h2> <!-- traduit -->
        <form method="post" class="row" style="margin-top:8px">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <input type="hidden" name="action" value="save_profile">
          <div>
            <label>Full Name
              <input name="fullname" required value="<?= htmlspecialchars($me['fullname']) ?>">
            </label>
          </div>
          <div>
            <label>Username
              <input name="username" required value="<?= htmlspecialchars($me['username']) ?>">
            </label>
          </div>
          <div>
            <label>Email
              <input type="email" name="email" required value="<?= htmlspecialchars($me['email']) ?>">
            </label>
          </div>
          <div>
            <label>New Password <small>(optional)</small>
              <input type="password" name="password" placeholder="Leave empty to keep current password">
            </label>
          </div>
          <div style="grid-column:1 / -1">
            <button class="btn" type="submit">Save</button> <!-- traduit -->
          </div>
        </form>
      </div>

    </div>
  </div>
</div>

<!-- FR: Scripts icônes -->
<script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
</body>
</html>
