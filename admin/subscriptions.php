<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('ADMIN');
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/subscriptions.php';

date_default_timezone_set('Europe/Paris');

// Marquer automatiquement les abonnements expirés
try {
  $expiredCount = expire_old_subscriptions($pdo);
} catch (Throwable $e) {
  $expiredCount = 0;
}

// CSRF
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf'];

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

// POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF token.');
    }
    $id  = (int)($_POST['id'] ?? 0);
    $act = (string)($_POST['action'] ?? '');
    if ($id <= 0) throw new RuntimeException('Invalid ID.');

    if ($act === 'approve') {
      $stmt = $pdo->prepare("
        UPDATE subscriptions s
        JOIN plans p ON p.id = s.plan_id
           SET s.status='ACTIVE',
               s.start_date = CURRENT_DATE(),
               s.end_date   = DATE_ADD(CURRENT_DATE(), INTERVAL 1 MONTH),
               s.amount_paid_cents = p.price_cents,
               s.paid_at = NOW(),
               s.approved_by = :admin
         WHERE s.id = :id AND s.status='PENDING'
      ");
      $stmt->execute([':admin'=>(int)($_SESSION['user']['id'] ?? 0), ':id'=>$id]);
      if ($stmt->rowCount()===0) throw new RuntimeException('Unable to approve (already processed?).');

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Subscription approved.')); exit;
    }

    if ($act === 'reject') {
      $stmt = $pdo->prepare("
        UPDATE subscriptions
           SET status='REJECTED', approved_by=:admin
         WHERE id=:id AND status='PENDING'
      ");
      $stmt->execute([':admin'=>(int)($_SESSION['user']['id'] ?? 0), ':id'=>$id]);
      if ($stmt->rowCount()===0) throw new RuntimeException('Unable to reject (already processed?).');

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Request rejected.')); exit;
    }

    if ($act === 'cancel_active_admin') {
      $stmt = $pdo->prepare("
        UPDATE subscriptions
           SET status='CANCELLED',
               end_date = GREATEST(COALESCE(start_date, CURRENT_DATE()), CURRENT_DATE())
         WHERE id=:id AND status='ACTIVE'
      ");
      $stmt->execute([':id'=>$id]);
      if ($stmt->rowCount()===0) throw new RuntimeException('Cancellation not possible (not active or already cancelled).');

      header('Location: '.$_SERVER['PHP_SELF'].'?ok='.urlencode('Subscription cancelled (no refund).')); exit;
    }

    throw new RuntimeException('Unknown action.');
  } catch (Throwable $e) {
    header('Location: '.$_SERVER['PHP_SELF'].'?err='.urlencode($e->getMessage())); exit;
  }
}

// Load data
$pending = $pdo->query("
  SELECT s.id, s.user_id, s.created_at,
         p.name AS plan_name, p.price_cents,
         u.fullname, u.email
    FROM subscriptions s
    JOIN users u ON u.id = s.user_id
    JOIN plans p ON p.id = s.plan_id
   WHERE s.status='PENDING'
ORDER BY s.created_at ASC
")->fetchAll(PDO::FETCH_ASSOC);

$history = $pdo->query("
  SELECT s.id, s.user_id, s.status, s.start_date, s.end_date, s.created_at,
         s.paid_at,
         COALESCE(s.amount_paid_cents, p.price_cents) AS amount_cents,
         p.name AS plan_name,
         u.fullname, u.email
    FROM subscriptions s
    JOIN users u ON u.id = s.user_id
    JOIN plans p ON p.id = s.plan_id
   WHERE s.status <> 'PENDING'
ORDER BY s.id DESC
   LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

$firstDay = (new DateTime('first day of this month'))->format('Y-m-d 00:00:00');
$lastDay  = (new DateTime('last day of this month'))->format('Y-m-d 23:59:59');
$revMonth = (int)$pdo->query("
  SELECT COALESCE(SUM(s.amount_paid_cents),0)
    FROM subscriptions s
   WHERE s.paid_at BETWEEN '{$firstDay}' AND '{$lastDay}'
     AND s.status IN ('ACTIVE','EXPIRED','CANCELLED')
")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Subscriptions</title>
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
      margin-bottom: 2rem;
    }

    .header h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    /* Alerts */
    .alert {
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
    }

    .alert-success {
      background: rgba(16, 185, 129, 0.2);
      border: 1px solid rgba(16, 185, 129, 0.4);
      color: #10b981;
    }

    .alert-error {
      background: rgba(239, 68, 68, 0.2);
      border: 1px solid rgba(239, 68, 68, 0.4);
      color: #ef4444;
    }

    /* KPI Card */
    .kpi-card {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 2rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .kpi-value {
      font-size: 2rem;
      font-weight: 700;
      color: #dc2626;
    }

    .kpi-label {
      color: #9ca3af;
      font-size: 0.875rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    /* Section */
    .section {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }

    /* Table */
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

    /* Buttons */
    .btn {
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      color: #fff;
      border: 0;
      border-radius: 8px;
      padding: 0.5rem 1rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      display: inline-block;
      transition: all 0.3s;
      font-size: 0.875rem;
    }

    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(220, 38, 38, 0.4);
    }

    .btn-ghost {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, 0.2);
      color: #fff;
    }

    .btn-ghost:hover {
      background: rgba(255, 255, 255, 0.05);
      border-color: #dc2626;
    }

    .btn-sm {
      padding: 0.35rem 0.7rem;
      font-size: 0.8rem;
    }

    /* Badge */
    .badge {
      padding: 0.375rem 0.875rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      display: inline-block;
    }

    .badge-ACTIVE {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .badge-REJECTED {
      background: rgba(156, 163, 175, 0.2);
      color: #9ca3af;
    }

    .badge-EXPIRED {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
    }

    .badge-CANCELLED {
      background: rgba(156, 163, 175, 0.2);
      color: #9ca3af;
    }

    .badge-PENDING {
      background: rgba(245, 158, 11, 0.2);
      color: #f59e0b;
    }

    @media (max-width: 991px) {
      .sidebar {
        width: 0;
        opacity: 0;
      }
      .main-content {
        margin-left: 0;
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
            <a href="index.php" class="nav-link">
              <ion-icon name="grid"></ion-icon>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="users.php" class="nav-link">
              <ion-icon name="people"></ion-icon>
              <span>Users</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="courses.php" class="nav-link">
              <ion-icon name="barbell"></ion-icon>
              <span>Activities & Classes</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="subscriptions.php" class="nav-link active">
              <ion-icon name="card"></ion-icon>
              <span>Subscriptions</span>
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
        <h1>Subscriptions & Payments</h1>
        <p style="color: #9ca3af;">Manage member subscriptions and payments.</p>
      </div>

      <!-- Alerts -->
      <?php if ($ok): ?>
        <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <!-- KPI Revenue -->
      <div class="kpi-card">
        <div>
          <div class="kpi-label">Revenue (Current Month)</div>
          <div class="kpi-value">€ <?= number_format($revMonth/100, 2, ',', ' ') ?></div>
        </div>
      </div>

      <!-- Pending Requests -->
      <div class="section">
        <h2 class="section-title">Pending Requests</h2>
        <div style="overflow-x:auto">
          <table>
            <thead>
              <tr>
                <td>User</td>
                <td>Plan</td>
                <td>Price</td>
                <td>Requested At</td>
                <td style="text-align:right">Actions</td>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($pending)): ?>
              <tr><td colspan="5" style="text-align:center;color:#9ca3af">No requests for now.</td></tr>
            <?php else: foreach ($pending as $d): ?>
              <tr>
                <td><?= htmlspecialchars($d['fullname']) ?><br><small style="color:#9ca3af"><?= htmlspecialchars($d['email']) ?></small></td>
                <td><?= htmlspecialchars($d['plan_name']) ?></td>
                <td>€ <?= number_format(((int)$d['price_cents'])/100, 2, ',', ' ') ?></td>
                <td><?= htmlspecialchars($d['created_at']) ?></td>
                <td style="text-align:right">
                  <div style="display:flex;gap:8px;justify-content:flex-end">
                    <form method="post" style="display:inline">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                      <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                      <input type="hidden" name="action" value="approve">
                      <button class="btn btn-sm" type="submit">Approve</button>
                    </form>
                    <form method="post" onsubmit="return confirm('Reject this request?');" style="display:inline">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                      <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                      <input type="hidden" name="action" value="reject">
                      <button class="btn btn-ghost btn-sm" type="submit">Reject</button>
                    </form>
                  </div>
                </td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Recent History -->
      <div class="section">
        <h2 class="section-title">Recent History</h2>
        <div style="overflow-x:auto">
          <table>
            <thead>
              <tr>
                <td>User</td>
                <td>Plan</td>
                <td>Status</td>
                <td>Amount Paid</td>
                <td>Paid At</td>
                <td>Period</td>
                <td>Created At</td>
                <td style="text-align:right">Admin</td>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($history)): ?>
              <tr><td colspan="8" style="text-align:center;color:#9ca3af">No history.</td></tr>
            <?php else: foreach ($history as $h): ?>
              <tr>
                <td><?= htmlspecialchars($h['fullname']) ?></td>
                <td><?= htmlspecialchars($h['plan_name']) ?></td>
                <td><span class="badge badge-<?= htmlspecialchars($h['status']) ?>"><?= htmlspecialchars($h['status']) ?></span></td>
                <td>
                  <?php
                    $amt = (int)$h['amount_cents'];
                    echo $amt > 0 ? '€ '.number_format($amt/100, 2, ',', ' ') : '—';
                  ?>
                </td>
                <td><?= htmlspecialchars($h['paid_at'] ?? '—') ?></td>
                <td><?= htmlspecialchars(($h['start_date'] ?: '—').' → '.($h['end_date'] ?: '—')) ?></td>
                <td><?= htmlspecialchars($h['created_at']) ?></td>
                <td style="text-align:right">
                  <?php if ($h['status'] === 'ACTIVE'): ?>
                    <form method="post" onsubmit="return confirm('Cancel this active subscription? No refund will be issued.');" style="display:inline-block">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                      <input type="hidden" name="action" value="cancel_active_admin">
                      <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                      <button class="btn btn-ghost btn-sm" type="submit">Cancel</button>
                    </form>
                  <?php else: ?>
                    —
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
