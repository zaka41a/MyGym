<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('MEMBER','ADMIN');
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/subscriptions.php';

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

$userId   = (int)($_SESSION['user']['id'] ?? 0);
$userName = $_SESSION['user']['fullname'] ?? 'Member';

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

$canBook = false;
try {
  $canBook = has_class_access($pdo, $userId);
} catch (Throwable $e) {
  $canBook = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF token.');
    }

    $action = (string)($_POST['action'] ?? '');

    if ($action === 'choose_plan') {
      $planId = (int)($_POST['plan_id'] ?? 0);
      if ($planId <= 0) throw new RuntimeException('Invalid plan.');
      if (is_subscribed($pdo, $userId))       throw new RuntimeException("You already have an active subscription.");
      if (get_pending_request($pdo, $userId)) throw new RuntimeException("A request is already pending.");

      $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, plan_id, status) VALUES (:u,:p,'PENDING')");
      $stmt->execute([':u'=>$userId, ':p'=>$planId]);

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Request sent. An administrator must validate it.'));
      exit;
    }

    if ($action === 'cancel_active') {
      $sid = (int)($_POST['id'] ?? 0);
      if ($sid <= 0) {
        $sid = (int)($pdo->query("SELECT id FROM subscriptions WHERE user_id={$userId} AND status='ACTIVE' ORDER BY id DESC LIMIT 1")->fetchColumn() ?: 0);
      }
      if ($sid <= 0) throw new RuntimeException('No active subscription to cancel.');

      $stmt = $pdo->prepare("UPDATE subscriptions SET status='CANCELLED', end_date=CURRENT_DATE() WHERE id=:id AND user_id=:u AND status='ACTIVE'");
      $stmt->execute([':id'=>$sid, ':u'=>$userId]);
      if ($stmt->rowCount()===0) throw new RuntimeException("Cancellation failed (already cancelled?).");

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Subscription cancelled.'));
      exit;
    }

    if ($action === 'cancel_request') {
      $sid = (int)($_POST['id'] ?? 0);
      if ($sid <= 0) {
        $sid = (int)($pdo->query("SELECT id FROM subscriptions WHERE user_id={$userId} AND status='PENDING' ORDER BY id DESC LIMIT 1")->fetchColumn() ?: 0);
      }
      if ($sid <= 0) throw new RuntimeException('No pending request to cancel.');

      $stmt = $pdo->prepare("UPDATE subscriptions SET status='CANCELLED' WHERE id=:id AND user_id=:u AND status='PENDING'");
      $stmt->execute([':id'=>$sid, ':u'=>$userId]);
      if ($stmt->rowCount()===0) throw new RuntimeException("Cancellation failed (already processed?).");

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Request cancelled.'));
      exit;
    }

    throw new RuntimeException('Unknown action.');
  } catch (Throwable $e) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='.urlencode($e->getMessage()));
    exit;
  }
}

try {
  $active  = get_active_subscription($pdo, $userId);
  $pending = get_pending_request($pdo, $userId);
  $plans   = $pdo->query("SELECT id, code, name, price_cents, features FROM plans ORDER BY price_cents ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $active = $pending = null;
  $plans = [];
}

$daysLeft = null;
if ($active && !empty($active['end_date'])) {
  try {
    $end   = new DateTime($active['end_date']);
    $today = new DateTime('today');
    $daysLeft = max(0,(int)$today->diff($end)->format('%r%a'));
  }
  catch(Throwable $e){ $daysLeft=null; }
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
    .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; }
    .alert.ok { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
    .alert.err { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
    .status-card {
      background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px; padding: 2rem; margin-bottom: 2rem;
    }
    .status-card.active { border-left: 4px solid #4ade80; }
    .status-card.pending { border-left: 4px solid #facc15; }
    .plans-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
    .plan-card {
      background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px; padding: 2rem; transition: all 0.3s;
    }
    .plan-card:hover { transform: translateY(-5px); border-color: #dc2626; box-shadow: 0 20px 40px rgba(220,38,38,0.3); }
    .plan-name { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.5rem; }
    .plan-price { font-size: 2.5rem; font-weight: 800; color: #dc2626; margin-bottom: 1rem; }
    .plan-price span { font-size: 1rem; color: #9ca3af; font-weight: 500; }
    .plan-features { list-style: none; margin: 1.5rem 0; }
    .plan-features li {
      padding: 0.5rem 0; color: #9ca3af; display: flex; align-items: center; gap: 0.5rem;
    }
    .plan-features ion-icon { color: #4ade80; font-size: 1.2rem; }
    .btn {
      display: inline-flex; align-items: center; gap: 0.5rem;
      background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
      color: #fff; border: none; border-radius: 10px; padding: 0.75rem 1.5rem;
      font-weight: 600; cursor: pointer; transition: all 0.3s; font-family: 'Poppins', sans-serif;
      text-decoration: none; width: 100%; justify-content: center;
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4); }
    .btn-dark { background: rgba(255, 255, 255, 0.1); }
    .btn-dark:hover { background: rgba(255, 255, 255, 0.15); }
    .days-badge {
      display: inline-flex; align-items: center; gap: 0.5rem;
      background: rgba(34, 197, 94, 0.2); color: #4ade80;
      padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; margin-top: 1rem;
    }
    @media (max-width: 991px) {
      .sidebar { width: 0; opacity: 0; }
      .main-content { margin-left: 0; }
      .plans-grid { grid-template-columns: 1fr; }
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
              <a href="courses.php" class="nav-link"><ion-icon name="calendar"></ion-icon><span>My Classes</span></a>
            <?php else: ?>
              <a href="subscribe.php" class="nav-link locked" title="Upgrade to PLUS/PRO to unlock"><ion-icon name="lock-closed"></ion-icon><span>My Classes (Locked)</span></a>
            <?php endif; ?>
          </li>
          <li class="nav-item"><a href="subscribe.php" class="nav-link active"><ion-icon name="card"></ion-icon><span>Subscription</span></a></li>
          <li class="nav-item"><a href="profile.php" class="nav-link"><ion-icon name="person-circle"></ion-icon><span>Profile</span></a></li>
        </ul>
        <a href="/MyGym/backend/logout.php" class="logout-btn"><ion-icon name="log-out"></ion-icon><span>Logout</span></a>
      </nav>
    </aside>
    <main class="main-content">
      <div class="header">
        <h1>My Subscription</h1>
        <p style="color: #9ca3af;">Manage your membership plan and benefits</p>
      </div>

      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <?php if ($active): ?>
        <div class="status-card active">
          <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem">
            <ion-icon name="checkmark-circle" style="font-size:3rem;color:#4ade80"></ion-icon>
            <div>
              <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.25rem">Active Subscription</h2>
              <p style="font-size:1.25rem;color:#dc2626;font-weight:600"><?= htmlspecialchars($active['plan_name']) ?></p>
            </div>
          </div>
          <div style="color:#9ca3af;margin-bottom:1rem">
            <strong>Start:</strong> <?= htmlspecialchars($active['start_date'] ?: '—') ?> &nbsp;|&nbsp;
            <strong>End:</strong> <?= htmlspecialchars($active['end_date'] ?: '—') ?>
          </div>
          <?php if ($daysLeft !== null): ?>
            <div class="days-badge">
              <ion-icon name="time"></ion-icon>
              <strong><?= (int)$daysLeft ?> day(s) remaining</strong>
            </div>
          <?php endif; ?>
          <div style="display:flex;gap:1rem;margin-top:1.5rem;flex-wrap:wrap">
            <a class="btn" href="index.php" style="flex:1">
              <ion-icon name="arrow-back"></ion-icon> Back to Dashboard
            </a>
            <form method="post" onsubmit="return confirm('Confirm subscription cancellation?');" style="flex:1">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
              <input type="hidden" name="action" value="cancel_active">
              <input type="hidden" name="id" value="<?= (int)($active['id'] ?? 0) ?>">
              <button class="btn btn-dark" type="submit">
                <ion-icon name="close-circle"></ion-icon> Cancel Subscription
              </button>
            </form>
          </div>
        </div>

      <?php elseif ($pending): ?>
        <div class="status-card pending">
          <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem">
            <ion-icon name="time" style="font-size:3rem;color:#facc15"></ion-icon>
            <div>
              <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:0.25rem">Pending Request</h2>
              <p style="font-size:1.25rem;color:#dc2626;font-weight:600"><?= htmlspecialchars($pending['plan_name']) ?></p>
            </div>
          </div>
          <p style="color:#9ca3af">An administrator must validate your subscription request.</p>
          <div style="margin-top:1.5rem">
            <form method="post" onsubmit="return confirm('Cancel this request?');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
              <input type="hidden" name="action" value="cancel_request">
              <input type="hidden" name="id" value="<?= (int)($pending['id'] ?? 0) ?>">
              <button class="btn btn-dark" type="submit">
                <ion-icon name="close-circle"></ion-icon> Cancel Request
              </button>
            </form>
          </div>
        </div>

      <?php else: ?>
        <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:1.5rem">Choose Your Plan</h2>
        <div class="plans-grid">
          <?php foreach ($plans as $p): ?>
            <div class="plan-card">
              <div class="plan-name"><?= htmlspecialchars($p['name']) ?></div>
              <div class="plan-price">$<?= number_format($p['price_cents']/100, 0) ?><span>/month</span></div>
              <ul class="plan-features">
                <?php foreach (explode("\n", (string)$p['features']) as $f): if(trim($f)==='') continue; ?>
                  <li><ion-icon name="checkmark-circle"></ion-icon><?= htmlspecialchars(ltrim($f, "- ")) ?></li>
                <?php endforeach; ?>
              </ul>
              <form method="post">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="choose_plan">
                <input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>">
                <button class="btn" type="submit">Choose <?= htmlspecialchars($p['name']) ?></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
