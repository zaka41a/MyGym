<?php
declare(strict_types=1);
ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/auth.php';
requireRole('MEMBER','ADMIN');
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/subscriptions.php';

// Marquer automatiquement les abonnements expirés
try { expire_old_subscriptions($pdo); } catch (Throwable $e) {}

// User info
$userId   = (int)($_SESSION['user']['id'] ?? 0);
$userName = $_SESSION['user']['fullname'] ?? 'Member';

// CSRF token
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

// Avatar
$rootDir      = dirname(__DIR__);
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';
$avatarUrl    = null;

try {
  $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id=:id");
  $stmt->execute([':id'=>$userId]);
  $avatarDb = (string)($stmt->fetchColumn() ?? '');
  if ($avatarDb !== '') $avatarUrl = $uploadDirWeb . '/' . basename($avatarDb) . '?t=' . time();
} catch (Throwable $e) { }

if (!$avatarUrl) {
  foreach (['jpg','png','webp'] as $ext) {
    $p = $uploadDirFS . "/user_{$userId}.{$ext}";
    if (is_file($p)) { $avatarUrl = $uploadDirWeb . "/user_{$userId}.{$ext}?t=" . time(); break; }
  }
}
if (!$avatarUrl) $avatarUrl = 'https://via.placeholder.com/36x36?text=%20';

// Subscription info
$active = $pending = null;
$canBook = false;
try {
  $active  = get_active_subscription($pdo, $userId);
  $pending = get_pending_request($pdo, $userId);
  $canBook = has_class_access($pdo, $userId);
} catch (Throwable $e) {
  $active = $pending = null;
  $canBook = false;
}

// Days left & progress
$daysLeft = null; $pctLeft = null;
if ($active && !empty($active['end_date'])) {
  try {
    $end   = new DateTime($active['end_date']);
    $start = !empty($active['start_date']) ? new DateTime($active['start_date']) : null;
    $today = new DateTime('today');
    $daysLeft = max(0, (int)$today->diff($end)->format('%r%a'));
    if ($start) {
      $total = max(1, (int)$start->diff($end)->format('%r%a'));
      $pctLeft = max(0, min(100, (int)round(($daysLeft/$total)*100)));
    }
  } catch (Throwable $e) { $daysLeft = $pctLeft = null; }
}

// My next reservations
$myNext = [];
try {
  $sql = "
    SELECT s.id, s.start_at, s.end_at,
           a.name AS activity,
           u.fullname AS coach
      FROM reservations r
      JOIN sessions s   ON s.id = r.session_id
      JOIN activities a ON a.id = s.activity_id
      JOIN users u      ON u.id = s.coach_id
     WHERE r.user_id = :u
       AND r.status  = 'BOOKED'
       AND s.start_at >= NOW()
  ORDER BY s.start_at ASC
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':u'=>$userId]);
  $myNext = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $myNext = [];
}

// Booked count
$bookedCount = 0;
try {
  $st = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id=:u AND status='BOOKED'");
  $st->execute([':u'=>$userId]);
  $bookedCount = (int)$st->fetchColumn();
} catch (Throwable $e) {
  $bookedCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Member Dashboard</title>
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

    .btn {
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      color: #fff;
      border: 0;
      border-radius: 12px;
      padding: 0.5rem 1.2rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.3s;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
      display: inline-block;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
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
      display: inline-block;
    }

    .badge-success {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .progress {
      height: 8px;
      border-radius: 999px;
      background: rgba(255, 255, 255, 0.1);
      overflow: hidden;
      box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .progress>span {
      display: block;
      height: 100%;
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      border-radius: 999px;
      transition: width 0.3s ease;
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
            <?php if ($canBook): ?>
              <a href="courses.php" class="nav-link">
                <ion-icon name="calendar"></ion-icon>
                <span>My Classes</span>
              </a>
            <?php else: ?>
              <a href="subscribe.php" class="nav-link" style="opacity:0.6">
                <ion-icon name="lock-closed"></ion-icon>
                <span>My Classes (Locked)</span>
              </a>
            <?php endif; ?>
          </li>
          <li class="nav-item">
            <a href="subscribe.php" class="nav-link">
              <ion-icon name="card"></ion-icon>
              <span>Subscription</span>
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
        <h1>Welcome back, <?= htmlspecialchars($userName) ?>!</h1>
        <p>Track your fitness journey and upcoming classes.</p>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon">
              <ion-icon name="calendar"></ion-icon>
            </div>
          </div>
          <div class="stat-value"><?= (int)$bookedCount ?></div>
          <div class="stat-label">Booked Classes</div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon">
              <ion-icon name="time"></ion-icon>
            </div>
          </div>
          <?php if ($active && $daysLeft !== null): ?>
            <div class="stat-value">D-<?= (int)$daysLeft ?></div>
            <div class="stat-label"><?= htmlspecialchars($active['plan_name']) ?></div>
          <?php elseif ($pending): ?>
            <div class="stat-value">—</div>
            <div class="stat-label">Pending Request</div>
          <?php else: ?>
            <div class="stat-value">—</div>
            <div class="stat-label">No Subscription</div>
          <?php endif; ?>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div class="stat-icon">
              <ion-icon name="<?= $active ? 'checkmark-circle' : ($pending ? 'hourglass' : 'alert-circle') ?>"></ion-icon>
            </div>
          </div>
          <div class="stat-value"><?= $active ? 'Active' : ($pending ? 'Pending' : 'None') ?></div>
          <div class="stat-label">Status</div>
        </div>
      </div>

      <!-- Two columns -->
      <div class="cols">
        <!-- My upcoming classes -->
        <div class="section">
          <div class="section-header">
            <h2 class="section-title">My Upcoming Classes</h2>
            <?php if ($canBook): ?>
              <a href="courses.php" class="btn">View Catalog</a>
            <?php else: ?>
              <a href="subscribe.php" class="btn">Upgrade Plan</a>
            <?php endif; ?>
          </div>
          <table>
            <thead>
              <tr>
                <td>Date</td>
                <td>Activity</td>
                <td>Coach</td>
                <td>Action</td>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($myNext)): ?>
                <tr><td colspan="4" style="text-align:center;color:#9ca3af">No upcoming bookings.</td></tr>
              <?php else: foreach ($myNext as $row): ?>
                <tr>
                  <td><?= date('M d, H:i', strtotime((string)$row['start_at'])) ?></td>
                  <td><?= htmlspecialchars($row['activity']) ?></td>
                  <td><?= htmlspecialchars($row['coach']) ?></td>
                  <td>
                    <form method="post" action="courses.php" style="display:inline">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                      <input type="hidden" name="action" value="cancel">
                      <input type="hidden" name="session_id" value="<?= (int)$row['id'] ?>">
                      <button class="btn" type="submit" style="background:#666;font-size:0.75rem" onclick="return confirm('Cancel this booking?');">Cancel</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <!-- My subscription -->
        <div class="section">
          <div class="section-header">
            <h2 class="section-title">My Subscription</h2>
          </div>

          <?php if ($active): ?>
            <p style="margin:6px 0;color:#9ca3af">
              <strong style="color:#fff"><?= htmlspecialchars($active['plan_name']) ?></strong><br>
              Start: <?= htmlspecialchars($active['start_date'] ?: '—') ?><br>
              End: <?= htmlspecialchars($active['end_date'] ?: '—') ?>
            </p>
            <div style="display:flex;align-items:center;gap:10px;margin:16px 0">
              <?php if ($pctLeft !== null): ?>
                <div class="progress" style="flex:1 1 200px"><span style="width: <?= (int)$pctLeft ?>%"></span></div>
              <?php endif; ?>
              <span class="badge badge-success"><?= (int)$daysLeft ?> day(s)</span>
            </div>
            <div style="display:flex;gap:10px;margin-top:16px">
              <a class="btn" href="subscribe.php">Manage</a>
              <form method="post" action="subscribe.php" onsubmit="return confirm('Cancel the active subscription?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="cancel_active">
                <input type="hidden" name="id" value="<?= (int)($active['id'] ?? 0) ?>">
                <button class="btn" type="submit" style="background:#666">Cancel</button>
              </form>
            </div>

          <?php elseif ($pending): ?>
            <p style="margin:6px 0;color:#9ca3af">
              <strong style="color:#fff">Pending Request</strong><br>
              Plan: <?= htmlspecialchars($pending['plan_name']) ?>
            </p>
            <div style="display:flex;gap:10px;margin-top:16px">
              <a class="btn" href="subscribe.php">View</a>
              <form method="post" action="subscribe.php" onsubmit="return confirm('Cancel this request?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="cancel_request">
                <input type="hidden" name="id" value="<?= (int)($pending['id'] ?? 0) ?>">
                <button class="btn" type="submit" style="background:#666">Cancel</button>
              </form>
            </div>

          <?php else: ?>
            <p style="margin:6px 0;color:#9ca3af">You don't have an active subscription.</p>
            <a class="btn" href="subscribe.php" style="margin-top:16px">Choose a Plan</a>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
