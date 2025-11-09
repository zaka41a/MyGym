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
    .nav-link.locked { opacity: 0.6; }
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
    .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; }
    .alert.ok { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
    .alert.err { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    thead td {
      font-weight: 600; color: #9ca3af; font-size: 0.85rem; text-transform: uppercase;
      letter-spacing: 0.05em; padding-bottom: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    tbody tr { border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.2s; }
    tbody tr:hover { background: rgba(220, 38, 38, 0.05); }
    td { padding: 1rem 0.75rem; vertical-align: middle; }
    .badge {
      display: inline-flex; align-items: center; gap: 0.4rem;
      background: rgba(255, 255, 255, 0.1); padding: 0.25rem 0.75rem;
      border-radius: 20px; font-size: 0.85rem; font-weight: 600;
    }
    .badge.success { background: rgba(34, 197, 94, 0.2); color: #4ade80; }
    .badge.warning { background: rgba(234, 179, 8, 0.2); color: #facc15; }
    .btn {
      display: inline-flex; align-items: center; gap: 0.5rem;
      background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
      color: #fff; border: none; border-radius: 10px; padding: 0.6rem 1.2rem;
      font-weight: 600; cursor: pointer; transition: all 0.3s; font-family: 'Poppins', sans-serif;
      font-size: 0.9rem;
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4); }
    .btn-dark {
      background: rgba(255, 255, 255, 0.1);
    }
    .btn-dark:hover { background: rgba(255, 255, 255, 0.15); transform: translateY(-2px); }
    .locked-msg {
      background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.3);
      padding: 1rem; border-radius: 12px; color: #facc15; display: flex; align-items: center; gap: 0.75rem;
    }
    @media (max-width: 991px) {
      .sidebar { width: 0; opacity: 0; }
      .main-content { margin-left: 0; }
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

      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <?php if (!$canBook): ?>
        <div class="locked-msg">
          <ion-icon name="lock-closed" style="font-size:1.5rem"></ion-icon>
          <div>
            <strong>Class reservations are locked</strong><br>
            <span style="font-size:0.9rem">Upgrade to PLUS or PRO plan to reserve classes with our coaches.</span>
          </div>
        </div>
      <?php endif; ?>

      <div class="section">
        <h2 style="font-size:1.25rem;font-weight:600;margin-bottom:1.5rem">Upcoming Sessions</h2>
        <table>
          <thead>
            <tr>
              <td>Start</td><td>End</td><td>Class</td><td>Coach</td>
              <td>Capacity</td><td>Booked</td><td>Available</td><td>Action</td>
            </tr>
          </thead>
          <tbody>
          <?php if (!$sessions): ?>
            <tr><td colspan="8" style="color:#6b7280;text-align:center;padding:2rem">No upcoming sessions available</td></tr>
          <?php else: foreach ($sessions as $s):
            $cap   = (int)$s['capacity'];
            $res   = (int)$s['reserved'];
            $left  = max(0, $cap - $res);
            $mine  = ((int)$s['my_reserved'] === 1);
          ?>
            <tr>
              <td><?= date('d/m H:i', strtotime((string)$s['start_at'])) ?></td>
              <td><?= date('d/m H:i', strtotime((string)$s['end_at'])) ?></td>
              <td><strong><?= htmlspecialchars($s['activity']) ?></strong></td>
              <td><?= htmlspecialchars($s['coach']) ?></td>
              <td><span class="badge"><ion-icon name="people"></ion-icon><?= $cap ?></span></td>
              <td><span class="badge success"><ion-icon name="checkmark-done"></ion-icon><?= $res ?></span></td>
              <td><span class="badge warning"><ion-icon name="time"></ion-icon><?= $left ?></span></td>
              <td>
                <?php if (!$canBook): ?>
                  <span style="color:#6b7280"><ion-icon name="lock-closed"></ion-icon> Locked</span>
                <?php elseif ($mine): ?>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                    <button class="btn btn-dark" type="submit">
                      <ion-icon name="close-circle"></ion-icon> Cancel
                    </button>
                  </form>
                <?php elseif ($left <= 0): ?>
                  <span style="color:#6b7280"><ion-icon name="ban"></ion-icon> Full</span>
                <?php else: ?>
                  <form method="post" style="display:inline">
                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                    <input type="hidden" name="action" value="reserve">
                    <input type="hidden" name="session_id" value="<?= (int)$s['id'] ?>">
                    <button class="btn" type="submit">
                      <ion-icon name="checkmark-circle"></ion-icon> Reserve
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
