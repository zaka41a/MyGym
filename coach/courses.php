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

$upcomingCount = count($upcoming);
$pastCount = count($past);
$totalUpcomingCapacity = 0;
$totalUpcomingBooked = 0;
foreach ($upcoming as $row) {
  $totalUpcomingCapacity += (int)($row['capacity'] ?? 0);
  $totalUpcomingBooked += (int)($row['booked'] ?? 0);
}
$utilization = $totalUpcomingCapacity > 0
  ? (int)round(($totalUpcomingBooked / $totalUpcomingCapacity) * 100)
  : 0;
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
          <li class="nav-item"><a href="courses.php" class="nav-link active"><ion-icon name="calendar"></ion-icon><span>My Classes</span></a></li>
          <li class="nav-item"><a href="members.php" class="nav-link"><ion-icon name="people"></ion-icon><span>My Members</span></a></li>
          <li class="nav-item"><a href="profile.php" class="nav-link"><ion-icon name="person-circle"></ion-icon><span>Profile</span></a></li>
        </ul>
        <a href="/MyGym/backend/logout.php" class="logout-btn"><ion-icon name="log-out"></ion-icon><span>Logout</span></a>
      </nav>
    </aside>
    <main class="main-content">
      <div class="header">
        <div>
          <h1>My Classes</h1>
          <p style="color:#9ca3af;">Manage your schedule, capacity, and attendance.</p>
        </div>
        <div class="header-date">
          <ion-icon name="calendar-outline"></ion-icon>
          <span><?= date('l, F j') ?></span>
        </div>
      </div>

      <!-- Schedule Overview Hero -->
      <div class="schedule-hero">
        <div class="schedule-stat">
          <div class="schedule-stat-icon" style="--color: #6366f1;">
            <ion-icon name="calendar"></ion-icon>
          </div>
          <div class="schedule-stat-info">
            <div class="schedule-stat-value"><?= $upcomingCount ?></div>
            <div class="schedule-stat-label">Upcoming</div>
          </div>
        </div>
        <div class="schedule-divider"></div>
        <div class="schedule-stat">
          <div class="schedule-stat-icon" style="--color: #8b5cf6;">
            <ion-icon name="time"></ion-icon>
          </div>
          <div class="schedule-stat-info">
            <div class="schedule-stat-value"><?= $pastCount ?></div>
            <div class="schedule-stat-label">Completed</div>
          </div>
        </div>
        <div class="schedule-divider"></div>
        <div class="schedule-stat">
          <div class="schedule-stat-icon" style="--color: #ec4899;">
            <ion-icon name="people"></ion-icon>
          </div>
          <div class="schedule-stat-info">
            <div class="schedule-stat-value"><?= $utilization ?>%</div>
            <div class="schedule-stat-label">Utilization</div>
          </div>
        </div>
        <div class="schedule-divider"></div>
        <div class="schedule-stat">
          <div class="schedule-stat-icon" style="--color: #10b981;">
            <ion-icon name="checkmark-circle"></ion-icon>
          </div>
          <div class="schedule-stat-info">
            <div class="schedule-stat-value"><?= (int)max(0, $totalUpcomingCapacity - $totalUpcomingBooked) ?></div>
            <div class="schedule-stat-label">Available Seats</div>
          </div>
        </div>
      </div>

      <?php if ($ok): ?><div class="alert alert-success"><ion-icon name="checkmark-circle"></ion-icon><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-error"><ion-icon name="alert-circle"></ion-icon><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- Professional Add Slot Form -->
      <div class="add-slot-card">
        <div class="add-slot-header">
          <div class="add-slot-title-wrapper">
            <div class="add-slot-icon">
              <ion-icon name="add-circle-outline"></ion-icon>
            </div>
            <div>
              <h2 class="add-slot-title">Create New Session</h2>
              <p class="add-slot-subtitle">Schedule a new class for your members</p>
            </div>
          </div>
          <div class="add-slot-badge">
            <ion-icon name="sparkles-outline"></ion-icon>
            <span>Quick Add</span>
          </div>
        </div>

        <form method="post" class="add-slot-form">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
          <input type="hidden" name="action" value="create">

          <!-- Activity Selection Section -->
          <div class="form-section">
            <div class="form-section-label">
              <ion-icon name="barbell-outline"></ion-icon>
              <span>Activity Type</span>
            </div>

            <div class="activity-selector">
              <div class="input-wrapper">
                <div class="input-icon">
                  <ion-icon name="list-outline"></ion-icon>
                </div>
                <select name="activity_id" id="activitySelect" class="styled-select" onchange="toggleNewActivity(this.value)">
                  <option value="">Select existing activity</option>
                  <?php foreach ($activities as $a): ?>
                    <option value="<?= (int)$a['id'] ?>"><?= htmlspecialchars($a['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="activity-divider">
                <span>or</span>
              </div>

              <div class="input-wrapper" id="newActivityWrapper">
                <div class="input-icon">
                  <ion-icon name="create-outline"></ion-icon>
                </div>
                <input
                  type="text"
                  name="activity_name"
                  id="newActivityInput"
                  class="styled-input"
                  placeholder="Create new activity (e.g., Yoga, Spinning)"
                  onfocus="document.getElementById('activitySelect').value=''"
                >
              </div>
            </div>
          </div>

          <!-- Date & Time Section -->
          <div class="form-section">
            <div class="form-section-label">
              <ion-icon name="time-outline"></ion-icon>
              <span>Date & Time</span>
            </div>

            <div class="datetime-grid">
              <div class="input-wrapper">
                <div class="input-icon">
                  <ion-icon name="calendar-outline"></ion-icon>
                </div>
                <div class="input-content">
                  <label class="floating-label">Session Date</label>
                  <input type="date" name="date" class="styled-input" required>
                </div>
              </div>

              <div class="input-wrapper">
                <div class="input-icon">
                  <ion-icon name="play-outline"></ion-icon>
                </div>
                <div class="input-content">
                  <label class="floating-label">Start Time</label>
                  <input type="time" name="start" class="styled-input" required>
                </div>
              </div>

              <div class="input-wrapper">
                <div class="input-icon">
                  <ion-icon name="stop-outline"></ion-icon>
                </div>
                <div class="input-content">
                  <label class="floating-label">End Time</label>
                  <input type="time" name="end" class="styled-input">
                </div>
              </div>
            </div>
          </div>

          <!-- Capacity Section -->
          <div class="form-section">
            <div class="form-section-label">
              <ion-icon name="people-outline"></ion-icon>
              <span>Capacity</span>
            </div>

            <div class="capacity-selector">
              <div class="input-wrapper">
                <div class="input-icon">
                  <ion-icon name="person-outline"></ion-icon>
                </div>
                <div class="input-content">
                  <label class="floating-label">Max Participants</label>
                  <input type="number" min="1" max="50" name="capacity" value="15" class="styled-input" required>
                </div>
              </div>
              <div class="capacity-presets">
                <button type="button" class="preset-btn" onclick="document.querySelector('input[name=capacity]').value=10">10</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('input[name=capacity]').value=15">15</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('input[name=capacity]').value=20">20</button>
                <button type="button" class="preset-btn" onclick="document.querySelector('input[name=capacity]').value=30">30</button>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="form-actions">
            <button type="submit" class="btn-create-slot">
              <ion-icon name="checkmark-circle-outline"></ion-icon>
              <span>Create Session</span>
              <div class="btn-shine"></div>
            </button>
          </div>
        </form>
      </div>

      <script>
        function toggleNewActivity(value) {
          const newInput = document.getElementById('newActivityInput');
          if (value !== '') {
            newInput.value = '';
          }
        }
      </script>

      <div class="section activity-section">
        <div class="section-header">
          <h2 class="section-title">
            <ion-icon name="calendar-outline"></ion-icon>
            Upcoming classes
          </h2>
        </div>
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
                  <button class="btn btn-ghost" type="submit">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <div class="section activity-section">
        <div class="section-header">
          <h2 class="section-title">
            <ion-icon name="time"></ion-icon>
            Past classes (last 20)
          </h2>
        </div>
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
