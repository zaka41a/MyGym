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

$totalAvailable = count($sessions);
$myBookingsCount = 0;
$totalCapacityRemaining = 0;
foreach ($sessions as $session) {
  if ((int)$session['my_reserved'] === 1) $myBookingsCount++;
  $totalCapacityRemaining += max(0, (int)$session['capacity'] - (int)$session['reserved']);
}
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
        <div>
          <h1>My Classes</h1>
          <p>Browse the schedule and manage your bookings.</p>
        </div>
        <div class="header-date">
          <ion-icon name="calendar-outline"></ion-icon>
          <span><?= date('l, F j') ?></span>
        </div>
      </div>

      <?php if ($ok): ?><div class="alert alert-success"><ion-icon name="checkmark-circle"></ion-icon><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-error"><ion-icon name="alert-circle"></ion-icon><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <?php if (!$canBook): ?>
        <div class="alert alert-error" style="margin-bottom:2rem;">
          <ion-icon name="lock-closed"></ion-icon>
          <div>
            <strong>Class reservations are locked</strong><br>
            Upgrade to PLUS or PRO plan to reserve classes with our coaches.
          </div>
        </div>
      <?php endif; ?>

      <!-- Classes Overview Hero -->
      <div class="classes-overview-hero">
        <div class="classes-hero-stat">
          <div class="classes-hero-icon" style="--icon-color: #ef4444;">
            <ion-icon name="calendar-outline"></ion-icon>
          </div>
          <div class="classes-hero-info">
            <div class="classes-hero-value"><?= $totalAvailable ?></div>
            <div class="classes-hero-label">Available Classes</div>
            <div class="classes-hero-desc">Fresh sessions added weekly</div>
          </div>
        </div>

        <div class="classes-hero-divider"></div>

        <div class="classes-hero-stat">
          <div class="classes-hero-icon" style="--icon-color: #dc2626;">
            <ion-icon name="checkmark-done-outline"></ion-icon>
          </div>
          <div class="classes-hero-info">
            <div class="classes-hero-value"><?= $myBookingsCount ?></div>
            <div class="classes-hero-label">My Bookings</div>
            <div class="classes-hero-desc">Keep your routine steady</div>
          </div>
        </div>

        <div class="classes-hero-divider"></div>

        <div class="classes-hero-stat">
          <div class="classes-hero-icon" style="--icon-color: #b91c1c;">
            <ion-icon name="people-outline"></ion-icon>
          </div>
          <div class="classes-hero-info">
            <div class="classes-hero-value"><?= $totalCapacityRemaining ?></div>
            <div class="classes-hero-label">Open Spots</div>
            <div class="classes-hero-desc">Secure your place early</div>
          </div>
        </div>

        <div class="classes-hero-divider"></div>

        <div class="classes-hero-stat">
          <div class="classes-hero-icon" style="--icon-color: <?= $canBook ? '#10b981' : '#f87171' ?>;">
            <ion-icon name="<?= $canBook ? 'shield-checkmark-outline' : 'lock-closed-outline' ?>"></ion-icon>
          </div>
          <div class="classes-hero-info">
            <div class="classes-hero-value"><?= $canBook ? '<span style="color: #ef4444;">Unlocked</span>' : 'Locked' ?></div>
            <div class="classes-hero-label">Access Status</div>
            <div class="classes-hero-desc"><?= $canBook ? 'You can reserve any class' : 'Upgrade to unlock' ?></div>
          </div>
        </div>
      </div>

      <!-- Upcoming Sessions Section -->
      <div class="section">
        <div class="section-header">
          <h2>
            <ion-icon name="calendar-outline" style="margin-right: 0.5rem;"></ion-icon>
            Upcoming Sessions
          </h2>
          <span class="badge <?= $canBook ? 'badge-success' : 'badge-warning' ?>"><?= $canBook ? 'Booking enabled' : 'Locked' ?></span>
        </div>
        <ul class="activity-list">
          <?php if (!$sessions): ?>
            <li class="activity-item"><div class="activity-info"><p style="color:#9ca3af">No sessions are currently scheduled.</p></div></li>
          <?php else: foreach ($sessions as $session): ?>
            <?php
              $sessionReserved = (int)$session['my_reserved'] === 1;
              $isFull = (int)$session['reserved'] >= (int)$session['capacity'];
              $startLabel = date('M d • H:i', strtotime((string)$session['start_at']));
              $endLabel   = $session['end_at'] ? date('H:i', strtotime((string)$session['end_at'])) : null;
            ?>
            <li class="activity-item">
              <div class="activity-icon"><ion-icon name="barbell"></ion-icon></div>
              <div class="activity-info">
                <div class="activity-title"><?= htmlspecialchars((string)$session['activity']) ?></div>
                <div class="activity-meta">
                  <?= $startLabel ?><?= $endLabel ? ' → ' . $endLabel : '' ?> · Coach <?= htmlspecialchars((string)$session['coach']) ?>
                </div>
                <div class="activity-meta" style="font-size:0.85rem;">
                  <?= (int)$session['reserved'] ?> / <?= (int)$session['capacity'] ?> spots booked
                </div>
              </div>
              <div style="display:flex;align-items:center;gap:0.5rem;">
                <?php if (!$canBook): ?>
                  <span class="badge badge-warning">Locked</span>
                <?php elseif ($sessionReserved): ?>
                  <form method="post" onsubmit="return confirm('Cancel this reservation?');">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="session_id" value="<?= (int)$session['id'] ?>">
                    <button class="btn btn-ghost" type="submit">Cancel</button>
                  </form>
                <?php elseif ($isFull): ?>
                  <span class="badge badge-warning">Full</span>
                <?php else: ?>
                  <form method="post">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                    <input type="hidden" name="action" value="reserve">
                    <input type="hidden" name="session_id" value="<?= (int)$session['id'] ?>">
                    <button class="btn" type="submit">Reserve</button>
                  </form>
                <?php endif; ?>
              </div>
            </li>
          <?php endforeach; endif; ?>
        </ul>
      </div>
    </main>
  </div>
</body>
</html>
