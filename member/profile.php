<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../backend/auth.php';
requireRole('MEMBER','ADMIN');                 // Accès membre (rôle requis)
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/subscriptions.php'; // <= pour has_class_access()

/* ===== CSRF =====
   // Génère un token CSRF s'il n'existe pas encore (protection des formulaires)
*/
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

/* ===== User =====
   // Récupère l'utilisateur courant depuis la session
*/
$user = $_SESSION['user'] ?? null;
$userId = (int)($user['id'] ?? 0);
if ($userId <= 0) { http_response_code(401); exit('Accès refusé.'); }

/* ===== Abonnement / Accès cours =====
   // Détermine si l'utilisateur peut réserver (plans PLUS/PRO)
*/
$canBook = false;
try { $canBook = has_class_access($pdo, $userId); } catch (Throwable $e) { $canBook = false; }

/* ===== Dossiers / URLs pour avatars =====
   // Prépare les chemins d'upload côté serveur et l'URL publique
*/
$rootDir      = dirname(__DIR__);
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';
@is_dir($uploadDirFS) || @mkdir($uploadDirFS, 0777, true);
@chmod($uploadDirFS, 0777);

/* ===== Infos actuelles =====
   // Charge les infos de profil pour pré-remplir le formulaire
*/
$stmt = $pdo->prepare("SELECT fullname, email, username, avatar FROM users WHERE id=:id");
$stmt->execute([':id' => $userId]);
$me = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['fullname'=>'','email'=>'','username'=>'','avatar'=>null];

/* Messages
   // Messages de feedback passés en query string (?ok=..., ?err=...)
*/
$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

/* ===== POST =====
   // Traite les actions: upload avatar et sauvegarde du profil
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Vérification du token CSRF
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('CSRF invalide.');
    }

    // -- Upload avatar
    if (($_POST['action'] ?? '') === 'upload_avatar') {
      if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        $code = $_FILES['avatar']['error'] ?? -1;
        throw new RuntimeException("Upload invalide (code $code).");
      }
      $tmp  = $_FILES['avatar']['tmp_name'];
      $size = (int)$_FILES['avatar']['size'];
      if ($size > 3*1024*1024) throw new RuntimeException("Fichier trop volumineux (max 3 Mo).");

      // Détection du type MIME pour limiter aux formats autorisés
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime  = (string)$finfo->file($tmp);
      $ext   = match($mime){'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp',default=>null};
      if (!$ext) throw new RuntimeException("Format non supporté (jpg/png/webp).");

      // Nettoie les anciennes versions puis déplace le fichier
      foreach (['jpg','png','webp'] as $e) { @unlink($uploadDirFS."/user_{$userId}.{$e}"); }
      $destFS = $uploadDirFS . "/user_{$userId}.{$ext}";
      if (!move_uploaded_file($tmp, $destFS)) throw new RuntimeException("Impossible d’enregistrer l’avatar.");
      @chmod($destFS, 0666); // droits larges en dev

      // Met à jour la BDD avec le nom de fichier
      $stmt = $pdo->prepare("UPDATE users SET avatar=:a WHERE id=:id");
      $stmt->execute([':a'=>"user_{$userId}.{$ext}", ':id'=>$userId]);

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Photo mise à jour.')); exit;
    }

    // -- Sauvegarde du profil (texte + éventuel nouveau mot de passe)
    if (($_POST['action'] ?? '') === 'save_profile') {
      $fullname = trim((string)($_POST['fullname'] ?? ''));
      $email    = trim((string)($_POST['email'] ?? ''));
      $username = trim((string)($_POST['username'] ?? ''));
      $password = (string)($_POST['password'] ?? '');

      if ($fullname==='' || $email==='' || $username==='') {
        throw new RuntimeException('Champs requis manquants.');
      }

      if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET fullname=:f, email=:e, username=:u, password_hash=:ph WHERE id=:id");
        $stmt->execute([':f'=>$fullname, ':e'=>$email, ':u'=>$username, ':ph'=>$hash, ':id'=>$userId]);
      } else {
        $stmt = $pdo->prepare("UPDATE users SET fullname=:f, email=:e, username=:u WHERE id=:id");
        $stmt->execute([':f'=>$fullname, ':e'=>$email, ':u'=>$username, ':id'=>$userId]);
      }

      // Synchronise la session pour refléter les changements immédiatement
      $_SESSION['user']['fullname'] = $fullname;
      $_SESSION['user']['email']    = $email;
      $_SESSION['user']['username'] = $username;

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Profil mis à jour.')); exit;
    }

    // Aucune action reconnue
    throw new RuntimeException('Action inconnue.');
  } catch (Throwable $e) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='.urlencode($e->getMessage())); exit;
  }
}

/* ===== URL avatar =====
   // Construit l’URL publique de la photo (BDD sinon fallback fichier)
*/
$avatarUrl = null;
if (!empty($me['avatar'])) {
  $avatarUrl = $uploadDirWeb . '/' . basename((string)$me['avatar']) . '?t=' . time();
} else {
  foreach (['jpg','png','webp'] as $e) {
    $p = $uploadDirFS."/user_{$userId}.{$e}";
    if (is_file($p)) { $avatarUrl = $uploadDirWeb."/user_{$userId}.{$e}?t=".time(); break; }
  }
}
?>
<!DOCTYPE html>
<html lang="en"> <!-- UI en anglais -->
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Profile — MyGym</title>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <style>
    :root{--primary:#e50914;--primary-600:#cc0812;--border:#e9e9e9;--gray:#f5f5f5;--shadow:0 7px 25px rgba(0,0,0,.08)}
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Ubuntu",system-ui,Segoe UI,Roboto,sans-serif}
    body{background:var(--gray)}
    .container{position:relative;width:100%}
    .navigation{position:fixed;width:300px;height:100%;background:var(--primary);box-shadow:var(--shadow)}
    .navigation ul{position:absolute;inset:0} .navigation li{list-style:none}
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
    .ok{background:#e8f5e9;border:1px solid #c8e6c9} .err{background:#fdecea;border:1px solid #f5c6cb}
    .avatarBlock{display:flex;align-items:center;gap:16px}
    .avatarBlock img{width:108px;height:108px;border-radius:50%;object-fit:cover;border:2px solid #eee}
    @media (max-width:900px){.main{left:0;width:100%}.row{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="container">
  <div class="navigation">
    <ul>
      <li><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li>
        <?php if ($canBook): ?>
          <a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">My Classes</span></a>
        <?php else: ?>
          <a href="subscribe.php" title="Reservations available with Plus/Pro" style="opacity:.75">
            <span class="icon"><ion-icon name="lock-closed-outline"></ion-icon></span>
            <span class="title">My Classes (locked)</span>
          </a>
        <?php endif; ?>
      </li>
      <li><a href="subscribe.php"><span class="icon"><ion-icon name="card-outline"></ion-icon></span><span class="title">My Subscription</span></a></li>
      <li class="active"><a href="profile.php"><span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span><span class="title">Profile</span></a></li>
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Sign out</span></a></li>
    </ul>
  </div>

  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div style="font-weight:700;color:#e50914">My Profile</div>
    </div>

    <div class="wrap">
      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- Bloc : Photo de profil (upload) -->
      <div class="panel">
        <h2 style="margin-top:0">Profile photo</h2>
        <div class="avatarBlock" style="margin-top:8px">
          <img src="<?= $avatarUrl ?: 'https://via.placeholder.com/108x108?text=+' ?>" alt="Avatar">
          <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
            <input type="hidden" name="action" value="upload_avatar">
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
            <button class="btn" type="submit" style="margin-left:8px">Upload</button>
          </form>
        </div>
        <p style="color:#666;margin-top:8px">Formats: JPG, PNG, WEBP · Max 3&nbsp;MB</p>
      </div>

      <!-- Bloc : Informations personnelles -->
      <div class="panel">
        <h2 style="margin-top:0">Personal information</h2>
        <form method="post" class="row" style="margin-top:8px">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <input type="hidden" name="action" value="save_profile">
          <div><label>Full name <input name="fullname" required value="<?= htmlspecialchars($me['fullname']) ?>"></label></div>
          <div><label>Username <input name="username" required value="<?= htmlspecialchars($me['username']) ?>"></label></div>
          <div><label>Email <input type="email" name="email" required value="<?= htmlspecialchars($me['email']) ?>"></label></div>
          <div><label>New password <small>(optional)</small> <input type="password" name="password" placeholder="Leave blank to keep current password"></label></div>
          <div style="grid-column:1 / -1"><button class="btn" type="submit">Save</button></div>
        </form>
      </div>

    </div>
  </div>
</div>
</body>
</html>
