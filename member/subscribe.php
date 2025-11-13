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
$planProgress = null;
if ($active && !empty($active['end_date'])) {
  try {
    $end   = new DateTime($active['end_date']);
    $start = !empty($active['start_date']) ? new DateTime($active['start_date']) : null;
    $today = new DateTime('today');
    $daysLeft = max(0,(int)$today->diff($end)->format('%r%a'));
    if ($start) {
      $duration = max(1, (int)$start->diff($end)->format('%r%a'));
      $planProgress = max(0, min(100, (int)round(($daysLeft / $duration) * 100)));
    }
  }
  catch(Throwable $e){
    $daysLeft = null;
    $planProgress = null;
  }
}

$planName = (string)($active['plan_name'] ?? ($pending['plan_name'] ?? 'No plan selected'));
$statusLabel = $active ? 'Active plan' : ($pending ? 'Pending approval' : 'No plan');
$statusHint = $active
  ? 'You have full access to classes.'
  : ($pending ? 'Waiting for admin validation.' : 'Pick a plan to unlock the schedule.');
$accessLabel = $canBook ? 'Unlocked' : 'Locked';
$accessHint  = $canBook ? 'Class reservations available.' : 'Upgrade to unlock reservations.';
$daysLabel   = $daysLeft !== null ? $daysLeft . ' day(s)' : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Subscription Plans</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <?php include __DIR__ . '/../shared/member-styles.php'; ?>
  <style>
    /* Enhanced Pricing Cards with 3D Effects */
    .pricing-hero {
      text-align: center;
      margin-bottom: 3rem;
      animation: fadeInDown 0.8s ease-out;
    }

    .pricing-hero h1 {
      font-size: 3rem;
      font-weight: 900;
      margin-bottom: 1rem;
      background: linear-gradient(135deg, #fff 0%, #dc2626 50%, #ef4444 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .pricing-hero p {
      font-size: 1.25rem;
      color: #9ca3af;
      max-width: 600px;
      margin: 0 auto;
    }

    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Modern Alert Messages */
    .alert {
      padding: 1.25rem 1.75rem;
      border-radius: 16px;
      margin-bottom: 2rem;
      border: 2px solid;
      animation: slideInRight 0.5s ease-out;
      display: flex;
      align-items: center;
      gap: 1rem;
      font-weight: 500;
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .alert.ok {
      background: rgba(239, 68, 68, 0.15);
      border-color: #ef4444;
      color: #ef4444;
    }

    .alert.err {
      background: rgba(239, 68, 68, 0.15);
      border-color: #ef4444;
      color: #ef4444;
    }

    .alert ion-icon {
      font-size: 1.5rem;
      flex-shrink: 0;
    }

    /* Status Cards Enhanced */
    .status-card {
      background: transparent;
      border: none;
      border-radius: 0;
      padding: 2.5rem 0;
      margin-bottom: 3rem;
      position: relative;
      overflow: visible;
      animation: scaleIn 0.6s ease-out;
    }

    .status-card::after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--member-border), transparent);
    }

    @keyframes scaleIn {
      from {
        opacity: 0;
        transform: scale(0.9);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }


    .days-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.75rem 1.5rem;
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.2), rgba(239, 68, 68, 0.2));
      border: 1px solid rgba(220, 38, 38, 0.4);
      border-radius: 12px;
      color: #fff;
      font-weight: 600;
    }

    .btn-dark {
      background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
      color: #fff;
      border: 0;
      border-radius: 12px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s;
      width: 100%;
      justify-content: center;
    }

    .btn-dark:hover {
      background: linear-gradient(135deg, #374151 0%, #4b5563 100%);
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
    }

    /* Enhanced Pricing Grid */
    .plans-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
      gap: 2.5rem;
      margin-bottom: 3rem;
      perspective: 1000px;
    }

    .plan-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 24px;
      padding: 2.5rem;
      transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: visible;
      transform-style: preserve-3d;
      animation: fadeInUp 0.6s ease-out;
      animation-fill-mode: both;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(40px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .plan-card:nth-child(1) { animation-delay: 0.1s; }
    .plan-card:nth-child(2) { animation-delay: 0.2s; }
    .plan-card:nth-child(3) { animation-delay: 0.3s; }

    /* Recommended Badge */
    .plan-card.recommended {
      border-color: #dc2626;
      background: #ffffff;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.15), 0 0 0 2px #dc2626;
      transform: scale(1.05);
    }

    .recommended-badge {
      position: absolute;
      top: -12px;
      right: 20px;
      background: linear-gradient(135deg, #dc2626, #ef4444);
      color: white;
      padding: 0.5rem 1.5rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      box-shadow: 0 4px 12px rgba(220, 38, 38, 0.5);
      animation: pulse 2s infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    /* Currently Active Badge */
    .active-badge {
      position: absolute;
      top: -12px;
      left: 20px;
      background: linear-gradient(135deg, #ef4444, #ffffff);
      color: white;
      padding: 0.5rem 1.5rem;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 800;
      letter-spacing: 0.1em;
      text-transform: uppercase;
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.5);
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    /* 3D Hover Effect */
    .plan-card:hover {
      transform: translateY(-16px) rotateX(5deg) rotateY(-5deg) scale(1.02);
      box-shadow:
        0 30px 60px rgba(220, 38, 38, 0.3),
        0 0 0 1px rgba(220, 38, 38, 0.2),
        inset 0 1px 0 rgba(255, 255, 255, 0.1);
      border-color: rgba(220, 38, 38, 0.6);
    }

    .plan-card.recommended:hover {
      transform: translateY(-16px) rotateX(5deg) rotateY(-5deg) scale(1.07);
    }

    /* Plan Header */
    .plan-header {
      text-align: center;
      padding-bottom: 2rem;
      border-bottom: 1px solid #e5e7eb;
      margin-bottom: 2rem;
    }

    .plan-icon {
      width: 90px;
      height: 90px;
      margin: 0 auto 1.5rem;
      background: transparent;
      border: 3px solid var(--plan-color, #dc2626);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      color: var(--plan-color, #dc2626);
      transition: all 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      position: relative;
      overflow: hidden;
    }

    .plan-icon::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(circle, var(--plan-color, #dc2626) 0%, transparent 70%);
      opacity: 0;
      transition: opacity 0.5s;
    }

    .plan-card:hover .plan-icon {
      transform: rotateY(360deg) scale(1.15);
      box-shadow: 0 0 40px var(--plan-color, #dc2626);
    }

    .plan-card:hover .plan-icon::before {
      opacity: 0.2;
    }

    .plan-name {
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
      color: #1e293b;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .plan-subtitle {
      color: #9ca3af;
      font-size: 0.95rem;
      margin-bottom: 1.5rem;
    }

    /* Pricing Display */
    .plan-pricing {
      text-align: center;
      margin-bottom: 2rem;
    }

    .plan-price {
      font-size: 4rem;
      font-weight: 900;
      line-height: 1;
      background: linear-gradient(135deg, #dc2626, #ef4444);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      gap: 0.25rem;
    }

    .plan-price .currency {
      font-size: 2rem;
      margin-top: 0.5rem;
    }

    .plan-price .period {
      font-size: 1.25rem;
      color: #9ca3af;
      font-weight: 600;
      align-self: flex-end;
      margin-bottom: 0.75rem;
      -webkit-text-fill-color: #9ca3af;
    }

    /* Features List */
    .plan-features {
      list-style: none;
      margin: 2rem 0;
      padding: 0;
    }

    .plan-features li {
      padding: 1rem 0;
      display: flex;
      align-items: flex-start;
      gap: 1rem;
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      transition: all 0.3s;
    }

    .plan-features li:last-child {
      border-bottom: none;
    }

    .plan-features li:hover {
      padding-left: 0.5rem;
      background: rgba(220, 38, 38, 0.05);
      margin: 0 -0.5rem;
      padding-right: 0.5rem;
      border-radius: 8px;
    }

    .plan-features ion-icon {
      color: #ef4444;
      font-size: 1.5rem;
      flex-shrink: 0;
      margin-top: 0.1rem;
    }

    .plan-features li span {
      flex: 1;
      color: #e5e7eb;
      font-weight: 500;
    }

    /* CTA Button */
    .plan-cta {
      width: 100%;
      padding: 1.25rem 2rem;
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      color: #fff;
      border: 0;
      border-radius: 16px;
      font-weight: 700;
      font-size: 1.1rem;
      cursor: pointer;
      transition: all 0.4s;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      box-shadow: 0 8px 24px rgba(220, 38, 38, 0.3);
      position: relative;
      overflow: hidden;
    }

    .plan-cta::before {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      width: 0;
      height: 0;
      border-radius: 50%;
      background: rgba(255, 255, 255, 0.2);
      transform: translate(-50%, -50%);
      transition: width 0.6s, height 0.6s;
    }

    .plan-cta:hover::before {
      width: 300px;
      height: 300px;
    }

    .plan-cta:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 40px rgba(220, 38, 38, 0.5);
    }

    .plan-cta ion-icon {
      font-size: 1.5rem;
      position: relative;
      z-index: 1;
    }

    .plan-cta span {
      position: relative;
      z-index: 1;
    }

    /* Responsive adjustments */
    @media (max-width: 991px) {
      .plans-grid {
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      }

      .plan-card.recommended {
        transform: scale(1);
      }
    }

    @media (max-width: 640px) {
      .pricing-hero h1 {
        font-size: 2rem;
      }

      .plan-price {
        font-size: 3rem;
      }

      .plans-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
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
        <div>
          <h1>Membership Plans</h1>
          <p style="color:#9ca3af;">Review your status and upgrade whenever you're ready.</p>
        </div>
        <div class="header-date">
          <ion-icon name="calendar-outline"></ion-icon>
          <span><?= date('l, F j') ?></span>
        </div>
      </div>

      <!-- Subscription Status Hero -->
      <div class="subscription-status-hero">
        <div class="subscription-hero-main">
          <div class="subscription-hero-icon">
            <ion-icon name="trophy"></ion-icon>
          </div>
          <div class="subscription-hero-content">
            <div class="subscription-hero-plan"><?= htmlspecialchars($planName) ?></div>
            <div class="subscription-hero-label">Current Membership</div>
            <div class="subscription-hero-status <?= $active ? 'active' : 'inactive' ?>">
              <ion-icon name="<?= $active ? 'checkmark-circle' : ($pending ? 'time' : 'alert-circle') ?>"></ion-icon>
              <?= htmlspecialchars($statusLabel) ?>
            </div>
          </div>
        </div>

        <div class="subscription-hero-divider"></div>

        <div class="subscription-hero-stats">
          <div class="subscription-hero-stat">
            <ion-icon name="calendar-outline"></ion-icon>
            <div>
              <div class="subscription-stat-value"><?= htmlspecialchars($daysLabel) ?></div>
              <div class="subscription-stat-label">Remaining</div>
            </div>
          </div>
          <div class="subscription-hero-stat">
            <ion-icon name="<?= $canBook ? 'lock-open' : 'lock-closed' ?>"></ion-icon>
            <div>
              <div class="subscription-stat-value"><?= $accessLabel ?></div>
              <div class="subscription-stat-label">Class Access</div>
            </div>
          </div>
        </div>
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
          <?php
          $planIcons = [
            'BASIC' => 'walk-outline',
            'PLUS' => 'fitness-outline',
            'PRO' => 'trophy-outline'
          ];
          $planColors = [
            'BASIC' => '#94a3b8',
            'PLUS' => '#ef4444',
            'PRO' => '#dc2626'
          ];
          $planIndex = 0;
          foreach ($plans as $p):
            $planIndex++;
            $planNameUpper = strtoupper($p['name']);
            $iconName = $planIcons[$planNameUpper] ?? 'star-outline';
            $planColor = $planColors[$planNameUpper] ?? '#ef4444';
            $isRecommended = $planNameUpper === 'PLUS';
          ?>
            <div class="plan-card <?= $isRecommended ? 'recommended' : '' ?>">
              <?php if ($isRecommended): ?>
                <div class="recommended-badge">⭐ MOST POPULAR</div>
              <?php endif; ?>

              <div class="plan-header">
                <div class="plan-icon" style="--plan-color: <?= $planColor ?>;">
                  <ion-icon name="<?= $iconName ?>"></ion-icon>
                </div>
                <div class="plan-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="plan-subtitle">Perfect for <?= $planNameUpper === 'BASIC' ? 'beginners' : ($planNameUpper === 'PLUS' ? 'enthusiasts' : 'professionals') ?></div>
              </div>

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
                <button class="btn <?= $isRecommended ? 'btn-primary' : '' ?>" type="submit">
                  <ion-icon name="rocket-outline"></ion-icon>
                  Choose <?= htmlspecialchars($p['name']) ?>
                </button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</body>
</html>
