<?php
declare(strict_types=1);

/**
 * Admin — User management (with Photo column)
 * FR: Cette page d’admin liste/crée/édite/supprime des utilisateurs.
 * FR: Affiche l’avatar pour MEMBER/COACH si dispo, ADMIN → emplacement vide.
 */

session_start();

require_once __DIR__ . '/../backend/auth.php';
requireRole('ADMIN'); // FR: accès admin uniquement

require_once __DIR__ . '/../backend/db.php';

/* ===================== CSRF =====================
   FR: Génère et stocke un token CSRF pour sécuriser les POST */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf'];

/* =================== Helpers ====================
   FR: Fonctions utilitaires d’assainissement et de normalisation des rôles */
function sanitize_email(string $e): string { return filter_var(trim($e), FILTER_SANITIZE_EMAIL) ?: ''; }
function sanitize_text(string $t): string  { return trim($t); }

/** FR: Détecte le format de rôle attendu en BDD (MEMBRE vs MEMBER) et convertit à l’insert/update */
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

/** FR: Normalise depuis la BDD vers l’UI (ADMIN/COACH/MEMBER) */
function role_for_ui(string $dbRole): string {
  $r = strtoupper(trim($dbRole));
  if ($r === 'MEMBRE') $r = 'MEMBER';
  if (!in_array($r, ['ADMIN','COACH','MEMBER'], true)) $r = 'MEMBER';
  return $r;
}

/** FR: Résout l’URL d’avatar (BDD -> fichiers -> placeholder). Admin => vide (null). */
function avatar_url_for_user(array $u): ?string {
  // FR: Si Admin => pas de photo affichée
  $ru = role_for_ui((string)($u['role'] ?? 'MEMBER'));
  if ($ru === 'ADMIN') return null;

  $rootFS      = dirname(__DIR__);                // .../MyGym
  $uploadFS    = $rootFS . '/uploads/avatars';
  $uploadWeb   = '/MyGym/uploads/avatars';

  // 1) FR: BDD (colonne avatar si existe)
  if (!empty($u['avatar'])) {
    return $uploadWeb . '/' . basename((string)$u['avatar']) . '?t=' . time();
  }

  // 2) FR: Fichiers nommés user_{id}.ext
  $id = (int)($u['id'] ?? 0);
  foreach (['jpg','png','webp'] as $ext) {
    $p = $uploadFS . "/user_{$id}.{$ext}";
    if (is_file($p)) return $uploadWeb . "/user_{$id}.{$ext}?t=" . time();
  }

  // 3) FR: Placeholder si MEMBER/COACH
  return 'https://via.placeholder.com/40x40?text=%20';
}

/* ================== Actions (PRG) =================
   FR: Post/Redirect/Get avec messages ok/err via query string */
$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

/* FR: Détecte si la colonne avatar existe pour adapter la SELECT */
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

/* ============= Lecture + pré-remplissage =============
   FR: Charge la liste d’utilisateurs et prépare un éventuel “edit” */
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

/* ===================== KPIs =====================
   FR: Compte combien d’admins/coachs/members pour les cartes */
$admins = $coachs = $members = 0;
foreach ($users as $u) {
  $ru = role_for_ui((string)$u['role']); // ADMIN/COACH/MEMBER
  if ($ru==='ADMIN')  $admins++;
  if ($ru==='COACH')  $coachs++;
  if ($ru==='MEMBER') $members++;
}

?>
<!DOCTYPE html>
<html lang="en"> <!-- FR: attribut non affiché gardé tel quel -->
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MyGym — Users</title> <!-- traduit -->

  <!-- FR: Librairie d’icônes -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>

  <style>
    /* FR: Styles inchangés (dashboard admin) */
    @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap");
    :root{
      --primary:#e50914;--primary-600:#cc0812;--white:#fff;--gray:#f5f5f5;--border:#e9e9e9;
      --black1:#222;--black2:#999;--shadow:0 7px 25px rgba(0,0,0,.08);
      --ok:#e8f5e9;--okb:#c8e6c9;--err:#fdecea;--errb:#f5c6cb
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Ubuntu",system-ui,Segoe UI,Roboto,sans-serif}
    body{background:var(--gray);color:var(--black1);min-height:100vh;overflow-x:hidden}
    .container{position:relative;width:100%}

    /* Sidebar */
    .navigation{position:fixed;width:300px;height:100%;background:var(--primary);overflow:hidden;box-shadow:var(--shadow)}
    .navigation ul{position:absolute;inset:0}
    .navigation ul li{list-style:none}
    .navigation ul li a{display:flex;height:60px;align-items:center;color:#fff;text-decoration:none;padding-left:10px}
    .navigation ul li a .icon{min-width:50px;text-align:center}
    .navigation ul li a .icon ion-icon{font-size:1.5rem;color:#fff}
    .navigation ul li a .title{white-space:nowrap}
    .navigation ul li:hover,.navigation ul li.active{background:var(--primary-600)}

    /* Main */
    .main{position:absolute;left:300px;width:calc(100% - 300px);min-height:100vh;background:var(--white)}
    .topbar{height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid var(--border)}
    .wrap{max-width:1200px;margin:0 auto;padding:20px}

    /* Cards KPIs */
    .grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
    .card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:center}
    .numbers{font-weight:700;font-size:1.8rem;color:var(--primary)}
    .cardName{color:#777}

    /* Panels / forms */
    .panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;box-shadow:var(--shadow);margin-top:20px}
    .alert{padding:10px 12px;border-radius:8px;margin:14px 0}
    .ok{background:var(--ok);border:1px solid var(--okb)}
    .err{background:var(--err);border:1px solid var(--errb)}
    .form-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:12px}
    .form-grid .full{grid-column:1 / -1}
    label{font-weight:600;font-size:.92rem}
    input,select{width:100%;margin-top:6px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;outline:none}
    input:focus,select:focus{border-color:var(--primary)}

    /* Table users */
    .tablewrap{overflow:auto;max-width:100%}
    table{width:100%;border-collapse:collapse}
    thead th{position:sticky;top:0;background:#fafafa;border-bottom:1px solid var(--border);text-align:left;font-weight:700}
    th,td{padding:10px;border-bottom:1px solid #eee;vertical-align:middle}
    tbody tr:nth-child(even){background:#fcfcfc}

    .col-id{width:70px}
    .col-photo{width:70px;text-align:center}
    .col-role{width:140px}
    .col-actions{width:230px;text-align:right}

    .thumb{width:40px;height:40px;border-radius:50%;object-fit:cover;border:1px solid #e6e6e6;display:inline-block}
    .no-avatar{display:inline-block;width:40px;height:40px;border-radius:50%;background:#f3f3f3;border:1px dashed #ddd}

    .badge{padding:2px 10px;border-radius:999px;font-weight:700;display:inline-block}
    .badge.admin{background:var(--primary);color:#fff}
    .badge.coach{background:#111;color:#fff}
    .badge.member{background:#ddd;color:#111}

    .btn{background:var(--primary);color:#fff;border:0;border-radius:8px;padding:.45rem .9rem;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
    .btn--ghost{background:transparent;color:var(--primary);border:1px solid var(--primary)}
    .btn--sm{padding:.35rem .6rem;border-radius:6px}

    @media (max-width:991px){
      .main{left:0;width:100%}
      .grid4{grid-template-columns:repeat(2,1fr)}
      .col-actions{width:170px}
    }
  </style>
</head>
<body>
<div class="container">
  <!-- FR: Navigation latérale (libellés traduits) -->
  <div class="navigation">
    <ul>
      <li><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li class="active"><a href="users.php"><span class="icon"><ion-icon name="people-outline"></ion-icon></span><span class="title">Users</span></a></li> <!-- traduit -->
      <li><a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">Activities & Classes</span></a></li> <!-- traduit -->
      <li><a href="subscriptions.php"><span class="icon"><ion-icon name="card-outline"></ion-icon></span><span class="title">Subscriptions & Payments</span></a></li> <!-- traduit -->
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Logout</span></a></li> <!-- traduit -->
    </ul>
  </div>

  <!-- FR: Contenu principal -->
  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div style="font-weight:700;color:var(--primary)">Users — Administration</div> <!-- traduit -->
    </div>

    <div class="wrap">
      <!-- FR: Messages globaux (succès/erreur) -->
      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- KPIs -->
      <div class="grid4">
        <div class="card">
          <div>
            <div class="numbers"><?= count($users) ?></div>
            <div class="cardName">Total users</div> <!-- traduit -->
          </div>
          <ion-icon class="iconBx" name="people-outline" style="font-size:2rem;color:#999"></ion-icon>
        </div>
        <div class="card">
          <div>
            <div class="numbers"><?= $admins ?></div>
            <div class="cardName">Admins</div>
          </div>
          <ion-icon class="iconBx" name="shield-checkmark-outline" style="font-size:2rem;color:#999"></ion-icon>
        </div>
        <div class="card">
          <div>
            <div class="numbers"><?= $coachs ?></div>
            <div class="cardName">Coaches</div> <!-- traduit (pluriel anglais) -->
          </div>
          <ion-icon class="iconBx" name="fitness-outline" style="font-size:2rem;color:#999"></ion-icon>
        </div>
        <div class="card">
          <div>
            <div class="numbers"><?= $members ?></div>
            <div class="cardName">Members</div> <!-- traduit -->
          </div>
          <ion-icon class="iconBx" name="person-outline" style="font-size:2rem;color:#999"></ion-icon>
        </div>
      </div>

      <!-- Formulaire Ajouter / Modifier -->
      <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <h2 style="color:#e50914"><?= $editUser ? 'Edit user' : 'Add user' ?></h2> <!-- traduit -->
          <?php if ($editUser): ?><a class="btn btn--ghost" href="users.php">Cancel</a><?php endif; ?> <!-- traduit -->
        </div>
        <br>
        <form method="post" class="form-grid">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <?php if ($editUser): ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" value="<?= (int)$editUser['id'] ?>">
          <?php else: ?>
            <input type="hidden" name="action" value="create">
          <?php endif; ?>

          <div>
            <label>Full name <!-- traduit -->
              <input name="fullname" required value="<?= htmlspecialchars($editUser['fullname'] ?? '') ?>">
            </label>
          </div>
          <div>
            <label>Username
              <input name="username" required value="<?= htmlspecialchars($editUser['username'] ?? '') ?>">
            </label>
          </div>
          <div>
            <label>Email
              <input type="email" name="email" required value="<?= htmlspecialchars($editUser['email'] ?? '') ?>">
            </label>
          </div>
          <div>
            <label>Role <!-- traduit -->
              <select name="role">
                <?php
                  $currentUi = role_for_ui($editUser['role'] ?? 'MEMBER'); // ADMIN/COACH/MEMBER
                  // FR: Valeurs envoyées = clés (en anglais), libellés affichés = anglais
                  $options = ['ADMIN'=>'ADMIN','COACH'=>'COACH','MEMBER'=>'MEMBER'];
                  foreach ($options as $val => $label) {
                    $sel = ($currentUi === $val) ? 'selected' : '';
                    echo "<option value=\"$val\" $sel>$label</option>";
                  }
                ?>
              </select>
            </label>
          </div>
          <div class="full">
            <label>Password <?= $editUser ? '<small>(leave empty to keep current)</small>' : '' ?> <!-- traduit -->
              <input type="password" name="password" <?= $editUser ? '' : 'required' ?>>
            </label>
          </div>

          <div class="full" style="display:flex;gap:10px">
            <button class="btn" type="submit"><?= $editUser ? 'Save' : 'Add' ?></button> <!-- traduit -->
          </div>
        </form>
      </div>

      <!-- Liste des utilisateurs -->
      <div class="panel">
        <h2 style="color:#e50914">Users list</h2> <!-- traduit -->
        <br>
        <div class="tablewrap">
          <table class="table-users">
            <thead>
              <tr>
                <th class="col-id">ID</th>
                <th class="col-photo">Photo</th>
                <th>Name</th>        <!-- traduit -->
                <th>Username</th>
                <th>Email</th>
                <th class="col-role">Role</th> <!-- traduit -->
                <th class="col-actions">Actions</th>
              </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u): ?>
              <?php
                $ru = role_for_ui((string)$u['role']); // ADMIN/COACH/MEMBER
                $photo = avatar_url_for_user($u);      // null si ADMIN (place vide)
                $badgeClass = $ru==='ADMIN' ? 'admin' : ($ru==='COACH' ? 'coach' : 'member');
                // FR: Libellé de badge en anglais pour l’UI
                $badgeText  = $ru==='ADMIN' ? 'ADMIN' : ($ru==='COACH' ? 'COACH' : 'MEMBER');
              ?>
              <tr>
                <td><?= (int)$u['id'] ?></td>
                <td style="text-align:center">
                  <?php if ($photo): ?>
                    <img class="thumb" src="<?= htmlspecialchars($photo) ?>" alt="avatar">
                  <?php else: ?>
                    <span class="no-avatar" title="<?= $ru==='ADMIN' ? 'Admin (photo hidden)' : 'No photo' ?>"></span> <!-- traduit -->
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
                    <a class="btn btn--ghost btn--sm" href="users.php?edit=<?= (int)$u['id'] ?>">Edit</a> <!-- traduit -->
                    <form method="post" onsubmit="return confirm('Delete this user?');"> <!-- traduit -->
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                      <button class="btn btn--sm" type="submit" style="background:#333">Delete</button> <!-- traduit -->
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
</html>
