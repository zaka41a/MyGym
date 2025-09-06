<?php
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../backend/auth.php';
requireRole('MEMBER','ADMIN'); // ⚠️ Vérifie le rôle de l’utilisateur
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/subscriptions.php'; // Pour has_class_access()

// ===== CSRF =====
// Génère un token unique pour sécuriser les formulaires
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

$userId   = (int)($_SESSION['user']['id'] ?? 0);
$userName = (string)($_SESSION['user']['fullname'] ?? 'Member');

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

/* ===== Vérifie l'accès aux réservations =====
   Les offres PLUS/PRO donnent droit à réserver un cours */
$canBook = false;
try { $canBook = has_class_access($pdo, $userId); } catch (Throwable $e) { $canBook = false; }

/* ===== Actions (réserver / annuler) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF token.');
    }
    if (!$canBook) throw new RuntimeException("Your plan does not allow class reservations.");

    $action    = (string)($_POST['action'] ?? '');
    $sessionId = (int)($_POST['session_id'] ?? 0);
    if ($sessionId <= 0) throw new RuntimeException('Invalid session.');

    if ($action === 'reserve') {
      // Vérifie si déjà réservé
      $st = $pdo->prepare("SELECT 1 FROM reservations WHERE user_id=:u AND session_id=:s AND status='BOOKED' LIMIT 1");
      $st->execute([':u'=>$userId, ':s'=>$sessionId]);
      if ($st->fetchColumn()) throw new RuntimeException('Already reserved.');

      // Vérifie la capacité de la séance
      $st = $pdo->prepare("
        SELECT s.capacity,
               COALESCE(SUM(CASE WHEN r.status='BOOKED' THEN 1 ELSE 0 END),0) AS reserved
          FROM sessions s
          LEFT JOIN reservations r ON r.session_id = s.id
         WHERE s.id=:id
         GROUP BY s.capacity
      ");
      $st->execute([':id'=>$sessionId]);
      $row = $st->fetch(PDO::FETCH_ASSOC);
      if (!$row) throw new RuntimeException('Session not found.');
      if ((int)$row['reserved'] >= (int)$row['capacity']) throw new RuntimeException('This session is full.');

      // Insère la réservation
      $st = $pdo->prepare("INSERT INTO reservations (user_id, session_id, status, created_at) VALUES (:u,:s,'BOOKED',NOW())");
      $st->execute([':u'=>$userId, ':s'=>$sessionId]);

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Reservation confirmed.'));
      exit;
    }

    if ($action === 'cancel') {
      // Supprime la réservation
      $st = $pdo->prepare("DELETE FROM reservations WHERE user_id=:u AND session_id=:s AND status='BOOKED'");
      $st->execute([':u'=>$userId, ':s'=>$sessionId]);
      if ($st->rowCount() === 0) throw new RuntimeException('No reservation to cancel.');
      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Reservation canceled.'));
      exit;
    }

    throw new RuntimeException('Unknown action.');
  } catch (Throwable $e) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='.urlencode($e->getMessage()));
    exit;
  }
}

/* ===== Récupère les séances disponibles ===== */
$sql = "
  SELECT s.id, s.start_at, s.end_at, s.capacity,
         a.name AS activity,
         u.fullname AS coach,
         COALESCE(SUM(CASE WHEN r.status='BOOKED' THEN 1 ELSE 0 END),0) AS reserved,
         EXISTS(SELECT 1 FROM reservations rr WHERE rr.user_id=:uid AND rr.session_id=s.id AND rr.status='BOOKED') AS my_reserved
    FROM sessions s
    JOIN activities a ON a.id = s.activity_id
    JOIN users u      ON u.id = s.coach_id
    LEFT JOIN reservations r ON r.session_id = s.id
   WHERE s.start_at >= NOW()
GROUP BY s.id, s.start_at, s.end_at, s.capacity, a.name, u.fullname
ORDER BY s.start_at ASC
";
$st = $pdo->prepare($sql);
$st->execute([':uid'=>$userId]);
$sessions = $st->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Classes — MyGym</title>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap");
    :root{--primary:#e50914;--primary-600:#cc0812;--white:#fff;--gray:#f5f5f5;--border:#e9e9e9;--black1:#222;--black2:#999;--shadow:0 7px 25px rgba(0,0,0,.08)}
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
    .wrap{max-width:1000px;margin:0 auto;padding:20px}
    .alert{padding:10px 12px;border-radius:8px;margin:10px 0}
    .ok{background:#e8f5e9;border:1px solid #c8e6c9} .err{background:#fdecea;border:1px solid #f5c6cb}
    table{width:100%;border-collapse:collapse;background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;box-shadow:var(--shadow)}
    thead td{font-weight:700;background:#fafafa}
    td{padding:12px;border-bottom:1px solid #eee;vertical-align:middle}
    .btn{display:inline-flex;align-items:center;gap:6px;background:var(--primary);color:#fff;border:0;border-radius:8px;padding:.45rem .9rem;font-weight:600;cursor:pointer;text-decoration:none}
    .btn--ghost{background:#333}
    .muted{color:#666}
    .pill{display:inline-flex;align-items:center;gap:4px;padding:2px 10px;border-radius:999px;background:#f3f3f3;font-weight:700}
    @media (max-width:900px){.main{left:0;width:100%}}
  </style>
</head>
<body>
<div class="container">
  <div class="navigation">
    <ul>
      <li><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li class="active">
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
      <li><a href="profile.php"><span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span><span class="title">Profile</span></a></li>
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Sign out</span></a></li>
    </ul>
  </div>

  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div style="font-weight:700;color:#e50914">My Classes</div>
      <div style="color:#e50914;font-weight:700">Hello, <?= htmlspecialchars($userName) ?></div>
    </div>

    <div class="wrap">
      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <h2 style="margin:0 0 10px">Upcoming Sessions</h2>
      <table>
        <thead>
          <tr>
            <td>Start</td><td>End</td><td>Class</td><td>Coach</td>
            <td>Capacity</td><td>Booked</td><td>Remaining</td><td>Action</td>
          </tr>
        </thead>
        <tbody>
        <?php if (!$sessions): ?>
          <tr><td colspan="8" class="muted" style="text-align:center">No upcoming sessions.</td></tr>
        <?php else: foreach ($sessions as $s):
          $cap   = (int)$s['capacity'];
          $res   = (int)$s['reserved'];
          $left  = max(0, $cap - $res);
          $mine  = ((int)$s['my_reserved'] === 1);
        ?>
          <tr>
            <td><?= date('d/m H:i', strtotime((string)$s['start_at'])) ?></td>
            <td><?= date('d/m H:i', strtotime((string)$s['end_at'])) ?></td>
            <td><?= htmlspecialchars($s['activity']) ?></td>
            <td><?= htmlspecialchars($s['coach']) ?></td>
            <td><span class="pill"><ion-icon name="people-outline"></ion-icon><?= $cap ?></span></td>
            <td><span class="pill"><ion-icon name="checkmark-done-outline"></ion-icon><?= $res ?></span></td>
            <td><span class="pill"><ion-icon name="hourglass-outline"></ion-icon><?= $left ?></span></td>
            <td>
              <?php if (!$canBook): ?>
                <span class="muted"><ion-icon name="lock-closed-outline"></ion-icon> Locked</span>
              <?php elseif ($mine): ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="action" value="cancel">
                  <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                  <button class="btn btn--ghost" type="submit">
                    <ion-icon name="close-circle-outline"></ion-icon> Cancel
                  </button>
                </form>
              <?php elseif ($left <= 0): ?>
                <span class="muted"><ion-icon name="time-outline"></ion-icon> Full</span>
              <?php else: ?>
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="action" value="reserve">
                  <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                  <button class="btn" type="submit">
                    <ion-icon name="checkmark-circle-outline"></ion-icon> Reserve
                  </button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>
