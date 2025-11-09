<?php
declare(strict_types=1);
ini_set('display_errors','1');
error_reporting(E_ALL);

require_once __DIR__ . '/../backend/auth.php';
requireRole('MEMBER','ADMIN'); // FR: Accès réservé aux membres et admins (MEMBRE est normalisé en MEMBER)
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
  <!-- FR: Styles premium pour le dashboard membre -->
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap");

    /* Light Theme (Default) */
    :root{
      --primary:#8b5cf6;
      --primary-dark:#7c3aed;
      --primary-light:#a78bfa;
      --secondary:#ec4899;
      --accent:#f59e0b;
      --bg-main:#f8fafc;
      --bg-card:#ffffff;
      --text-primary:#1e293b;
      --text-secondary:#64748b;
      --border:#e2e8f0;
      --shadow:0 10px 40px rgba(139,92,246,.12);
      --shadow-lg:0 20px 60px rgba(139,92,246,.18);
      --green:#10b981;
      --red:#ef4444;
      --gradient-primary:linear-gradient(135deg, #8b5cf6 0%, #ec4899 100%);
      --gradient-card:linear-gradient(135deg, rgba(139,92,246,.05) 0%, rgba(236,72,153,.05) 100%);
    }

    /* Dark Theme */
    body.dark-mode{
      --bg-main:#0f172a;
      --bg-card:#1e293b;
      --text-primary:#f1f5f9;
      --text-secondary:#94a3b8;
      --border:#334155;
      --shadow:0 10px 40px rgba(0,0,0,.3);
      --shadow-lg:0 20px 60px rgba(0,0,0,.4);
    }

    *{box-sizing:border-box;margin:0;padding:0;font-family:"Inter",-apple-system,BlinkMacSystemFont,system-ui,sans-serif}
    body{background:var(--bg-main);color:var(--text-primary);min-height:100vh;overflow-x:hidden;transition:background .3s ease,color .3s ease}
    .container{position:relative;width:100%}

    /* Navigation Premium */
    .navigation{
      position:fixed;width:280px;height:100%;
      background:var(--gradient-primary);
      overflow:hidden;box-shadow:var(--shadow-lg);
      backdrop-filter:blur(10px);
    }
    .navigation ul{position:absolute;inset:0;padding:20px 0}
    .navigation li{list-style:none;margin:4px 12px}
    .navigation a{
      display:flex;width:100%;text-decoration:none;color:rgba(255,255,255,.9);
      align-items:center;padding:14px 16px;height:auto;border-radius:12px;
      transition:all .3s cubic-bezier(0.4,0,0.2,1);
      backdrop-filter:blur(10px);
    }
    .navigation .icon{min-width:44px;text-align:center}
    .navigation .icon ion-icon{font-size:1.6rem;color:rgba(255,255,255,.9);transition:transform .3s ease}
    .navigation .title{white-space:nowrap;font-weight:500;font-size:.95rem}
    .navigation li:hover a,.navigation li.active a{
      background:rgba(255,255,255,.2);
      color:#fff;
      transform:translateX(4px);
    }
    .navigation li:hover a .icon ion-icon{transform:scale(1.1)}

    /* Main Area */
    .main{position:absolute;left:280px;width:calc(100% - 280px);min-height:100vh;background:var(--bg-main)}
    .topbar{
      height:70px;display:flex;align-items:center;justify-content:space-between;
      padding:0 24px;border-bottom:1px solid var(--border);
      background:var(--bg-card);backdrop-filter:blur(10px);
    }
    .title-top{
      background:var(--gradient-primary);
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
      background-clip:text;
      font-weight:700;font-size:1.1rem;
    }
    .topbar-right{display:flex;align-items:center;gap:16px}
    .avatarTop{width:42px;height:42px;border-radius:50%;object-fit:cover;border:2px solid var(--primary-light);transition:transform .3s ease}
    .avatarTop:hover{transform:scale(1.05)}
    .wrap{max-width:1280px;margin:0 auto;padding:28px}

    /* Theme Toggle */
    .theme-toggle{
      width:50px;height:26px;background:var(--border);border-radius:20px;
      position:relative;cursor:pointer;transition:background .3s ease;
    }
    .theme-toggle::after{
      content:'';position:absolute;top:3px;left:3px;width:20px;height:20px;
      background:var(--bg-card);border-radius:50%;transition:transform .3s ease;
      box-shadow:0 2px 4px rgba(0,0,0,.2);
    }
    body.dark-mode .theme-toggle{background:var(--primary)}
    body.dark-mode .theme-toggle::after{transform:translateX(24px)}

    /* Premium Cards */
    .grid3{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
    .card{
      background:var(--bg-card);
      border:1px solid var(--border);
      border-radius:20px;
      padding:24px;
      box-shadow:var(--shadow);
      display:flex;
      justify-content:space-between;
      align-items:center;
      transition:all .4s cubic-bezier(0.4,0,0.2,1);
      position:relative;
      overflow:hidden;
    }
    .card::before{
      content:'';position:absolute;top:0;left:0;right:0;bottom:0;
      background:var(--gradient-card);opacity:0;transition:opacity .4s ease;
    }
    .card:hover{
      transform:translateY(-8px);
      box-shadow:var(--shadow-lg);
      border-color:var(--primary-light);
    }
    .card:hover::before{opacity:1}
    .numbers{
      font-weight:800;font-size:2.5rem;
      background:var(--gradient-primary);
      -webkit-background-clip:text;
      -webkit-text-fill-color:transparent;
      background-clip:text;
      position:relative;z-index:1;
    }
    .cardName{color:var(--text-secondary);font-weight:500;font-size:.9rem;position:relative;z-index:1}
    .iconBx{
      font-size:3rem;color:var(--primary-light);opacity:.6;
      transition:all .4s ease;position:relative;z-index:1;
    }
    .card:hover .iconBx{opacity:1;transform:scale(1.1) rotate(5deg)}

    /* Panels & Tables */
    .cols{display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-top:28px}
    .panel{
      background:var(--bg-card);border:1px solid var(--border);
      border-radius:20px;padding:24px;box-shadow:var(--shadow);
      transition:all .3s ease;
    }
    .panel:hover{box-shadow:var(--shadow-lg)}
    .cardHeader{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px}
    .cardHeader h2{background:var(--gradient-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;font-size:1.3rem}
    .btn{
      background:var(--gradient-primary);color:#fff;border:0;
      border-radius:12px;padding:.5rem 1.2rem;font-weight:600;
      cursor:pointer;text-decoration:none;
      transition:all .3s ease;box-shadow:0 4px 12px rgba(139,92,246,.3);
    }
    .btn:hover{transform:translateY(-2px);box-shadow:0 6px 20px rgba(139,92,246,.4)}

    table{width:100%;border-collapse:collapse;margin-top:16px}
    thead td{font-weight:600;color:var(--text-secondary);font-size:.85rem;text-transform:uppercase;letter-spacing:.05em;padding-bottom:12px}
    tr{border-bottom:1px solid var(--border);transition:background .2s ease}
    tr:hover{background:var(--gradient-card)}
    td{padding:14px 10px;vertical-align:middle;color:var(--text-primary)}

    .status{
      padding:4px 12px;border-radius:20px;font-weight:600;font-size:.8rem;
      color:#fff;display:inline-block;box-shadow:0 2px 8px rgba(0,0,0,.1);
    }
    .status.delivered{background:linear-gradient(135deg,#10b981,#059669)}
    .status.pending{background:linear-gradient(135deg,#ef4444,#dc2626)}

    .pill{
      display:inline-block;padding:4px 14px;border-radius:20px;font-weight:700;
      font-size:.85rem;color:#fff;
      background:linear-gradient(135deg,#10b981,#059669);
      box-shadow:0 2px 8px rgba(16,185,129,.3);
    }

    .progress{
      height:8px;border-radius:999px;background:var(--border);overflow:hidden;
      box-shadow:inset 0 2px 4px rgba(0,0,0,.1);
    }
    .progress>span{
      display:block;height:100%;
      background:var(--gradient-primary);
      border-radius:999px;
      transition:width .3s ease;
    }

    @media (max-width:991px){
      .main{left:0;width:100%}
      .navigation{width:0;opacity:0}
      .grid3{grid-template-columns:1fr}
      .cols{grid-template-columns:1fr}
    }
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
        <div class="title-top">Welcome, <?= htmlspecialchars($userName) ?></div>
        <div class="theme-toggle" onclick="toggleTheme()" title="Toggle dark mode"></div>
        <a href="profile.php" title="View my profile">
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

<script>
  // Dark Mode Toggle with localStorage persistence
  function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
  }

  // Load saved theme on page load
  document.addEventListener('DOMContentLoaded', () => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
      document.body.classList.add('dark-mode');
    }
  });
</script>
</body>
</html>
