<?php
declare(strict_types=1);
session_start();

// Authentification et rôles requis
require_once __DIR__ . '/../backend/auth.php';
requireRole('MEMBER','ADMIN');
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/subscriptions.php'; // <= import

// Génération du token CSRF si absent
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

$userId   = (int)($_SESSION['user']['id'] ?? 0);
$userName = $_SESSION['user']['fullname'] ?? 'Member';

// Messages de retour (succès/erreur)
$ok  = $_GET['ok']  ?? null; 
$err = $_GET['err'] ?? null;

/* Accès aux cours (vérifie si plan PLUS/PRO) */
$canBook = false;
try { 
  $canBook = has_class_access($pdo, $userId); 
} catch (Throwable $e) { 
  $canBook = false; 
}

/* Gestion des actions POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    // Vérification CSRF
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF token.');
    }

    $action = (string)($_POST['action'] ?? '');

    // Choisir un plan
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

    // Annuler un abonnement actif
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

    // Annuler une demande en attente
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

/* Récupération des données de l’utilisateur */
try {
  $active  = get_active_subscription($pdo, $userId);
  $pending = get_pending_request($pdo, $userId);
  $plans   = $pdo->query("SELECT id, code, name, price_cents, features FROM plans ORDER BY price_cents ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $active = $pending = null;
  $plans = [];
}

/* Calcul des jours restants pour un abonnement actif */
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
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Subscription — MyGym</title>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap");
    :root{--primary:#e50914;--primary-600:#cc0812;--white:#fff;--gray:#f5f5f5;--border:#e9e9e9;--black1:#222;--black2:#999;--shadow:0 7px 25px rgba(0,0,0,.08);--green:#28a745;--amber:#ffb703}
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Ubuntu",system-ui,Segoe UI,Roboto,sans-serif}
    body{background:var(--gray)}
    .container{position:relative;width:100%}
    .navigation{position:fixed;width:300px;height:100%;background:var(--primary);box-shadow:var(--shadow)}
    .navigation ul{position:absolute;inset:0}
    .navigation a{display:flex;height:60px;align-items:center;color:#fff;text-decoration:none;padding-left:10px}
    .navigation .icon{min-width:50px;text-align:center}
    .navigation li{list-style:none} .navigation li:hover,.navigation li.active{background:var(--primary-600)}
    .main{position:absolute;left:300px;width:calc(100% - 300px);min-height:100vh;background:#fff}
    .topbar{height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid var(--border)}
    .wrap{max-width:1000px;margin:0 auto;padding:20px}
    .alert{padding:10px 12px;border-radius:8px;margin:10px 0}
    .ok{background:#e8f5e9;border:1px solid #c8e6c9} .err{background:#fdecea;border:1px solid #f5c6cb}
    .card{border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow);background:#fff}
    .price{font-size:2rem;font-weight:800;color:var(--primary)}
    .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
    .btn{display:inline-block;background:var(--primary);color:#fff;border:0;border-radius:8px;padding:.5rem .9rem;font-weight:600;text-decoration:none;cursor:pointer}
    .btn--ghost{background:#333}
    .muted{color:#666}
    @media (max-width:900px){.main{left:0;width:100%}.grid{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="container">
  <div class="navigation">
    <ul>
      <li><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li>
        <?php if ($canBook): ?>
          <a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">My Classes</span></a>
        <?php else: ?>
          <a href="subscribe.php" title="Reservations available with Plus/Pro" style="opacity:.75">
            <span class="icon"><ion-icon name="lock-closed-outline"></ion-icon></span>
            <span class="title">My Classes (locked)</span>
          </a>
        <?php endif; ?>
      </li>
      <li class="active"><a href="subscribe.php"><span class="icon"><ion-icon name="card-outline"></ion-icon></span><span class="title">My Subscription</span></a></li>
      <li><a href="profile.php"><span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span><span class="title">Profile</span></a></li>
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Sign out</span></a></li>
    </ul>
  </div>

  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div style="font-weight:700;color:#e50914">My Subscription</div>
    </div>

    <div class="wrap">
      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <?php if ($active): ?>
        <!-- Cas : abonnement actif -->
        <div class="card" style="border-left:6px solid var(--green)">
          <h2 style="margin:0 0 6px">Active Subscription — <?= htmlspecialchars($active['plan_name']) ?></h2>
          <div class="muted">Start: <?= htmlspecialchars($active['start_date'] ?: '—') ?> | End: <?= htmlspecialchars($active['end_date'] ?: '—') ?></div>
          <p style="margin-top:8px"><strong><?= (int)$daysLeft ?> day(s) left</strong></p>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
            <a class="btn" href="index.php">Back to Dashboard</a>
            <form method="post" onsubmit="return confirm('Confirm subscription cancellation?');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
              <input type="hidden" name="action" value="cancel_active">
              <input type="hidden" name="id" value="<?= (int)($active['id'] ?? 0) ?>">
              <button class="btn btn--ghost" type="submit">Cancel Subscription</button>
            </form>
          </div>
        </div>

      <?php elseif ($pending): ?>
        <!-- Cas : demande en attente -->
        <div class="card" style="border-left:6px solid var(--amber)">
          <h2 style="margin:0 0 6px">Pending Request — <?= htmlspecialchars($pending['plan_name']) ?></h2>
          <div class="muted">An administrator must validate your request.</div>
          <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
            <form method="post" onsubmit="return confirm('Cancel this request?');">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
              <input type="hidden" name="action" value="cancel_request">
              <input type="hidden" name="id" value="<?= (int)($pending['id'] ?? 0) ?>">
              <button class="btn btn--ghost" type="submit">Cancel Request</button>
            </form>
          </div>
        </div>

      <?php else: ?>
        <!-- Cas : aucun abonnement -->
        <h2 style="margin:0 0 8px">Choose a Plan</h2>
        <div class="grid">
          <?php foreach ($plans as $p): ?>
            <div class="card">
              <h3 style="margin:0 0 6px"><?= htmlspecialchars($p['name']) ?></h3>
              <div class="price">$<?= number_format($p['price_cents']/100, 0) ?><span class="muted">/mo</span></div>
              <ul style="margin:10px 0 0 18px">
                <?php foreach (explode("\n", (string)$p['features']) as $f): if(trim($f)==='') continue; ?>
                  <li><?= htmlspecialchars(ltrim($f, "- ")) ?></li>
                <?php endforeach; ?>
              </ul>
              <form method="post" style="margin-top:12px">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="choose_plan">
                <input type="hidden" name="plan_id" value="<?= (int)$p['id'] ?>">
                <button class="btn" type="submit">Choose <?= htmlspecialchars($p['name']) ?></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
</body>
</html>
