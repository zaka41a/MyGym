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

$nextLabel = '—';
if ($nextSession && !empty($nextSession['start_at'])) {
  try {
    $dt = new DateTime($nextSession['start_at']);
    $nextLabel = $dt->format('d/m H:i');
  } catch (Throwable $e) { $nextLabel = '—'; }
}

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
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #0a0a0a;
      color: #f5f7fb;
      min-height: 100vh;
      background: radial-gradient(55% 80% at 50% 0%, rgba(220, 38, 38, 0.22), transparent 65%),
                  radial-gradient(60% 90% at 75% 15%, rgba(127, 29, 29, 0.18), transparent 70%),
                  linear-gradient(180deg, rgba(10, 10, 10, 0.98) 0%, rgba(10, 10, 10, 1) 100%);
    }

    .container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 280px;
      background: rgba(17, 17, 17, 0.95);
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      padding: 2rem 1.5rem;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 3rem;
    }

    .logo-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      box-shadow: 0 10px 30px rgba(220,38,38,0.4);
    }

    .logo-text h1 {
      font-size: 1.5rem;
      font-weight: 800;
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .logo-text p {
      font-size: 0.75rem;
      color: #9ca3af;
      text-transform: uppercase;
      letter-spacing: 0.1em;
    }

    .nav-menu {
      list-style: none;
      margin: 2rem 0;
    }

    .nav-item {
      margin-bottom: 0.5rem;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      color: #9ca3af;
      text-decoration: none;
      border-radius: 12px;
      transition: all 0.3s;
      font-weight: 500;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.05);
      color: #fff;
    }

    .nav-link.active {
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
      color: #fff;
      box-shadow: 0 4px 20px rgba(220,38,38,0.3);
    }

    .nav-link ion-icon {
      font-size: 1.25rem;
    }

    .logout-btn {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      color: #9ca3af;
      text-decoration: none;
      transition: all 0.3s;
      font-weight: 500;
      margin-top: 2rem;
    }

    .logout-btn:hover {
      background: rgba(220, 38, 38, 0.2);
      color: #fff;
      border-color: #dc2626;
    }

    /* Main Content */
    .main-content {
      margin-left: 280px;
      flex: 1;
      padding: 2rem;
    }

    .header {
      margin-bottom: 3rem;
    }

    .header h1 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .header p {
      color: #9ca3af;
      font-size: 1rem;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 1.5rem;
      transition: all 0.3s;
    }

    .stat-card:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(220, 38, 38, 0.4);
      transform: translateY(-2px);
    }

    .stat-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1rem;
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #dc2626;
      font-size: 1.5rem;
    }

    .stat-value {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.25rem;
    }

    .stat-label {
      color: #9ca3af;
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Sections */
    .section {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead td {
      font-weight: 600;
      color: #9ca3af;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    tbody tr {
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      transition: all 0.2s;
    }

    tbody tr:hover {
      background: rgba(220, 38, 38, 0.05);
    }

    td {
      padding: 1rem 0.75rem;
      vertical-align: middle;
    }

    .badge {
      padding: 0.375rem 0.875rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .badge-success {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .badge-inactive {
      background: rgba(156, 163, 175, 0.2);
      color: #9ca3af;
    }

    .cols {
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 1.5rem;
    }

    @media (max-width: 991px) {
      .sidebar {
        width: 0;
        opacity: 0;
      }
      .main-content {
        margin-left: 0;
      }
      .cols {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
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
        <h1>Welcome back, <?= htmlspecialchars($coachName) ?>!</h1>
        <p>Manage your classes and members.</p>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon">
              <ion-icon name="calendar"></ion-icon>
            </div>
          </div>
          <div class="stat-value"><?= htmlspecialchars($nextLabel) ?></div>
          <div class="stat-label">Next Class<?= $nextSession && $nextSession['activity'] ? ' — '.htmlspecialchars($nextSession['activity']) : '' ?></div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon">
              <ion-icon name="analytics"></ion-icon>
            </div>
          </div>
          <div class="stat-value"><?= (int)$spotsLeft ?>/<?= (int)COACH_MAX_MEMBERS ?></div>
          <div class="stat-label">Available Slots</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon">
              <ion-icon name="people"></ion-icon>
            </div>
          </div>
          <div class="stat-value"><?= (int)$subscribedAssignedCount ?></div>
          <div class="stat-label">Active Members</div>
        </div>
      </div>

      <!-- Two columns -->
      <div class="cols">
        <!-- Upcoming classes -->
        <div class="section">
          <div class="section-header">
            <h2 class="section-title">Upcoming Classes</h2>
          </div>
          <table>
            <thead>
              <tr>
                <td>Date</td>
                <td>Time</td>
                <td>Activity</td>
                <td>Attendance</td>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($upcoming)): ?>
                <tr><td colspan="4" style="text-align:center;color:#9ca3af">No classes scheduled.</td></tr>
              <?php else: foreach ($upcoming as $s): ?>
                <?php
                  $dateTxt = '—';
                  try { $dateTxt = (new DateTime((string)$s['start_at']))->format('M d, Y'); } catch(Throwable $e){}
                  $period  = period_label($s['start_at'] ?? null, $s['end_at'] ?? null);
                  $booked  = (int)($s['booked'] ?? 0);
                  $cap     = (int)($s['capacity'] ?? 0);
                ?>
                <tr>
                  <td><?= htmlspecialchars($dateTxt) ?></td>
                  <td><?= htmlspecialchars($period) ?></td>
                  <td><?= htmlspecialchars($s['activity'] ?? '—') ?></td>
                  <td><?= $booked ?>/<?= $cap ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <!-- My members -->
        <div class="section">
          <div class="section-header">
            <h2 class="section-title">My Members</h2>
          </div>
          <table>
            <thead><tr><td>Name</td><td>Status</td></tr></thead>
            <tbody>
              <?php if (empty($membersWithStatus)): ?>
                <tr><td colspan="2" style="text-align:center;color:#9ca3af">No assigned members.</td></tr>
              <?php else: foreach ($membersWithStatus as $m): ?>
                <tr>
                  <td><?= htmlspecialchars($m['fullname']) ?></td>
                  <td>
                    <?php if ($m['active']): ?>
                      <span class="badge badge-success">Active</span>
                    <?php else: ?>
                      <span class="badge badge-inactive">Inactive</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
