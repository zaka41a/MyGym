<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('ADMIN'); // FR: page admin seulement
require_once __DIR__ . '/../backend/db.php';

date_default_timezone_set('Europe/Paris');

/* ===== CSRF =====
   FR: Génère un token CSRF si absent pour sécuriser les requêtes POST */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(32));
}
$CSRF = $_SESSION['csrf'];

/* FR: Messages (affichés en haut de page) */
$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

/* ===== Actions =====
   FR: Traite les formulaires (PRG: Post/Redirect/Get) */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF token.');
    }
    $id  = (int)($_POST['id'] ?? 0);
    $act = (string)($_POST['action'] ?? '');
    if ($id <= 0) throw new RuntimeException('Invalid ID.');

    if ($act === 'approve') {
      // FR: Passe en ACTIVE, fixe la période, enregistre le montant payé et la date de paiement
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
      // FR: Marque la demande comme refusée
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
      // FR: Annule un abonnement ACTIF sans remboursement (on garde paid_at / amount_paid_cents)
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

/* ===== Données =====
   FR: 1) Demandes en attente
       2) Historique (tous sauf PENDING)
       3) KPI revenus du mois courant (inclut CANCELLED/EXPIRED/ACTIVE si payé dans le mois) */
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

// Count pending requests
$pendingCount = count($pending);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Subscriptions & Payments</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <?php include __DIR__ . '/../shared/admin-styles.php'; ?>
  <style>
    /* Additional badge styles for subscription statuses */
    .badge-inactive {
      background: rgba(107, 114, 128, 0.2);
      color: #6b7280;
      border: 1px solid rgba(107, 114, 128, 0.3);
    }

    .badge-admin {
      background: rgba(220, 38, 38, 0.2);
      color: #dc2626;
      border: 1px solid rgba(220, 38, 38, 0.3);
    }

    .badge-coach {
      background: rgba(59, 130, 246, 0.2);
      color: #3b82f6;
      border: 1px solid rgba(59, 130, 246, 0.3);
    }

    .badge-member {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
      border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .actions-group {
      display: flex;
      gap: 0.5rem;
      justify-content: flex-end;
      flex-wrap: wrap;
    }

    .no-data {
      text-align: center;
      padding: 2rem;
      color: #6b7280;
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
              <span>Subscriptions & Payments</span>
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
        <p style="color: #9ca3af;">Manage subscription requests and payment history.</p>
      </div>

      <!-- Alerts -->
      <?php if ($ok): ?>
        <div class="alert alert-success"><?= htmlspecialchars($ok) ?></div>
      <?php endif; ?>
      <?php if ($err): ?>
        <div class="alert alert-error"><?= htmlspecialchars($err) ?></div>
      <?php endif; ?>

      <!-- Modern Subscriptions Stats -->
      <div class="subs-stats-grid">
        <div class="subs-stat-card" style="--card-gradient: linear-gradient(135deg, #059669, #047857);">
          <div class="subs-stat-icon-wrapper">
            <ion-icon name="cash"></ion-icon>
          </div>
          <div class="subs-stat-content">
            <div class="subs-stat-value">€<?= number_format($revMonth/100, 2) ?></div>
            <div class="subs-stat-label">Monthly Revenue</div>
            <div class="subs-stat-desc">Current month earnings</div>
          </div>
          <div class="subs-stat-badge" style="background: rgba(5, 150, 105, 0.2); color: #059669;">
            <ion-icon name="trending-up"></ion-icon>
            <span><?= date('M Y') ?></span>
          </div>
        </div>

        <div class="subs-stat-card" style="--card-gradient: linear-gradient(135deg, #ea580c, #c2410c);">
          <div class="subs-stat-icon-wrapper">
            <ion-icon name="time"></ion-icon>
          </div>
          <div class="subs-stat-content">
            <div class="subs-stat-value"><?= $pendingCount ?></div>
            <div class="subs-stat-label">Pending Requests</div>
            <div class="subs-stat-desc">Awaiting approval</div>
          </div>
          <div class="subs-stat-badge" style="background: rgba(234, 88, 12, 0.2); color: #ea580c;">
            <ion-icon name="hourglass"></ion-icon>
            <span>Action needed</span>
          </div>
        </div>
      </div>

      <!-- Modern Pending Requests Section -->
      <div class="subs-pending-section">
        <div class="subs-pending-header">
          <div class="subs-pending-title-wrapper">
            <ion-icon name="hourglass-outline"></ion-icon>
            <div>
              <h2>Pending Requests</h2>
              <p>Review and approve subscription requests</p>
            </div>
          </div>
          <div class="subs-pending-badge">
            <ion-icon name="alert-circle"></ion-icon>
            <span><?= $pendingCount ?> Pending</span>
          </div>
        </div>
        <div class="subs-table-wrapper">
          <table class="subs-table">
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
            <?php if (!$pending): ?>
              <tr>
                <td colspan="5" class="no-data">No pending requests at the moment.</td>
              </tr>
            <?php else: foreach ($pending as $d): ?>
              <tr>
                <td>
                  <div style="line-height:1.4">
                    <strong><?= htmlspecialchars($d['fullname']) ?></strong><br>
                    <small style="color:#9ca3af"><?= htmlspecialchars($d['email']) ?></small>
                  </div>
                </td>
                <td><?= htmlspecialchars($d['plan_name']) ?></td>
                <td>€<?= number_format(((int)$d['price_cents'])/100, 2) ?></td>
                <td><?= date('M d, Y H:i', strtotime($d['created_at'])) ?></td>
                <td>
                  <div class="actions-group">
                    <form method="post" style="display:inline">
                      <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                      <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                      <input type="hidden" name="action" value="approve">
                      <button class="btn btn-sm" type="submit">Approve</button>
                    </form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Reject this subscription request?');">
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

      <!-- Modern History Section -->
      <div class="subs-history-section">
        <div class="subs-history-header">
          <div class="subs-history-title-wrapper">
            <ion-icon name="receipt-outline"></ion-icon>
            <div>
              <h2>Subscription History</h2>
              <p>Recent subscription transactions and changes</p>
            </div>
          </div>
          <div class="subs-history-badge">
            <ion-icon name="list"></ion-icon>
            <span><?= count($history) ?> Records</span>
          </div>
        </div>
        <div class="subs-table-wrapper">
          <table class="subs-table">
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
            <?php if (!$history): ?>
              <tr>
                <td colspan="8" class="no-data">No subscription history available.</td>
              </tr>
            <?php else: foreach ($history as $h): ?>
              <tr>
                <td>
                  <div style="line-height:1.4">
                    <strong><?= htmlspecialchars($h['fullname']) ?></strong><br>
                    <small style="color:#9ca3af"><?= htmlspecialchars($h['email']) ?></small>
                  </div>
                </td>
                <td><?= htmlspecialchars($h['plan_name']) ?></td>
                <td>
                  <?php
                    $status = strtoupper($h['status']);
                    $badgeClass = ($status === 'ACTIVE') ? 'badge-success' : 'badge-inactive';
                  ?>
                  <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                </td>
                <td>
                  <?php
                    $amt = (int)$h['amount_cents'];
                    echo $amt > 0 ? '€'.number_format($amt/100, 2) : '—';
                  ?>
                </td>
                <td><?= $h['paid_at'] ? date('M d, Y H:i', strtotime($h['paid_at'])) : '—' ?></td>
                <td>
                  <?php
                    $start = $h['start_date'] ? date('M d, Y', strtotime($h['start_date'])) : '—';
                    $end = $h['end_date'] ? date('M d, Y', strtotime($h['end_date'])) : '—';
                    echo "$start → $end";
                  ?>
                </td>
                <td><?= date('M d, Y', strtotime($h['created_at'])) ?></td>
                <td style="text-align:right">
                  <?php if ($h['status'] === 'ACTIVE'): ?>
                    <form method="post" style="display:inline" onsubmit="return confirm('Cancel this active subscription? No refund will be issued.');">
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
