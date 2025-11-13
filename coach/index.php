<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('COACH','ADMIN');
require_once __DIR__ . '/../backend/db.php';

// Get coach info
$coachId   = (int)($_SESSION['user']['id'] ?? 0);
$coachName = $_SESSION['user']['fullname'] ?? 'Coach';
if ($coachId <= 0) { http_response_code(401); exit('Access denied.'); }

// Avatar
$rootDir      = dirname(__DIR__);
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';
$avatarUrl    = null;

try {
  $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id=:id");
  $stmt->execute([':id'=>$coachId]);
  $avatarDb = (string)($stmt->fetchColumn() ?: '');
  if ($avatarDb !== '') { $avatarUrl = $uploadDirWeb . '/' . basename($avatarDb) . '?t=' . time(); }
} catch (Throwable $e) { }
if (!$avatarUrl) {
  foreach (['jpg','png','webp'] as $ext) {
    $p = $uploadDirFS . "/user_{$coachId}.{$ext}";
    if (is_file($p)) { $avatarUrl = $uploadDirWeb . "/user_{$coachId}.{$ext}?t=" . time(); break; }
  }
}
if (!$avatarUrl) { $avatarUrl = 'https://via.placeholder.com/36x36?text=%20'; }

const COACH_MAX_MEMBERS = 5;

// Assigned members
$assignedMembers = [];
$assignedCount   = 0;
try {
  $assignedMembers = $pdo->prepare("
    SELECT u.id, u.fullname, u.email
    FROM coach_members cm
    JOIN users u ON u.id = cm.member_id
    WHERE cm.coach_id = :c
    ORDER BY u.fullname ASC
  ");
  $assignedMembers->execute([':c'=>$coachId]);
  $assignedMembers = $assignedMembers->fetchAll(PDO::FETCH_ASSOC);
  $assignedCount   = count($assignedMembers);
} catch (Throwable $e) {
  $assignedMembers = [];
  $assignedCount   = 0;
}

// Subscribed assigned members
$subscribedAssignedCount = 0;
$membersWithStatus = [];
try {
  $stmt = $pdo->prepare("
    SELECT u.id, u.fullname,
           EXISTS(
             SELECT 1 FROM subscriptions s
             WHERE s.user_id = u.id
               AND s.status = 'ACTIVE'
               AND s.approved_by IS NOT NULL
               AND (s.end_date IS NULL OR s.end_date >= CURRENT_DATE())
           ) AS has_active_sub
    FROM coach_members cm
    JOIN users u ON u.id = cm.member_id
    WHERE cm.coach_id = :c
    ORDER BY u.fullname ASC
  ");
  $stmt->execute([':c'=>$coachId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) {
    $ok = (int)$r['has_active_sub'] === 1;
    $membersWithStatus[] = ['id'=>$r['id'], 'fullname'=>$r['fullname'], 'active'=>$ok];
    if ($ok) $subscribedAssignedCount++;
  }
} catch (Throwable $e) {
  foreach ($assignedMembers as $m) {
    $membersWithStatus[] = ['id'=>$m['id'], 'fullname'=>$m['fullname'], 'active'=>false];
  }
  $subscribedAssignedCount = 0;
}

$spotsLeft = max(0, COACH_MAX_MEMBERS - $assignedCount);

// Next session
$nextSession = null;
try {
  $stmt = $pdo->prepare("
    SELECT s.id, s.start_at, s.end_at, s.capacity,
           a.name AS activity,
           (SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id) AS booked
    FROM sessions s
    LEFT JOIN activities a ON a.id = s.activity_id
    WHERE s.coach_id = :c
      AND s.start_at >= NOW()
    ORDER BY s.start_at ASC
    LIMIT 1
  ");
  $stmt->execute([':c'=>$coachId]);
  $nextSession = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) { $nextSession = null; }

// Upcoming sessions
$upcoming = [];
try {
  $stmt = $pdo->prepare("
    SELECT s.id, s.start_at, s.end_at, s.capacity,
           a.name AS activity,
           u.fullname AS coach_name,
           (SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id) AS booked
    FROM sessions s
    LEFT JOIN activities a ON a.id = s.activity_id
    LEFT JOIN users u ON u.id = s.coach_id
    WHERE s.coach_id = :c
      AND s.start_at >= NOW()
    ORDER BY s.start_at ASC
    LIMIT 5
  ");
  $stmt->execute([':c'=>$coachId]);
  $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $upcoming = [];
}

function period_label(?string $start, ?string $end): string {
  if (!$start) return '—';
  try {
    $s = new DateTime($start);
    if ($end) {
      $e = new DateTime($end);
      return $s->format('H:i') . ' → ' . $e->format('H:i');
    }
    return $s->format('H:i');
  } catch (Throwable $e) {
    return '—';
  }
}

$assignedCount = count($membersWithStatus);
$availabilityPercent = COACH_MAX_MEMBERS > 0
  ? (int)max(0, min(100, round(($spotsLeft / COACH_MAX_MEMBERS) * 100)))
  : 0;
$nextClassTime = 'No class scheduled';
$nextClassActivity = $nextSession['activity'] ?? '—';
if ($nextSession && !empty($nextSession['start_at'])) {
  try {
    $nextClassTime = (new DateTime($nextSession['start_at']))->format('M d • H:i');
  } catch (Throwable $e) {
    $nextClassTime = 'No class scheduled';
  }
}
$memberEngagement = $assignedCount > 0
  ? (int)round(($subscribedAssignedCount / max($assignedCount, 1)) * 100)
  : 0;
$coachingSpark = [];
$sparkLabels = ['Mon','Tue','Wed','Thu','Fri','Sat'];
$baseSpark = min(90, max(25, count($upcoming) * 12 + $assignedCount * 3));
foreach ($sparkLabels as $idx => $label) {
  $offset = ($idx - 2) * 5;
  $value = max(18, min(92, $baseSpark + $offset));
  $coachingSpark[] = ['label' => $label, 'value' => $value];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Coach Dashboard</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <?php include __DIR__ . '/../shared/coach-styles.php'; ?>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
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
          <li class="nav-item">
            <a href="index.php" class="nav-link active">
              <ion-icon name="grid"></ion-icon>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="courses.php" class="nav-link">
              <ion-icon name="calendar"></ion-icon>
              <span>My Classes</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="members.php" class="nav-link">
              <ion-icon name="people"></ion-icon>
              <span>My Members</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="profile.php" class="nav-link">
              <ion-icon name="person-circle"></ion-icon>
              <span>Profile</span>
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
        <div>
          <h1>Welcome back, <?= htmlspecialchars($coachName) ?>!</h1>
          <p>Keep your sessions full and members engaged.</p>
        </div>
        <div class="header-date">
          <ion-icon name="calendar-outline"></ion-icon>
          <span><?= date('l, F j') ?></span>
        </div>
      </div>

      <!-- Beautiful Stats Cards -->
      <div class="coach-hero-stats">
        <!-- Next Class Card -->
        <div class="hero-stat-card next-class-card">
          <div class="hero-stat-header">
            <div class="hero-stat-icon-wrapper" style="background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);">
              <ion-icon name="time-outline"></ion-icon>
            </div>
            <div class="hero-stat-badge badge-primary">
              <ion-icon name="flash"></ion-icon>
              <span>Next Up</span>
            </div>
          </div>
          <div class="hero-stat-content">
            <div class="hero-stat-label">Next Class</div>
            <div class="hero-stat-value"><?= htmlspecialchars($nextClassTime) ?></div>
            <div class="hero-stat-meta">
              <ion-icon name="barbell-outline"></ion-icon>
              <span><?= htmlspecialchars($nextClassActivity ?: 'No activity planned') ?></span>
            </div>
          </div>
          <?php if ($nextSession): ?>
            <div class="hero-stat-footer">
              <div class="capacity-indicator">
                <span class="capacity-text"><?= (int)($nextSession['booked'] ?? 0) ?>/<?= (int)($nextSession['capacity'] ?? 0) ?></span>
                <span class="capacity-label">Booked</span>
              </div>
              <div class="progress-ring">
                <svg width="46" height="46" viewBox="0 0 46 46">
                  <circle cx="23" cy="23" r="20" fill="none" stroke="#e5e7eb" stroke-width="3"/>
                  <circle cx="23" cy="23" r="20" fill="none" stroke="url(#gradient1)" stroke-width="3"
                          stroke-dasharray="<?= 2 * 3.14159 * 20 ?>"
                          stroke-dashoffset="<?= 2 * 3.14159 * 20 * (1 - min(1, (int)($nextSession['booked'] ?? 0) / max(1, (int)($nextSession['capacity'] ?? 1)))) ?>"
                          transform="rotate(-90 23 23)"/>
                  <defs>
                    <linearGradient id="gradient1" x1="0%" y1="0%" x2="100%" y2="100%">
                      <stop offset="0%" stop-color="#6366f1"/>
                      <stop offset="100%" stop-color="#8b5cf6"/>
                    </linearGradient>
                  </defs>
                </svg>
              </div>
            </div>
          <?php else: ?>
            <div class="hero-stat-footer empty-state">
              <ion-icon name="calendar-outline"></ion-icon>
              <span>No class scheduled</span>
            </div>
          <?php endif; ?>
        </div>

        <!-- Member Capacity Card -->
        <div class="hero-stat-card capacity-card">
          <div class="hero-stat-header">
            <div class="hero-stat-icon-wrapper" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);">
              <ion-icon name="people-outline"></ion-icon>
            </div>
            <div class="hero-stat-badge badge-purple">
              <ion-icon name="person-add-outline"></ion-icon>
              <span>Capacity</span>
            </div>
          </div>
          <div class="hero-stat-content">
            <div class="hero-stat-label">Member Capacity</div>
            <div class="hero-stat-value"><?= (int)$assignedCount ?><span class="value-divider">/</span><?= (int)COACH_MAX_MEMBERS ?></div>
            <div class="hero-stat-meta">
              <ion-icon name="podium-outline"></ion-icon>
              <span><?= $spotsLeft ?> spot<?= $spotsLeft !== 1 ? 's' : '' ?> available</span>
            </div>
          </div>
          <div class="hero-stat-footer">
            <div class="capacity-bar-wrapper">
              <div class="capacity-bar">
                <div class="capacity-bar-fill" style="width: <?= (int)(($assignedCount / max(1, COACH_MAX_MEMBERS)) * 100) ?>%; background: linear-gradient(90deg, #8b5cf6, #a78bfa);"></div>
              </div>
              <span class="capacity-percent"><?= (int)(($assignedCount / max(1, COACH_MAX_MEMBERS)) * 100) ?>%</span>
            </div>
          </div>
        </div>

        <!-- Active Members Card -->
        <div class="hero-stat-card engagement-card">
          <div class="hero-stat-header">
            <div class="hero-stat-icon-wrapper" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
              <ion-icon name="checkmark-done-outline"></ion-icon>
            </div>
            <div class="hero-stat-badge badge-success">
              <ion-icon name="trending-up-outline"></ion-icon>
              <span>Engagement</span>
            </div>
          </div>
          <div class="hero-stat-content">
            <div class="hero-stat-label">Active Members</div>
            <div class="hero-stat-value"><?= (int)$subscribedAssignedCount ?><span class="value-subtext">/<?= (int)$assignedCount ?></span></div>
            <div class="hero-stat-meta">
              <ion-icon name="pulse-outline"></ion-icon>
              <span><?= $memberEngagement ?>% engagement rate</span>
            </div>
          </div>
          <div class="hero-stat-footer">
            <div class="engagement-indicator">
              <div class="engagement-dot active"></div>
              <div class="engagement-dot <?= $memberEngagement >= 60 ? 'active' : '' ?>"></div>
              <div class="engagement-dot <?= $memberEngagement >= 80 ? 'active' : '' ?>"></div>
              <div class="engagement-label"><?= $memberEngagement >= 80 ? 'Excellent' : ($memberEngagement >= 60 ? 'Good' : 'Average') ?></div>
            </div>
          </div>
        </div>

        <!-- Upcoming Sessions Card -->
        <div class="hero-stat-card sessions-card">
          <div class="hero-stat-header">
            <div class="hero-stat-icon-wrapper" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
              <ion-icon name="calendar-outline"></ion-icon>
            </div>
            <div class="hero-stat-badge badge-warning">
              <ion-icon name="list-outline"></ion-icon>
              <span>Schedule</span>
            </div>
          </div>
          <div class="hero-stat-content">
            <div class="hero-stat-label">Upcoming Sessions</div>
            <div class="hero-stat-value"><?= count($upcoming) ?><span class="value-subtext">sessions</span></div>
            <div class="hero-stat-meta">
              <ion-icon name="analytics-outline"></ion-icon>
              <span>Plan ahead for full classes</span>
            </div>
          </div>
          <div class="hero-stat-footer">
            <div class="session-timeline">
              <?php
              $displayCount = min(3, count($upcoming));
              for ($i = 0; $i < $displayCount; $i++):
                $delay = $i * 0.1;
              ?>
                <div class="timeline-dot" style="animation-delay: <?= $delay ?>s;"></div>
              <?php endfor; ?>
              <?php if (count($upcoming) > 3): ?>
                <span class="timeline-more">+<?= count($upcoming) - 3 ?></span>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-row">
        <div class="quick-actions-panel">
          <div class="section-header">
            <h2 class="section-title">Quick Actions</h2>
          </div>
          <div class="quick-actions-grid">
            <a href="courses.php" class="quick-action-card">
              <div class="quick-action-icon"><ion-icon name="add-circle"></ion-icon></div>
              <div class="quick-action-info">
                <h3>Schedule Session</h3>
                <p>Add a new activity slot</p>
              </div>
            </a>
            <a href="members.php" class="quick-action-card">
              <div class="quick-action-icon"><ion-icon name="person-add"></ion-icon></div>
              <div class="quick-action-info">
                <h3>Assign Member</h3>
                <p>Match athletes to your roster</p>
              </div>
            </a>
            <a href="profile.php" class="quick-action-card">
              <div class="quick-action-icon"><ion-icon name="person-circle"></ion-icon></div>
              <div class="quick-action-info">
                <h3>Update Profile</h3>
                <p>Refresh your bio & contact info</p>
              </div>
            </a>
            <a href="mailto:admin@mygym.local" class="quick-action-card">
              <div class="quick-action-icon"><ion-icon name="chatbubbles"></ion-icon></div>
              <div class="quick-action-info">
                <h3>Coaching Support</h3>
                <p>Reach the admin team</p>
              </div>
            </a>
          </div>
        </div>

        <div class="performance-chart">
          <div class="section-header">
            <h2 class="section-title">Coaching Load</h2>
          </div>
          <div class="chart-container">
            <div class="chart-bars">
              <?php foreach ($coachingSpark as $point): ?>
                <div class="chart-bar" style="--height: <?= (float)$point['value'] ?>%;">
                  <div class="chart-bar-fill"></div>
                  <span class="chart-label"><?= htmlspecialchars($point['label']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="chart-stats">
              <div class="chart-stat">
                <span class="chart-stat-value"><?= count($upcoming) ?></span>
                <span class="chart-stat-label">Upcoming sessions</span>
              </div>
              <div class="chart-stat">
                <span class="chart-stat-value"><?= (int)$assignedCount ?></span>
                <span class="chart-stat-label">Assigned members</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="dashboard-row">
        <div class="section activity-section">
          <div class="section-header">
            <h2 class="section-title">
              <ion-icon name="calendar-outline"></ion-icon>
              Upcoming Classes
            </h2>
            <a href="courses.php" class="view-all-link">Manage schedule</a>
          </div>
          <ul class="activity-list">
            <?php if (empty($upcoming)): ?>
              <li class="activity-item">
                <div class="activity-info">
                  <p style="color:#9ca3af">No classes scheduled.</p>
                </div>
              </li>
            <?php else: foreach ($upcoming as $s): ?>
              <?php
                $dateTxt = '—';
                try { $dateTxt = (new DateTime((string)$s['start_at']))->format('M d, Y'); } catch(Throwable $e){}
                $period  = period_label($s['start_at'] ?? null, $s['end_at'] ?? null);
                $booked  = (int)($s['booked'] ?? 0);
                $cap     = (int)($s['capacity'] ?? 0);
              ?>
              <li class="activity-item">
                <div class="activity-icon"><ion-icon name="barbell"></ion-icon></div>
                <div class="activity-info">
                  <div class="activity-title"><?= htmlspecialchars($s['activity'] ?? '—') ?></div>
                  <div class="activity-meta"><?= htmlspecialchars($dateTxt) ?> • <?= htmlspecialchars($period) ?></div>
                  <div class="activity-meta">Attendees: <?= $booked ?>/<?= $cap ?></div>
                </div>
              </li>
            <?php endforeach; endif; ?>
          </ul>
        </div>

        <div class="section activity-section">
          <div class="section-header">
            <h2 class="section-title">
              <ion-icon name="people-outline"></ion-icon>
              My Members
            </h2>
            <a href="members.php" class="view-all-link">Manage members</a>
          </div>
          <ul class="activity-list">
            <?php if (empty($membersWithStatus)): ?>
              <li class="activity-item">
                <div class="activity-info">
                  <p style="color:#9ca3af">No assigned members yet.</p>
                </div>
              </li>
            <?php else: foreach ($membersWithStatus as $m): ?>
              <li class="activity-item">
                <div class="activity-icon"><ion-icon name="person"></ion-icon></div>
                <div class="activity-info">
                  <div class="activity-title"><?= htmlspecialchars($m['fullname']) ?></div>
                  <div class="activity-meta"><?= $m['active'] ? 'Subscription active' : 'Awaiting renewal' ?></div>
                </div>
                <span class="badge <?= $m['active'] ? 'badge-success' : 'badge-inactive' ?>">
                  <?= $m['active'] ? 'Active' : 'Inactive' ?>
                </span>
              </li>
            <?php endforeach; endif; ?>
          </ul>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
