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

$nextBooking        = $myNext[0] ?? null;
$nextBookingLabel   = $nextBooking ? date('M d • H:i', strtotime((string)$nextBooking['start_at'])) : 'No class booked yet';
$nextBookingCoach   = $nextBooking['coach'] ?? null;
$subscriptionStatus = 'No subscription';
$subscriptionBadge  = 'badge badge-warning';
$subscriptionCopy   = 'Choose a plan to unlock every class.';

if ($active) {
  $subscriptionStatus = 'Active';
  $subscriptionBadge  = 'badge badge-success';
  $subscriptionCopy   = sprintf('%s • Ends on %s',
    (string)($active['plan_name'] ?? ''), $active['end_date'] ?: '—'
  );
} elseif ($pending) {
  $subscriptionStatus = 'Pending';
  $subscriptionBadge  = 'badge badge-info';
  $subscriptionCopy   = sprintf('Waiting for validation • %s plan', (string)($pending['plan_name'] ?? ''));
}

$planProgress = $pctLeft ?? null;
$activitySpark = [];
$dayLabels = ['Mon','Tue','Wed','Thu','Fri','Sat'];
$base = min(82, max(24, $bookedCount * 6 + ($canBook ? 12 : -6)));
foreach ($dayLabels as $idx => $label) {
  $offset = ($idx - 2) * 5;
  $value = max(18, min(92, $base + $offset));
  $activitySpark[] = ['label'=>$label, 'value'=>$value];
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
  <?php include __DIR__ . '/../shared/member-styles.php'; ?>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="logo">
        <svg width="180" height="50" viewBox="0 0 220 60" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g transform="translate(5, 15)">
            <rect x="0" y="5" width="6" height="20" rx="1.5" fill="url(#gradientMember1)"/>
            <rect x="6" y="8" width="2" height="14" rx="0.5" fill="#7f1d1d"/>
            <rect x="8" y="12" width="34" height="6" rx="3" fill="url(#gradientMember1)"/>
            <rect x="42" y="8" width="2" height="14" rx="0.5" fill="#7f1d1d"/>
            <rect x="44" y="5" width="6" height="20" rx="1.5" fill="url(#gradientMember1)"/>
          </g>
          <text x="65" y="32" font-family="system-ui, -apple-system, 'Segoe UI', Arial, sans-serif" font-size="28" font-weight="900" fill="url(#textGradientMember)" letter-spacing="2">MyGym</text>
          <text x="65" y="46" font-family="system-ui, -apple-system, 'Segoe UI', Arial, sans-serif" font-size="10" font-weight="600" fill="#94a3b8" letter-spacing="3">MEMBER SPACE</text>
          <defs>
            <linearGradient id="gradientMember1" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#ef4444"/>
              <stop offset="100%" stop-color="#dc2626"/>
            </linearGradient>
            <linearGradient id="textGradientMember" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#ef4444"/>
              <stop offset="50%" stop-color="#f87171"/>
              <stop offset="100%" stop-color="#ef4444"/>
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
              <a href="subscribe.php" class="nav-link locked" title="Upgrade to unlock">
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
        <div>
          <h1>Welcome back, <?= htmlspecialchars($userName) ?>!</h1>
          <p>Track your performance, next classes, and membership health.</p>
        </div>
        <div class="header-date">
          <ion-icon name="calendar-outline"></ion-icon>
          <span><?= date('l, F j') ?></span>
        </div>
      </div>

      <!-- Member Stats Hero -->
      <div class="member-stats-hero">
        <div class="member-stat-card">
          <div class="member-stat-icon" style="--stat-color: #ef4444;">
            <ion-icon name="calendar"></ion-icon>
          </div>
          <div class="member-stat-content">
            <div class="member-stat-value"><?= number_format($bookedCount) ?></div>
            <div class="member-stat-label">Booked Classes</div>
            <div class="member-stat-subtitle"><?= $bookedCount > 0 ? 'Great consistency!' : 'Start your journey' ?></div>
          </div>
        </div>

        <div class="member-stat-divider"></div>

        <div class="member-stat-card">
          <div class="member-stat-icon" style="--stat-color: #dc2626;">
            <ion-icon name="time"></ion-icon>
          </div>
          <div class="member-stat-content">
            <div class="member-stat-value small"><?= htmlspecialchars($nextBookingLabel) ?></div>
            <div class="member-stat-label">Next Session</div>
            <div class="member-stat-subtitle"><?= $nextBookingCoach ? htmlspecialchars($nextBookingCoach) : 'No booking yet' ?></div>
          </div>
        </div>

        <div class="member-stat-divider"></div>

        <div class="member-stat-card">
          <div class="member-stat-icon" style="--stat-color: #b91c1c;">
            <ion-icon name="card"></ion-icon>
          </div>
          <div class="member-stat-content">
            <div class="member-stat-value"><?= htmlspecialchars($subscriptionStatus) ?></div>
            <div class="member-stat-label">Subscription</div>
            <div class="member-stat-subtitle"><?= $active ? htmlspecialchars((string)($active['plan_name'] ?? '')) : 'Choose a plan' ?></div>
          </div>
        </div>

        <div class="member-stat-divider"></div>

        <div class="member-stat-card">
          <div class="member-stat-icon" style="--stat-color: <?= $canBook ? '#10b981' : '#f87171' ?>;">
            <ion-icon name="<?= $canBook ? 'checkmark-circle' : 'lock-closed' ?>"></ion-icon>
          </div>
          <div class="member-stat-content">
            <div class="member-stat-value"><?= $canBook ? '<span style="color: #ef4444;">Unlocked</span>' : 'Locked' ?></div>
            <div class="member-stat-label">Class Access</div>
            <div class="member-stat-subtitle"><?= $canBook ? 'Ready to book' : 'Upgrade needed' ?></div>
          </div>
        </div>
      </div>

      <div class="dashboard-row">
        <div class="quick-actions-panel">
          <div class="section-header">
            <h2 class="section-title">Quick Actions</h2>
          </div>
          <div class="quick-actions-grid">
            <a href="<?= $canBook ? 'courses.php' : 'subscribe.php' ?>" class="quick-action-card">
              <div class="quick-action-icon">
                <ion-icon name="barbell-outline"></ion-icon>
              </div>
              <div class="quick-action-info">
                <h3><?= $canBook ? 'Book a Class' : 'Unlock Classes' ?></h3>
                <p><?= $canBook ? 'Secure your next spot' : 'Upgrade to access the schedule' ?></p>
              </div>
              <?php if (!$canBook): ?><span class="quick-action-badge">Lock</span><?php endif; ?>
            </a>

            <a href="subscribe.php" class="quick-action-card">
              <div class="quick-action-icon">
                <ion-icon name="card-outline"></ion-icon>
              </div>
              <div class="quick-action-info">
                <h3>Manage Plan</h3>
                <p><?= $active ? htmlspecialchars((string)($active['plan_name'] ?? '')) : 'Pick the right formula' ?></p>
              </div>
            </a>

            <a href="profile.php" class="quick-action-card">
              <div class="quick-action-icon">
                <ion-icon name="person-circle-outline"></ion-icon>
              </div>
              <div class="quick-action-info">
                <h3>Update Profile</h3>
                <p>Keep your contact info fresh</p>
              </div>
            </a>

            <a href="mailto:coach@mygym.local" class="quick-action-card">
              <div class="quick-action-icon">
                <ion-icon name="chatbubbles-outline"></ion-icon>
              </div>
              <div class="quick-action-info">
                <h3>Talk to Us</h3>
                <p>Need help with your goals?</p>
              </div>
            </a>
          </div>
        </div>

        <div class="performance-chart">
          <div class="section-header">
            <h2 class="section-title">Weekly Focus</h2>
          </div>
          <div class="chart-container">
            <div class="chart-bars">
              <?php foreach ($activitySpark as $point): ?>
                <div class="chart-bar" style="--height: <?= (float)$point['value'] ?>%;">
                  <div class="chart-bar-fill"></div>
                  <span class="chart-label"><?= htmlspecialchars($point['label']) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="chart-stats">
              <div class="chart-stat">
                <span class="chart-stat-value"><?= number_format($bookedCount) ?></span>
                <span class="chart-stat-label">Total bookings</span>
              </div>
              <div class="chart-stat">
                <span class="chart-stat-value"><?= $canBook ? 'Ready' : 'Locked' ?></span>
                <span class="chart-stat-label">Class access</span>
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
            <a href="<?= $canBook ? 'courses.php' : 'subscribe.php' ?>" class="view-all-link">
              <?= $canBook ? 'View schedule' : 'Unlock classes' ?>
            </a>
          </div>
          <ul class="activity-list">
            <?php if (empty($myNext)): ?>
              <li class="activity-item">
                <div class="activity-info">
                  <p style="color:#9ca3af">No upcoming bookings.</p>
                </div>
              </li>
            <?php else: foreach ($myNext as $row): ?>
              <li class="activity-item">
                <div class="activity-icon">
                  <ion-icon name="barbell"></ion-icon>
                </div>
                <div class="activity-info">
                  <div class="activity-title"><?= htmlspecialchars((string)$row['activity']) ?></div>
                  <div class="activity-meta">
                    <?= date('M d • H:i', strtotime((string)$row['start_at'])) ?> · <?= htmlspecialchars((string)$row['coach']) ?>
                  </div>
                </div>
                <form method="post" action="courses.php" onsubmit="return confirm('Cancel this booking?');">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="action" value="cancel">
                  <input type="hidden" name="session_id" value="<?= (int)$row['id'] ?>">
                  <button class="btn btn-ghost" type="submit">Cancel</button>
                </form>
              </li>
            <?php endforeach; endif; ?>
          </ul>
        </div>

        <div class="section activity-section">
          <div class="section-header">
            <h2 class="section-title">
              <ion-icon name="card-outline"></ion-icon>
              Subscription Summary
            </h2>
            <span class="<?= htmlspecialchars($subscriptionBadge) ?>"><?= htmlspecialchars($subscriptionStatus) ?></span>
          </div>
          <p style="color:#9ca3af;line-height:1.6;">
            <?= htmlspecialchars($subscriptionCopy) ?>
          </p>

          <?php if ($planProgress !== null): ?>
            <div class="stat-bar" style="margin: 1.5rem 0 0.5rem;">
              <div class="stat-bar-fill" style="width: <?= (int)$planProgress ?>%;"></div>
            </div>
            <p style="color:#9ca3af; font-size:0.9rem;">Approximately <?= (int)$daysLeft ?> day(s) remaining</p>
          <?php endif; ?>

          <div style="display:flex;flex-wrap:wrap;gap:0.75rem;margin-top:1.5rem;">
            <a href="subscribe.php" class="btn">Manage Plan</a>
            <?php if ($active): ?>
              <form method="post" action="subscribe.php" onsubmit="return confirm('Cancel the active subscription?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="cancel_active">
                <input type="hidden" name="id" value="<?= (int)($active['id'] ?? 0) ?>">
                <button class="btn btn-ghost" type="submit">Cancel</button>
              </form>
            <?php elseif ($pending): ?>
              <form method="post" action="subscribe.php" onsubmit="return confirm('Cancel this request?');">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="cancel_request">
                <input type="hidden" name="id" value="<?= (int)($pending['id'] ?? 0) ?>">
                <button class="btn btn-ghost" type="submit">Withdraw Request</button>
              </form>
            <?php else: ?>
              <a href="subscribe.php" class="btn btn-ghost">Choose Plan</a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
