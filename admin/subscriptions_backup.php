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
?>
<!DOCTYPE html>
<html lang="fr"><!-- FR: attribut non visible conservé -->
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin — Subscriptions</title> <!-- FR: titre affiché, traduit -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <style>
    :root{--primary:#e50914;--primary-600:#cc0812;--border:#e9e9e9;--green:#28a745;--amber:#ffb703;--muted:#6c757d}
    *{box-sizing:border-box;font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;margin:0;padding:0}
    body{background:#fff}
    .wrap{max-width:1100px;margin:24px auto;padding:0 16px}
    h1{color:var(--primary)}
    .alert{padding:10px 12px;border-radius:8px;margin:10px 0}
    .ok{background:#e8f5e9;border:1px solid #c8e6c9}
    .err{background:#fdecea;border:1px solid #f5c6cb}
    .panel{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px;box-shadow:0 7px 25px rgba(0,0,0,.08);margin-top:18px}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    th,td{padding:10px;border-bottom:1px solid var(--border);text-align:left;vertical-align:middle}
    .btn{background:var(--primary);color:#fff;border:0;border-radius:8px;padding:.45rem .8rem;font-weight:700;cursor:pointer;text-decoration:none}
    .btn--ghost{background:transparent;color:var(--primary);border:1px solid var(--primary)}
    .btn--sm{padding:.3rem .55rem;border-radius:6px;font-size:.9rem}
    .actions{display:flex;gap:8px;justify-content:flex-end}
    .badge{display:inline-block;padding:2px 8px;border-radius:999px;font-weight:700;font-size:.85rem;color:#fff}
    .badge.ACTIVE{background:var(--green)}
    .badge.REJECTED{background:var(--muted)}
    .badge.EXPIRED{background:#dc3545}
    .badge.CANCELLED{background:var(--muted)}
    .badge.PENDING{background:var(--amber);color:#222}
    .kpi{font-weight:800;font-size:1rem;color:#111}
  </style>
</head>
<body>
  <div class="wrap">
    <h1>Subscriptions</h1> <!-- FR: en-tête principal -->

    <!-- FR: Messages d’état (succès / erreur) -->
    <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

    <!-- FR: KPI revenus du mois + lien retour Dashboard -->
    <div class="panel" style="display:flex;align-items:center;justify-content:space-between">
      <div class="kpi">Revenue (current month): <span style="color:var(--primary)">€ <?= number_format($revMonth/100, 2, ',', ' ') ?></span></div>
      <a class="btn btn--ghost" href="index.php">↩ Dashboard</a>
    </div>

    <!-- FR: Bloc des demandes en attente -->
    <div class="panel">
      <div style="display:flex;align-items:center;justify-content:space-between">
        <h2 style="margin:0;color:var(--primary)">Pending requests</h2>
      </div>
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Plan</th>
            <th>Price</th>
            <th>Requested at</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$pending): ?>
          <tr><td colspan="5" style="text-align:center;color:#666">No requests for now.</td></tr>
        <?php else: foreach ($pending as $d): ?>
          <tr>
            <td><?= htmlspecialchars($d['fullname']) ?><br><small><?= htmlspecialchars($d['email']) ?></small></td>
            <td><?= htmlspecialchars($d['plan_name']) ?></td>
            <td>€ <?= number_format(((int)$d['price_cents'])/100, 2, ',', ' ') ?></td>
            <td><?= htmlspecialchars($d['created_at']) ?></td>
            <td style="text-align:right">
              <div class="actions">
                <!-- FR: Approuver la demande -->
                <form method="post">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                  <input type="hidden" name="action" value="approve">
                  <button class="btn" type="submit">Approve</button>
                </form>
                <!-- FR: Refuser la demande -->
                <form method="post" onsubmit="return confirm('Reject this request?');">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
                  <input type="hidden" name="action" value="reject">
                  <button class="btn btn--ghost" type="submit">Reject</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

    <!-- FR: Bloc d’historique des abonnements -->
    <div class="panel">
      <h2 style="margin:0;color:var(--primary)">Recent history</h2>
      <table>
        <thead>
          <tr>
            <th>User</th>
            <th>Plan</th>
            <th>Status</th>
            <th>Amount paid</th>
            <th>Paid at</th>
            <th>Period</th>
            <th>Created at</th>
            <th style="text-align:right">Admin</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!$history): ?>
          <tr><td colspan="8" style="text-align:center;color:#666">No history.</td></tr>
        <?php else: foreach ($history as $h): ?>
          <tr>
            <td><?= htmlspecialchars($h['fullname']) ?></td>
            <td><?= htmlspecialchars($h['plan_name']) ?></td>
            <td><span class="badge <?= htmlspecialchars($h['status']) ?>"><?= htmlspecialchars($h['status']) ?></span></td>
            <td>
              <?php
                // FR: Affiche le montant en euros si > 0
                $amt = (int)$h['amount_cents'];
                echo $amt > 0 ? '€ '.number_format($amt/100, 2, ',', ' ') : '—';
              ?>
            </td>
            <td><?= htmlspecialchars($h['paid_at'] ?? '—') ?></td>
            <td><?= htmlspecialchars(($h['start_date'] ?: '—').' → '.($h['end_date'] ?: '—')) ?></td>
            <td><?= htmlspecialchars($h['created_at']) ?></td>
            <td style="text-align:right">
              <?php if ($h['status'] === 'ACTIVE'): ?>
                <!-- FR: Annulation par l’admin (sans remboursement) -->
                <form method="post" onsubmit="return confirm('Cancel this active subscription? No refund will be issued.');" style="display:inline-block">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="action" value="cancel_active_admin">
                  <input type="hidden" name="id" value="<?= (int)$h['id'] ?>">
                  <button class="btn btn--ghost btn--sm" type="submit">Cancel</button>
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
</body>
</html>
