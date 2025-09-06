<?php
declare(strict_types=1);
session_start();
ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/auth.php';
requireRole('MEMBER','MEMBRE','ADMIN'); // FR: Accès réservé aux membres (accepte 'MEMBER' et 'MEMBRE') et admins
require_once __DIR__ . '/../backend/db.php';
require_once __DIR__ . '/../backend/subscriptions.php';

// FR: Identité basique de l'utilisateur connecté (utile pour l'affichage)
$userId   = (int)($_SESSION['user']['id'] ?? 0);
$userName = $_SESSION['user']['fullname'] ?? 'Member';

// FR: Jeton CSRF pour sécuriser les formulaires POST
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

/* ===== Avatar URL =====
   FR: Détermine l'URL publique de l'avatar (BDD > fichiers locaux > placeholder) */
$rootDir      = dirname(__DIR__);
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';
$avatarUrl    = null;

try {
  $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id=:id");
  $stmt->execute([':id'=>$userId]);
  $avatarDb = (string)($stmt->fetchColumn() ?? '');
  if ($avatarDb !== '') $avatarUrl = $uploadDirWeb . '/' . basename($avatarDb) . '?t=' . time();
} catch (Throwable $e) { /* FR: On ignore, fallback fichiers */ }

if (!$avatarUrl) {
  foreach (['jpg','png','webp'] as $ext) {
    $p = $uploadDirFS . "/user_{$userId}.{$ext}";
    if (is_file($p)) { $avatarUrl = $uploadDirWeb . "/user_{$userId}.{$ext}?t=" . time(); break; }
  }
}
if (!$avatarUrl) $avatarUrl = 'https://via.placeholder.com/36x36?text=%20';

/* ===== Abonnements / Accès =====
   FR: Récupère l'abonnement actif, une éventuelle demande en attente et les droits de réservation */
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

/* ===== Jours restants + % progression =====
   FR: Calcule les jours restants et la progression temporelle de l'abonnement actif */
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

/* ===== Mes prochaines réservations (réelles) =====
   FR: Liste des prochaines sessions réservées par l'utilisateur */
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

/* ===== Nombre réel de cours réservés (BOOKED) =====
   FR: KPI simple pour l'encart carte en haut */
$bookedCount = 0;
try {
  $st = $pdo->prepare("SELECT COUNT(*) FROM reservations WHERE user_id=:u AND status='BOOKED'");
  $st->execute([':u'=>$userId]);
  $bookedCount = (int)$st->fetchColumn();
} catch (Throwable $e) {
  $bookedCount = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MyGym — Member Area</title> <!-- FR: Titre de la page (onglet) -->
  <!-- FR: Librairie d'icônes -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <!-- FR: Styles intégrés pour le tableau de bord -->
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap");
    :root{--primary:#e50914;--primary-600:#cc0812;--white:#fff;--gray:#f5f5f5;--border:#e9e9e9;--black1:#222;--black2:#999;--shadow:0 7px 25px rgba(0,0,0,.08);--green:#28a745;--red:#dc3545}
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Ubuntu",system-ui,Segoe UI,Roboto,sans-serif}
    body{background:var(--gray);color:var(--black1);min-height:100vh;overflow-x:hidden}
    .container{position:relative;width:100%}
    .navigation{position:fixed;width:300px;height:100%;background:var(--primary);overflow:hidden;box-shadow:var(--shadow)}
    .navigation ul{position:absolute;inset:0}
    .navigation li{list-style:none}
    .navigation a{display:flex;height:60px;align-items:center;color:#fff;text-decoration:none;padding-left:10px}
    .navigation .icon{min-width:50px;text-align:center}
    .navigation li:hover,.navigation li.active{background:var(--primary-600)}
    .main{position:absolute;left:300px;width:calc(100% - 300px);min-height:100vh;background:var(--white)}
    .topbar{height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid var(--border)}
    .title-top{color:var(--primary);font-weight:700}
    .topbar-right{display:flex;align-items:center;gap:10px}
    .avatarTop{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #eee}
    .wrap{max-width:1200px;margin:0 auto;padding:20px}
    .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:20px}
    .card{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:center}
    .numbers{font-weight:700;font-size:2rem;color:var(--primary)}
    .cardName{color:var(--black2)} .iconBx{font-size:2.2rem;color:var(--black2)}
    .cols{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px}
    .panel{background:var(--white);border:1px solid var(--border);border-radius:12px;padding:20px;box-shadow:var(--shadow)}
    .cardHeader{display:flex;justify-content:space-between;align-items:center}
    .btn{background:var(--primary);color:#fff;border:0;border-radius:8px;padding:.45rem .9rem;font-weight:600;cursor:pointer;text-decoration:none}
    table{width:100%;border-collapse:collapse;margin-top:10px} thead td{font-weight:700}
    tr{border-bottom:1px solid #eee} td{padding:10px;vertical-align:middle}
    .status{padding:2px 8px;border-radius:999px;font-weight:600;font-size:.85rem;color:#fff;display:inline-block}
    .status.delivered{background:var(--green)} .status.pending{background:var(--red)}
    .pill{display:inline-block;padding:2px 10px;border-radius:999px;font-weight:700;font-size:.85rem;color:#fff;background:var(--green)}
    .progress{height:6px;border-radius:999px;background:#f0f0f0;overflow:hidden}
    .progress>span{display:block;height:100%;background:var(--green)}
    @media (max-width:991px){.main{left:0;width:100%}.grid3{grid-template-columns:1fr}.cols{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="container">
  <!-- FR: Navigation latérale (branding + liens) -->
  <div class="navigation">
    <ul>
      <li style="background:transparent">
        <a href="index.php">
          <span class="icon"><ion-icon name="barbell-outline"></ion-icon></span>
          <span class="title">MyGym — Member</span> <!-- traduit -->
        </a>
      </li>
      <li class="active"><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>

      <li>
        <?php if ($canBook): ?>
          <a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">My Classes</span></a> <!-- traduit -->
        <?php else: ?>
          <a href="subscribe.php" title="Reservations available with Plus/Pro" style="opacity:.75"> <!-- title traduit -->
            <span class="icon"><ion-icon name="lock-closed-outline"></ion-icon></span>
            <span class="title">My Classes (locked)</span> <!-- traduit -->
          </a>
        <?php endif; ?>
      </li>

      <li><a href="subscribe.php"><span class="icon"><ion-icon name="card-outline"></ion-icon></span><span class="title">My Subscription</span></a></li> <!-- traduit -->
      <li><a href="profile.php"><span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span><span class="title">Profile</span></a></li> <!-- traduit -->
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Logout</span></a></li> <!-- traduit -->
    </ul>
  </div>

  <!-- FR: Zone principale (topbar + contenu) -->
  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div class="topbar-right">
        <div class="title-top">Hello, <?= htmlspecialchars($userName) ?></div> <!-- traduit -->
        <a href="profile.php" title="View my profile"> <!-- title traduit -->
          <img class="avatarTop" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar">
        </a>
      </div>
    </div>

    <div class="wrap">
      <!-- FR: Cartes KPI (réservations, abonnement, statut) -->
      <div class="grid3">
        <div class="card">
          <div>
            <div class="numbers"><?= (int)$bookedCount ?></div>
            <div class="cardName">Booked classes</div> <!-- traduit -->
          </div>
          <div class="iconBx"><ion-icon name="calendar-outline"></ion-icon></div>
        </div>
        <div class="card">
          <div>
            <?php if ($active && $daysLeft !== null): ?>
              <div class="numbers">D-<?= (int)$daysLeft ?></div> <!-- FR: Conserve le format D-XX (jour restant) -->
              <div class="cardName">Active subscription — <?= htmlspecialchars($active['plan_name']) ?></div> <!-- traduit -->
            <?php elseif ($pending): ?>
              <div class="numbers">—</div>
              <div class="cardName">Pending request — <?= htmlspecialchars($pending['plan_name']) ?></div> <!-- traduit -->
            <?php else: ?>
              <div class="numbers">—</div>
              <div class="cardName">No subscription</div> <!-- traduit -->
            <?php endif; ?>
          </div>
          <div class="iconBx"><ion-icon name="time-outline"></ion-icon></div>
        </div>
        <div class="card">
          <div>
            <div class="numbers"><?= $active ? 'Active' : ($pending ? 'Pending' : 'None') ?></div> <!-- traduit -->
            <div class="cardName">Status</div> <!-- traduit -->
          </div>
          <div class="iconBx">
            <ion-icon name="<?= $active ? 'checkmark-circle-outline' : ($pending ? 'hourglass-outline' : 'alert-circle-outline') ?>"></ion-icon>
          </div>
        </div>
      </div>

      <!-- FR: Deux colonnes - à gauche: prochaines réservations ; à droite: abonnement -->
      <div class="cols">
        <!-- FR: Mes prochaines réservations (table) -->
        <div class="panel">
          <div class="cardHeader">
            <h2>My upcoming classes</h2> <!-- traduit -->
            <?php if ($canBook): ?>
              <a href="courses.php" class="btn">Catalog</a> <!-- traduit -->
            <?php else: ?>
              <a href="subscribe.php" class="btn" title="Reservations available with Plus/Pro">Upgrade to Plus/Pro</a> <!-- traduit -->
            <?php endif; ?>
          </div>
          <table>
            <thead><tr><td>Date</td><td>Activity</td><td>Coach</td><td>Action</td></tr></thead> <!-- traduit -->
            <tbody>
              <?php if ($myNext): ?>
                <?php foreach ($myNext as $row): ?>
                  <tr>
                    <td><?= date('d/m H:i', strtotime((string)$row['start_at'])) ?></td>
                    <td><?= htmlspecialchars($row['activity']) ?></td>
                    <td><?= htmlspecialchars($row['coach']) ?></td>
                    <td>
                      <form method="post" action="courses.php" style="display:inline">
                        <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                        <input type="hidden" name="action" value="cancel">
                        <input type="hidden" name="session_id" value="<?= (int)$row['id'] ?>">
                        <button class="btn" type="submit" style="background:#333" onclick="return confirm('Cancel this booking?');">Cancel</button> <!-- traduit + confirm -->
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="4" style="color:#777;text-align:center">No upcoming bookings.</td></tr> <!-- traduit -->
              <?php endif; ?>
            </tbody>
          </table>
        </div>

        <!-- FR: Encadré 'Mon abonnement' -->
        <div class="panel">
          <div class="cardHeader"><h2>My subscription</h2></div> <!-- traduit -->

          <?php if ($active): ?>
            <p style="margin:6px 0">
              Plan: <strong><?= htmlspecialchars($active['plan_name']) ?></strong><br>
              Start: <?= htmlspecialchars($active['start_date'] ?: '—') ?> |
              End: <?= htmlspecialchars($active['end_date'] ?: '—') ?>
            </p>
            <div style="display:flex;align-items:center;gap:10px;margin:8px 0">
              <?php if ($pctLeft !== null): ?>
                <div class="progress" style="flex:1 1 200px"><span style="width: <?= (int)$pctLeft ?>%"></span></div>
              <?php endif; ?>
              <span class="pill"><?= (int)$daysLeft ?> day(s)</span> <!-- traduit -->
            </div>
            <div style="display:flex;gap:10px;margin-top:8px">
              <a class="btn" href="subscribe.php">Manage</a> <!-- traduit -->
              <form method="post" action="subscribe.php" onsubmit="return confirm('Cancel the active subscription?');"> <!-- confirm traduit -->
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="cancel_active">
                <input type="hidden" name="id" value="<?= (int)($active['id'] ?? 0) ?>">
                <button class="btn" type="submit" style="background:#333">Cancel subscription</button> <!-- traduit -->
              </form>
            </div>

          <?php elseif ($pending): ?>
            <p style="margin:6px 0"><strong>Pending request</strong> — <?= htmlspecialchars($pending['plan_name']) ?></p> <!-- traduit -->
            <div style="display:flex;gap:10px;margin-top:8px">
              <a class="btn" href="subscribe.php">View</a> <!-- traduit -->
              <form method="post" action="subscribe.php" onsubmit="return confirm('Cancel this request?');"> <!-- confirm traduit -->
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="action" value="cancel_request">
                <input type="hidden" name="id" value="<?= (int)($pending['id'] ?? 0) ?>">
                <button class="btn" type="submit" style="background:#333">Cancel request</button> <!-- traduit -->
              </form>
            </div>

          <?php else: ?>
            <p style="margin:6px 0">You don’t have an active subscription.</p><br> <!-- traduit -->
            <a class="btn" href="subscribe.php">Choose a plan</a> <!-- traduit -->
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
