<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../backend/auth.php';
requireRole('COACH','ADMIN'); // FR: Admin autorisé à accéder (debug)
require_once __DIR__ . '/../backend/db.php';

/* -------- Contexte utilisateur --------
   FR: Récupère l'identité du coach connecté pour l'affichage/topbar */
$coachId   = (int)($_SESSION['user']['id'] ?? 0);
$coachName = $_SESSION['user']['fullname'] ?? 'Coach';
if ($coachId <= 0) { http_response_code(401); exit('Access denied.'); } // traduit (affiché)

/* FR: CSRF pour sécuriser les formulaires */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

/* -------- Avatar (BDD -> fichiers) --------
   FR: Construit l’URL de l’avatar (BDD, puis fichiers, sinon placeholder) */
$rootDir      = dirname(__DIR__);
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';
$avatarUrl    = null;
try {
  $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id=:id");
  $stmt->execute([':id'=>$coachId]);
  $avatarDb = (string)($stmt->fetchColumn() ?: '');
  if ($avatarDb !== '') $avatarUrl = $uploadDirWeb.'/'.basename($avatarDb).'?t='.time();
} catch(Throwable $e){}
if (!$avatarUrl) {
  foreach (['jpg','png','webp'] as $ext) {
    if (is_file($uploadDirFS."/user_{$coachId}.{$ext}")) {
      $avatarUrl = $uploadDirWeb."/user_{$coachId}.{$ext}?t=".time(); break;
    }
  }
}
if (!$avatarUrl) $avatarUrl = 'https://via.placeholder.com/36x36?text=%20';

/* FR: Messages (affichage en haut de page) */
$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

/* ===== Actions (PRG) =====
   FR: Créer / modifier / supprimer un créneau ; validation + redirection */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF.');
    }
    $action = $_POST['action'] ?? '';

    // FR: Crée ou récupère une activité selon l'ID ou le nom saisi
    $ensureActivity = function(PDO $pdo, ?int $activityId, string $activityName): int {
      $aid = (int)($activityId ?? 0);
      $name = trim($activityName);
      if ($aid > 0) return $aid;
      if ($name === '') throw new RuntimeException("Select an activity or enter a new one.");
      $st = $pdo->prepare("INSERT INTO activities(name) VALUES(:n)");
      $st->execute([':n'=>$name]);
      return (int)$pdo->lastInsertId();
    };

    if ($action === 'create') {
      $activityId   = (int)($_POST['activity_id'] ?? 0);
      $activityName = (string)($_POST['activity_name'] ?? '');
      $date         = trim((string)($_POST['date'] ?? ''));      // YYYY-MM-DD
      $start        = trim((string)($_POST['start'] ?? ''));     // HH:MM
      $end          = trim((string)($_POST['end'] ?? ''));       // HH:MM
      $capacity     = max(0, (int)($_POST['capacity'] ?? 0));

      if ($date==='' || $start==='') throw new RuntimeException('Start date/time is required.');
      $aid = $ensureActivity($pdo, $activityId, $activityName);

      $startAt = "{$date} {$start}:00";
      $endAt   = ($end!=='') ? "{$date} {$end}:00" : null;
      if ($endAt && strtotime($endAt) <= strtotime($startAt)) {
        throw new RuntimeException("End must be after start.");
      }

      $st = $pdo->prepare("
        INSERT INTO sessions(activity_id, coach_id, start_at, end_at, capacity)
        VALUES(:a,:c,:s,:e,:cap)
      ");
      $st->execute([':a'=>$aid, ':c'=>$coachId, ':s'=>$startAt, ':e'=>$endAt, ':cap'=>$capacity]);

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Slot added.'));
      exit;
    }

    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) throw new RuntimeException('Invalid ID.');
      $st = $pdo->prepare("DELETE FROM sessions WHERE id=:id AND coach_id=:c");
      $st->execute([':id'=>$id, ':c'=>$coachId]);
      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Slot deleted.'));
      exit;
    }

    if ($action === 'update') {
      $id           = (int)($_POST['id'] ?? 0);
      $activityId   = (int)($_POST['activity_id'] ?? 0);
      $activityName = (string)($_POST['activity_name'] ?? '');
      $date         = trim((string)($_POST['date'] ?? ''));
      $start        = trim((string)($_POST['start'] ?? ''));
      $end          = trim((string)($_POST['end'] ?? ''));
      $capacity     = max(0, (int)($_POST['capacity'] ?? 0));
      if ($id<=0) throw new RuntimeException('Invalid ID.');
      if ($date==='' || $start==='') throw new RuntimeException('Start date/time is required.');
      $aid = $ensureActivity($pdo, $activityId, $activityName);

      $startAt = "{$date} {$start}:00";
      $endAt   = ($end!=='') ? "{$date} {$end}:00" : null;
      if ($endAt && strtotime($endAt) <= strtotime($startAt)) {
        throw new RuntimeException("End must be after start.");
      }

      $st = $pdo->prepare("
        UPDATE sessions
           SET activity_id=:a, start_at=:s, end_at=:e, capacity=:cap
         WHERE id=:id AND coach_id=:c
      ");
      $st->execute([':a'=>$aid, ':s'=>$startAt, ':e'=>$endAt, ':cap'=>$capacity, ':id'=>$id, ':c'=>$coachId]);
      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Slot updated.'));
      exit;
    }

    throw new RuntimeException('Unknown action.');
  } catch(Throwable $e) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='.urlencode($e->getMessage()));
    exit;
  }
}

/* ===== Données =====
   FR: Charge la liste des activités + sessions futures et passées */
$activities = [];
try {
  $activities = $pdo->query("SELECT id, name FROM activities ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){ $activities=[]; }

$upcoming = [];
$past     = [];
try {
  $st = $pdo->prepare("
    SELECT s.id, s.start_at, s.end_at, s.capacity,
           a.name AS activity,
           (SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id) AS booked
    FROM sessions s
    LEFT JOIN activities a ON a.id = s.activity_id
    WHERE s.coach_id = :c AND s.start_at >= NOW()
    ORDER BY s.start_at ASC
  ");
  $st->execute([':c'=>$coachId]);
  $upcoming = $st->fetchAll(PDO::FETCH_ASSOC);

  $st2 = $pdo->prepare("
    SELECT s.id, s.start_at, s.end_at, s.capacity,
           a.name AS activity,
           (SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id) AS booked
    FROM sessions s
    LEFT JOIN activities a ON a.id = s.activity_id
    WHERE s.coach_id = :c AND s.start_at < NOW()
    ORDER BY s.start_at DESC
    LIMIT 20
  ");
  $st2->execute([':c'=>$coachId]);
  $past = $st2->fetchAll(PDO::FETCH_ASSOC);
} catch(Throwable $e){
  $upcoming = $past = [];
}

/* FR: Helpers d’affichage (dates/horaires) */
function dmy_hm(?string $dt): string {
  if (!$dt) return '—';
  try { return (new DateTime($dt))->format('d/m/Y H:i'); } catch(Throwable $e){ return '—'; }
}
function hm_range(?string $s, ?string $e): string {
  try {
    $ss = $s ? (new DateTime($s))->format('H:i') : '—';
    $ee = $e ? (new DateTime($e))->format('H:i') : '';
    return $ee ? "$ss → $ee" : $ss;
  } catch(Throwable $e){ return '—'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Classes — Coach</title> <!-- FR: Titre de l’onglet -->
  <!-- FR: Icônes -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <!-- FR: Styles intégrés -->
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap");
    :root{--primary:#e50914;--primary-600:#cc0812;--white:#fff;--gray:#f5f5f5;--border:#e9e9e9;--shadow:0 7px 25px rgba(0,0,0,.08)}
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Ubuntu",system-ui,Segoe UI,Roboto,sans-serif}
    body{background:var(--gray)}
    .navigation{position:fixed;width:300px;height:100%;background:var(--primary);box-shadow:var(--shadow)}
    .navigation ul{position:absolute;inset:0} .navigation li{list-style:none}
    .navigation a{display:flex;height:60px;align-items:center;color:#fff;text-decoration:none;padding-left:10px}
    .navigation .icon{min-width:50px;text-align:center}
    .navigation li:hover,.navigation li.active{background:var(--primary-600)}
    .main{position:absolute;left:300px;width:calc(100% - 300px);min-height:100vh;background:#fff}
    .topbar{height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid var(--border)}
    .title-top{color:var(--primary);font-weight:700}
    .avatar{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #eee}
    .wrap{max-width:1100px;margin:0 auto;padding:20px}
    .alert{padding:10px;border-radius:10px;margin:10px 0}
    .ok{background:#e8f5e9;border:1px solid #c8e6c9}
    .err{background:#fdecea;border:1px solid #f5c6cb}
    .panel{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow);margin-top:18px}
    .cardHeader{display:flex;justify-content:space-between;align-items:center}
    .btn{background:var(--primary);color:#fff;border:0;border-radius:10px;padding:.45rem .9rem;font-weight:700;cursor:pointer;text-decoration:none}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    thead td{font-weight:700} tr{border-bottom:1px solid #eee}
    td{padding:10px;vertical-align:middle}
    input,select{width:100%;padding:10px;border:1px solid var(--border);border-radius:10px;margin-top:6px}
    .form-grid{display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr;gap:10px}
    @media (max-width:991px){.main{left:0;width:100%}.form-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="container">
  <!-- FR: Navigation latérale (libellés traduits) -->
  <div class="navigation">
    <ul>
      <li><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li class="active"><a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">My Classes</span></a></li> <!-- traduit -->
      <li><a href="members.php"><span class="icon"><ion-icon name="people-outline"></ion-icon></span><span class="title">My Members</span></a></li> <!-- traduit -->
      <li><a href="profile.php"><span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span><span class="title">Profile</span></a></li> <!-- traduit -->
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Logout</span></a></li> <!-- traduit -->
    </ul>
  </div>

  <!-- FR: Topbar avec avatar -->
  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div style="display:flex;align-items:center;gap:10px">
        <div class="title-top">My Classes — <?= htmlspecialchars($coachName) ?></div> <!-- traduit -->
        <a href="profile.php"><img class="avatar" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar"></a>
      </div>
    </div>

    <div class="wrap">
      <!-- FR: Messages de retour (succès/erreur) -->
      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- Formulaire création -->
      <div class="panel">
        <div class="cardHeader"><h2 style="margin:0">Add a slot</h2></div> <!-- traduit -->
        <form method="post" class="form-grid" style="margin-top:8px">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <input type="hidden" name="action" value="create">

          <div>
            <label>Activity (select)</label> <!-- traduit -->
            <select name="activity_id">
              <option value="">—</option>
              <?php foreach ($activities as $a): ?>
                <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <small style="color:#666">or enter below</small> <!-- traduit -->
            <input name="activity_name" placeholder="New activity (optional)"> <!-- traduit -->
          </div>

          <div>
            <label>Date</label>
            <input type="date" name="date" required>
          </div>
          <div>
            <label>Start</label> <!-- traduit -->
            <input type="time" name="start" required>
          </div>
          <div>
            <label>End</label> <!-- traduit -->
            <input type="time" name="end">
          </div>
          <div>
            <label>Capacity</label> <!-- traduit -->
            <input type="number" min="0" name="capacity" value="15">
          </div>

          <div style="grid-column:1/-1">
            <button class="btn" type="submit">Add</button> <!-- traduit -->
          </div>
        </form>
      </div>

      <!-- Prochains cours -->
      <div class="panel">
        <div class="cardHeader"><h2 style="margin:0">Upcoming classes</h2></div> <!-- traduit -->
        <table>
          <thead><tr><td>Date</td><td>Period</td><td>Type</td><td>Members</td><td>Action</td></tr></thead> <!-- traduit -->
          <tbody>
          <?php if (!$upcoming): ?>
            <tr><td colspan="5" style="color:#666;text-align:center">No classes scheduled.</td></tr> <!-- traduit -->
          <?php else: foreach ($upcoming as $s): ?>
            <tr>
              <td><?= dmy_hm($s['start_at']) ?></td>
              <td><?= hm_range($s['start_at'],$s['end_at']) ?></td>
              <td><?= htmlspecialchars($s['activity'] ?? '—') ?></td>
              <td><?= (int)$s['booked'] ?>/<?= (int)$s['capacity'] ?></td>
              <td>
                <div style="display:flex;gap:8px;justify-content:flex-end">
                  <form method="post" onsubmit="return confirm('Delete this slot?');"> <!-- traduit -->
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button class="btn" type="submit" style="background:#333">Delete</button> <!-- traduit -->
                  </form>
                </div>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Passés (dernier 20) -->
      <div class="panel">
        <div class="cardHeader"><h2 style="margin:0">Past classes</h2></div> <!-- traduit -->
        <table>
          <thead><tr><td>Date</td><td>Period</td><td>Type</td><td>Attendees</td></tr></thead> <!-- traduit -->
          <tbody>
          <?php if (!$past): ?>
            <tr><td colspan="4" style="color:#666;text-align:center">—</td></tr>
          <?php else: foreach ($past as $s): ?>
            <tr>
              <td><?= dmy_hm($s['start_at']) ?></td>
              <td><?= hm_range($s['start_at'],$s['end_at']) ?></td>
              <td><?= htmlspecialchars($s['activity'] ?? '—') ?></td>
              <td><?= (int)$s['booked'] ?>/<?= (int)$s['capacity'] ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</body>
</html>
