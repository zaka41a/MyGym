<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('COACH','ADMIN');
require_once __DIR__ . '/../backend/db.php';

$coachId   = (int)($_SESSION['user']['id'] ?? 0);
$coachName = $_SESSION['user']['fullname'] ?? 'Coach';
if ($coachId <= 0) { http_response_code(401); exit('Access denied.'); }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

/* ===== Actions (PRG) ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF.');
    }
    $action = $_POST['action'] ?? '';

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
      $date         = trim((string)($_POST['date'] ?? ''));
      $start        = trim((string)($_POST['start'] ?? ''));
      $end          = trim((string)($_POST['end'] ?? ''));
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

/* ===== Data ===== */
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Coach</title>
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
    .nav-link ion-icon { font-size: 1.25rem; }
    .logout-btn {
      display: flex; align-items: center; gap: 1rem; padding: 1rem;
      background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px; color: #9ca3af; text-decoration: none; transition: all 0.3s;
      font-weight: 500; margin-top: 2rem;
    }
    .logout-btn:hover { background: rgba(220, 38, 38, 0.2); color: #fff; border-color: #dc2626; }
    .main-content { margin-left: 280px; flex: 1; padding: 2rem; }
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
    .form-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 1rem; margin-top: 1rem; }
    label { display: block; color: #9ca3af; font-size: 0.85rem; font-weight: 500; margin-bottom: 0.5rem; }
    input, select {
      width: 100%; padding: 0.75rem; background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 10px; color: #f5f7fb;
      font-family: 'Poppins', sans-serif; transition: all 0.3s;
    }
    input:focus, select:focus { outline: none; border-color: #dc2626; background: rgba(255, 255, 255, 0.08); }
    small { color: #6b7280; font-size: 0.75rem; display: block; margin-top: 0.25rem; }
    .btn {
      background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
      color: #fff; border: none; border-radius: 10px; padding: 0.75rem 1.5rem;
      font-weight: 600; cursor: pointer; transition: all 0.3s; font-family: 'Poppins', sans-serif;
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4); }
    .btn-dark { background: rgba(255, 255, 255, 0.1); }
    .btn-dark:hover { background: rgba(255, 255, 255, 0.15); }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    thead td {
      font-weight: 600; color: #9ca3af; font-size: 0.85rem; text-transform: uppercase;
      letter-spacing: 0.05em; padding-bottom: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    tbody tr { border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.2s; }
    tbody tr:hover { background: rgba(220, 38, 38, 0.05); }
    td { padding: 1rem 0.75rem; vertical-align: middle; }
    @media (max-width: 991px) {
      .sidebar { width: 0; opacity: 0; }
      .main-content { margin-left: 0; }
      .form-grid { grid-template-columns: 1fr; }
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
          <li class="nav-item"><a href="courses.php" class="nav-link active"><ion-icon name="calendar"></ion-icon><span>My Classes</span></a></li>
          <li class="nav-item"><a href="members.php" class="nav-link"><ion-icon name="people"></ion-icon><span>My Members</span></a></li>
          <li class="nav-item"><a href="profile.php" class="nav-link"><ion-icon name="person-circle"></ion-icon><span>Profile</span></a></li>
        </ul>
        <a href="/MyGym/backend/logout.php" class="logout-btn"><ion-icon name="log-out"></ion-icon><span>Logout</span></a>
      </nav>
    </aside>
    <main class="main-content">
      <div class="header">
        <h1>My Classes</h1>
        <p style="color: #9ca3af;">Manage your class schedule and availability</p>
      </div>

      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- Add slot form -->
      <div class="section">
        <h2 class="section-title">Add a new slot</h2>
        <form method="post" class="form-grid">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <input type="hidden" name="action" value="create">

          <div>
            <label>Activity (select or create new)</label>
            <select name="activity_id">
              <option value="">—</option>
              <?php foreach ($activities as $a): ?>
                <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
              <?php endforeach; ?>
            </select>
            <small>or enter new activity below</small>
            <input name="activity_name" placeholder="New activity name">
          </div>

          <div>
            <label>Date</label>
            <input type="date" name="date" required>
          </div>
          <div>
            <label>Start time</label>
            <input type="time" name="start" required>
          </div>
          <div>
            <label>End time</label>
            <input type="time" name="end">
          </div>
          <div>
            <label>Capacity</label>
            <input type="number" min="0" name="capacity" value="15">
          </div>

          <div style="grid-column: 1/-1; margin-top: 0.5rem;">
            <button class="btn" type="submit">Add slot</button>
          </div>
        </form>
      </div>

      <!-- Upcoming classes -->
      <div class="section">
        <h2 class="section-title">Upcoming classes</h2>
        <table>
          <thead><tr><td>Date & Time</td><td>Period</td><td>Activity</td><td>Bookings</td><td>Actions</td></tr></thead>
          <tbody>
          <?php if (!$upcoming): ?>
            <tr><td colspan="5" style="color:#6b7280;text-align:center;padding:2rem">No upcoming classes scheduled</td></tr>
          <?php else: foreach ($upcoming as $s): ?>
            <tr>
              <td><?= dmy_hm($s['start_at']) ?></td>
              <td><?= hm_range($s['start_at'],$s['end_at']) ?></td>
              <td><?= htmlspecialchars($s['activity'] ?? '—') ?></td>
              <td><span style="color:#dc2626;font-weight:600"><?= (int)$s['booked'] ?></span> / <?= (int)$s['capacity'] ?></td>
              <td>
                <form method="post" style="display:inline" onsubmit="return confirm('Delete this slot?');">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                  <button class="btn btn-dark" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Past classes -->
      <div class="section">
        <h2 class="section-title">Past classes (last 20)</h2>
        <table>
          <thead><tr><td>Date & Time</td><td>Period</td><td>Activity</td><td>Attendees</td></tr></thead>
          <tbody>
          <?php if (!$past): ?>
            <tr><td colspan="4" style="color:#6b7280;text-align:center;padding:2rem">No past classes</td></tr>
          <?php else: foreach ($past as $s): ?>
            <tr>
              <td><?= dmy_hm($s['start_at']) ?></td>
              <td><?= hm_range($s['start_at'],$s['end_at']) ?></td>
              <td><?= htmlspecialchars($s['activity'] ?? '—') ?></td>
              <td><?= (int)$s['booked'] ?> / <?= (int)$s['capacity'] ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
