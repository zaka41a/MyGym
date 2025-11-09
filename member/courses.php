<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('MEMBER','ADMIN');
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/subscriptions.php';

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

$userId   = (int)($_SESSION['user']['id'] ?? 0);
$userName = (string)($_SESSION['user']['fullname'] ?? 'Member');

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

$canBook = false;
try { $canBook = has_class_access($pdo, $userId); } catch (Throwable $e) { $canBook = false; }

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
      $st = $pdo->prepare("SELECT 1 FROM reservations WHERE user_id=:u AND session_id=:s AND status='BOOKED' LIMIT 1");
      $st->execute([':u'=>$userId, ':s'=>$sessionId]);
      if ($st->fetchColumn()) throw new RuntimeException('Already reserved.');

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

      $st = $pdo->prepare("INSERT INTO reservations (user_id, session_id, status, created_at) VALUES (:u,:s,'BOOKED',NOW())");
      $st->execute([':u'=>$userId, ':s'=>$sessionId]);

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Reservation confirmed.'));
      exit;
    }

    if ($action === 'cancel') {
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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Member</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <?php include __DIR__ . '/../shared/member-styles.php'; ?>
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
          <li class="nav-item">
            <?php if ($canBook): ?>
              <a href="courses.php" class="nav-link active"><ion-icon name="calendar"></ion-icon><span>My Classes</span></a>
            <?php else: ?>
              <a href="subscribe.php" class="nav-link locked" title="Upgrade to PLUS/PRO to unlock"><ion-icon name="lock-closed"></ion-icon><span>My Classes (Locked)</span></a>
            <?php endif; ?>
          </li>
          <li class="nav-item"><a href="subscribe.php" class="nav-link"><ion-icon name="card"></ion-icon><span>Subscription</span></a></li>
          <li class="nav-item"><a href="profile.php" class="nav-link"><ion-icon name="person-circle"></ion-icon><span>Profile</span></a></li>
        </ul>
        <a href="/MyGym/backend/logout.php" class="logout-btn"><ion-icon name="log-out"></ion-icon><span>Logout</span></a>
      </nav>
    </aside>
    <main class="main-content">
      <div class="header">
        <h1>My Classes</h1>
        <p style="color: #9ca3af;">Browse and reserve upcoming fitness classes</p>
      </div>

      <?php if ($ok): ?><div class="alert alert-success"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-error"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <?php if (!$canBook): ?>
        <div class="locked-msg">
          <ion-icon name="lock-closed" style="font-size:1.5rem"></ion-icon>
          <div>
            <strong>Class reservations are locked</strong><br>
            <span style="font-size:0.9rem">Upgrade to PLUS or PRO plan to reserve classes with our coaches.</span>
          </div>
        </div>
      <?php endif; ?>

      <?php
        // Calculate stats
        $totalAvailable = count($sessions);
        $myBookingsCount = 0;
        $totalCapacityRemaining = 0;
        foreach ($sessions as $s) {
          if ((int)$s['my_reserved'] === 1) $myBookingsCount++;
          $totalCapacityRemaining += max(0, (int)$s['capacity'] - (int)$s['reserved']);
        }
      ?>

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-label">Available Classes</div>
              <div class="stat-value"><?= $totalAvailable ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="calendar"></ion-icon>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-label">My Bookings</div>
              <div class="stat-value"><?= $myBookingsCount ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="checkmark-done-circle"></ion-icon>
            </div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-label">Total Spots Left</div>
              <div class="stat-value"><?= $totalCapacityRemaining ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="people"></ion-icon>
            </div>
          </div>
        </div>
      </div>

      <!-- Search and Filter Bar -->
      <div class="filter-bar">
        <div class="search-box">
          <ion-icon name="search"></ion-icon>
          <input type="text" id="searchInput" placeholder="Search by class name or coach..." />
        </div>
        <div class="filter-buttons">
          <button class="filter-btn active" data-filter="all">All Classes</button>
          <button class="filter-btn" data-filter="available">Available</button>
          <button class="filter-btn" data-filter="booked">My Bookings</button>
        </div>
      </div>

      <div class="section">
        <div class="section-header">
          <h2 class="section-title">Upcoming Sessions</h2>
          <span class="results-count" id="resultsCount"><?= $totalAvailable ?> classes</span>
        </div>

        <?php if (!$sessions): ?>
          <div class="empty-state">
            <ion-icon name="calendar-outline" style="font-size: 4rem; color: #4b5563; margin-bottom: 1rem;"></ion-icon>
            <h3 style="color: #9ca3af; font-size: 1.25rem; font-weight: 600;">No upcoming sessions available</h3>
            <p style="color: #6b7280; font-size: 0.9rem;">Check back later for new class schedules</p>
          </div>
        <?php else: ?>
          <div class="classes-grid">
            <?php foreach ($sessions as $s):
              $cap   = (int)$s['capacity'];
              $res   = (int)$s['reserved'];
              $left  = max(0, $cap - $res);
              $mine  = ((int)$s['my_reserved'] === 1);
              $full  = ($left <= 0);
              $progress = ($cap > 0) ? round(($res / $cap) * 100) : 0;

              // Activity color coding
              $activityName = strtoupper($s['activity']);
              $activityColors = [
                'YOGA' => ['bg' => 'rgba(168, 85, 247, 0.2)', 'border' => '#a855f7', 'text' => '#c084fc'],
                'CROSSFIT' => ['bg' => 'rgba(220, 38, 38, 0.2)', 'border' => '#dc2626', 'text' => '#ef4444'],
                'PILATES' => ['bg' => 'rgba(236, 72, 153, 0.2)', 'border' => '#ec4899', 'text' => '#f472b6'],
                'ZUMBA' => ['bg' => 'rgba(245, 158, 11, 0.2)', 'border' => '#f59e0b', 'text' => '#fbbf24'],
                'SPINNING' => ['bg' => 'rgba(14, 165, 233, 0.2)', 'border' => '#0ea5e9', 'text' => '#38bdf8'],
                'BOXING' => ['bg' => 'rgba(239, 68, 68, 0.2)', 'border' => '#ef4444', 'text' => '#f87171'],
              ];
              $colors = $activityColors[$activityName] ?? ['bg' => 'rgba(59, 130, 246, 0.2)', 'border' => '#3b82f6', 'text' => '#60a5fa'];
            ?>
              <div class="class-card"
                   data-activity="<?= htmlspecialchars($activityName) ?>"
                   data-coach="<?= htmlspecialchars($s['coach']) ?>"
                   data-status="<?= $mine ? 'booked' : ($full ? 'full' : 'available') ?>">

                <!-- Class Image Placeholder -->
                <div class="class-image" style="background: linear-gradient(135deg, <?= $colors['bg'] ?>, rgba(0,0,0,0.3));">
                  <div class="class-overlay">
                    <span class="activity-badge" style="background: <?= $colors['bg'] ?>; border: 1px solid <?= $colors['border'] ?>; color: <?= $colors['text'] ?>;">
                      <?= htmlspecialchars($activityName) ?>
                    </span>
                    <?php if ($mine): ?>
                      <span class="booked-indicator">
                        <ion-icon name="checkmark-circle"></ion-icon> Booked
                      </span>
                    <?php elseif ($full): ?>
                      <span class="full-indicator">
                        <ion-icon name="close-circle"></ion-icon> Full
                      </span>
                    <?php endif; ?>
                  </div>
                </div>

                <!-- Class Content -->
                <div class="class-content">
                  <div class="class-header">
                    <h3 class="class-title"><?= htmlspecialchars($s['activity']) ?></h3>
                    <div class="class-time">
                      <ion-icon name="time-outline"></ion-icon>
                      <span><?= date('D, M j', strtotime((string)$s['start_at'])) ?></span>
                    </div>
                  </div>

                  <div class="class-schedule">
                    <div class="schedule-item">
                      <ion-icon name="play-circle"></ion-icon>
                      <span><?= date('h:i A', strtotime((string)$s['start_at'])) ?></span>
                    </div>
                    <span style="color: #4b5563;">—</span>
                    <div class="schedule-item">
                      <ion-icon name="stop-circle"></ion-icon>
                      <span><?= date('h:i A', strtotime((string)$s['end_at'])) ?></span>
                    </div>
                  </div>

                  <div class="class-coach">
                    <ion-icon name="person-circle"></ion-icon>
                    <span><?= htmlspecialchars($s['coach']) ?></span>
                  </div>

                  <!-- Capacity Progress Bar -->
                  <div class="capacity-section">
                    <div class="capacity-header">
                      <span class="capacity-label">Capacity</span>
                      <span class="capacity-numbers"><?= $res ?> / <?= $cap ?></span>
                    </div>
                    <div class="progress-bar">
                      <div class="progress-fill" style="width: <?= $progress ?>%; background: <?= $progress >= 90 ? '#ef4444' : ($progress >= 70 ? '#f59e0b' : '#10b981') ?>;"></div>
                    </div>
                    <div class="spots-left">
                      <ion-icon name="<?= $left > 0 ? 'checkmark-circle' : 'close-circle' ?>"></ion-icon>
                      <span><?= $left ?> spot<?= $left !== 1 ? 's' : '' ?> remaining</span>
                    </div>
                  </div>

                  <!-- Action Button -->
                  <div class="class-actions">
                    <?php if (!$canBook): ?>
                      <button class="class-btn locked" disabled>
                        <ion-icon name="lock-closed"></ion-icon>
                        <span>Locked - Upgrade Required</span>
                      </button>
                    <?php elseif ($mine): ?>
                      <form method="post">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                        <button class="class-btn cancel" type="submit">
                          <ion-icon name="close-circle"></ion-icon>
                          <span>Cancel Booking</span>
                        </button>
                      </form>
                    <?php elseif ($full): ?>
                      <button class="class-btn full" disabled>
                        <ion-icon name="ban"></ion-icon>
                        <span>Class Full</span>
                      </button>
                    <?php else: ?>
                      <form method="post">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                        <input type="hidden" name="action" value="reserve">
                        <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                        <button class="class-btn book" type="submit">
                          <ion-icon name="checkmark-circle"></ion-icon>
                          <span>Book Now</span>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</body>
</html>
