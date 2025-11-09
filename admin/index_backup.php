<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('ADMIN'); // FR: accès réservé à l'ADMIN
require_once __DIR__ . '/../backend/db.php';

/* ---------- Utils ----------
   FR: Petites helpers d'affichage (normalisation rôle, format €) */
function role_to_ui(string $r): string {
  $r = strtoupper(trim($r));
  return $r === 'MEMBRE' ? 'MEMBER' : (in_array($r, ['ADMIN','COACH','MEMBER'], true) ? $r : 'MEMBER');
}
function euro_from_cents(int $c): string {
  return '€ '.number_format($c / 100, 0, ',', ' ');
}

/* ---------- Contexte utilisateur + avatar ----------
   FR: Si ADMIN, on affiche un en-tête générique (logo admin) */
$user      = $_SESSION['user'] ?? [];
$userRole  = strtoupper($user['role'] ?? '');

$rootDir      = dirname(__DIR__);
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';

if ($userRole === 'ADMIN') {
  // FR: En espace admin : on n'affiche PAS le profil perso, mais un header générique
  $userId   = (int)($user['id'] ?? 0);
  $userName = 'Administrator'; // (EN) était "Administrateur"
  // FR: Image générique admin (place ton fichier à /MyGym/assets/admin.png)
  $adminLogoFS  = $rootDir . '/assets/admin.png';
  $adminLogoWeb = '/MyGym/assets/admin.png';
  if (is_file($adminLogoFS)) {
    $avatarUrl = $adminLogoWeb . '?t=' . time();
  } else {
    // FR: fallback
    $avatarUrl = 'https://via.placeholder.com/36x36?text=%20';
  }
} else {
  // FR: Comportement normal pour non-admins
  $userId   = (int)($user['id'] ?? 0);
  $userName = $user['fullname'] ?? 'User'; // (EN) était "Utilisateur"
  $avatarUrl = null;

  try {
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id=:id");
    $stmt->execute([':id'=>$userId]);
    $avatarDb = (string)($stmt->fetchColumn() ?? '');
    if ($avatarDb !== '') $avatarUrl = $uploadDirWeb . '/' . basename($avatarDb) . '?t=' . time();
  } catch (Throwable $e) { /* FR: ignorer si pas de colonne avatar */ }

  if (!$avatarUrl) {
    foreach (['jpg','png','webp'] as $ext) {
      $p = $uploadDirFS . "/user_{$userId}.{$ext}";
      if (is_file($p)) { $avatarUrl = $uploadDirWeb . "/user_{$userId}.{$ext}?t=" . time(); break; }
    }
  }
  if (!$avatarUrl) $avatarUrl = 'https://via.placeholder.com/36x36?text=%20';
}

/* ---------- Statistiques utilisateurs ----------
   FR: Compte ADMIN/COACH/MEMBER et membres actifs */
$stmt = $pdo->query("SELECT id, role, is_active FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stats = ['ADMIN'=>0, 'COACH'=>0, 'MEMBER'=>0, 'MEMBER_ACTIVE'=>0];
foreach ($users as $u) {
  $r = role_to_ui((string)($u['role'] ?? ''));
  if (isset($stats[$r])) $stats[$r]++;
  if ($r === 'MEMBER' && (int)($u['is_active'] ?? 0) === 1) $stats['MEMBER_ACTIVE']++;
}

/* ---------- Abonnements récents ----------
   FR: 3 derniers abonnements non PENDING */
$recentSubs = $pdo->query("
  SELECT s.id, s.status, s.start_date, s.created_at,
         u.fullname,
         p.name AS plan_name
  FROM subscriptions s
  JOIN users u ON u.id = s.user_id
  JOIN plans p ON p.id = s.plan_id
  WHERE s.status <> 'PENDING'
  ORDER BY s.id DESC
  LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

/* ---------- Revenus (mois courant) ----------
   FR: Somme des prix plan approuvés sur la période du mois */
$firstDay = (new DateTime('first day of this month'))->format('Y-m-d');
$lastDay  = (new DateTime('last day of this month'))->format('Y-m-d');

$stmRev = $pdo->prepare("
  SELECT COALESCE(SUM(p.price_cents),0) AS cents
  FROM subscriptions s
  JOIN plans p ON p.id = s.plan_id
  WHERE s.approved_by IS NOT NULL
    AND s.status IN ('ACTIVE','CANCELLED','EXPIRED')
    AND DATE(COALESCE(s.start_date, s.created_at)) BETWEEN :d1 AND :d2
");
$stmRev->execute([':d1'=>$firstDay, ':d2'=>$lastDay]);
$monthRevenueCents = (int)$stmRev->fetchColumn();

/* ---------- Historique revenus (12 derniers mois) ----------
   FR: Total par mois, libellés utilisés dans la liste de droite */
$hist = $pdo->query("
  SELECT DATE_FORMAT(DATE(COALESCE(s.start_date, s.created_at)),'%Y-%m') AS ym,
         COALESCE(SUM(p.price_cents),0) AS cents
  FROM subscriptions s
  JOIN plans p ON p.id = s.plan_id
  WHERE s.approved_by IS NOT NULL
    AND s.status IN ('ACTIVE','CANCELLED','EXPIRED')
  GROUP BY ym
  ORDER BY ym DESC
  LIMIT 12
")->fetchAll(PDO::FETCH_ASSOC);

$notes = [];
foreach ($hist as $row) {
  [$y, $m] = explode('-', $row['ym']);
  // (EN) était "— total revenu"
  $notes[] = sprintf('%s/%s — total revenue %s', (int)$m, (int)$y, euro_from_cents((int)$row['cents']));
}

/* ---------- (Optionnel) Cours semaine courante ----------
   FR: Nombre de sessions créées sur la semaine en cours */
$weeklyCourses = 0;
try {
  $weekStart = new DateTime('monday this week 00:00:00');
  $weekEnd   = new DateTime('sunday this week 23:59:59');
  $stmWeek = $pdo->prepare("SELECT COUNT(*) FROM sessions WHERE start_at BETWEEN :d1 AND :d2");
  $stmWeek->execute([':d1'=>$weekStart->format('Y-m-d H:i:s'), ':d2'=>$weekEnd->format('Y-m-d H:i:s')]);
  $weeklyCourses = (int)$stmWeek->fetchColumn();
} catch (Throwable $e) {
  $weeklyCourses = 0;
}
?>
<!DOCTYPE html>
<html lang="fr"><!-- FR: attribut non visible conservé -->
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MyGym — Admin</title>
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
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
      --amber:#f59e0b;
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
      overflow:hidden;
      box-shadow:var(--shadow-lg);
      backdrop-filter:blur(10px);
    }
    .navigation ul{position:absolute;inset:0;padding:20px 0}
    .navigation ul li{list-style:none;margin:4px 12px}
    .navigation ul li a{
      display:flex;width:100%;text-decoration:none;color:rgba(255,255,255,.9);
      align-items:center;padding:14px 16px;height:auto;border-radius:12px;
      transition:all .3s cubic-bezier(0.4,0,0.2,1);
      backdrop-filter:blur(10px);
    }
    .navigation ul li a .icon{min-width:44px;text-align:center}
    .navigation ul li a .icon ion-icon{font-size:1.6rem;color:rgba(255,255,255,.9);transition:transform .3s ease}
    .navigation ul li a .title{white-space:nowrap;font-weight:500;font-size:.95rem}
    .navigation ul li:hover a,.navigation ul li.active a{
      background:rgba(255,255,255,.2);
      color:#fff;
      transform:translateX(4px);
    }
    .navigation ul li:hover a .icon ion-icon{transform:scale(1.1)}

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
    }
    body.dark-mode .theme-toggle{background:var(--primary)}
    body.dark-mode .theme-toggle::after{transform:translateX(24px)}

    /* Premium Cards */
    .grid4{display:grid;grid-template-columns:repeat(4,1fr);gap:24px}
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
      content:'';
      position:absolute;
      top:0;left:0;right:0;bottom:0;
      background:var(--gradient-card);
      opacity:0;
      transition:opacity .4s ease;
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
      font-size:3rem;
      color:var(--primary-light);
      opacity:.6;
      transition:all .4s ease;
      position:relative;z-index:1;
    }
    .card:hover .iconBx{opacity:1;transform:scale(1.1) rotate(5deg)}

    /* Panels */
    .cols{display:grid;grid-template-columns:2fr 1fr;gap:24px;margin-top:28px}
    .panel{
      background:var(--bg-card);
      border:1px solid var(--border);
      border-radius:20px;
      padding:24px;
      box-shadow:var(--shadow);
      transition:all .3s ease;
    }
    .panel:hover{box-shadow:var(--shadow-lg)}

    /* Tables */
    table{width:100%;border-collapse:collapse;margin-top:16px}
    thead td{font-weight:600;color:var(--text-secondary);font-size:.85rem;text-transform:uppercase;letter-spacing:.05em}
    tr{border-bottom:1px solid var(--border);transition:background .2s ease}
    tr:hover{background:var(--gradient-card)}
    td{padding:14px 10px;color:var(--text-primary)}

    /* Status Badges */
    .status{
      padding:4px 12px;border-radius:20px;font-weight:600;font-size:.8rem;
      color:#fff;display:inline-block;
      box-shadow:0 2px 8px rgba(0,0,0,.1);
    }
    .status.ACTIVE{background:linear-gradient(135deg,#10b981,#059669)}
    .status.REJECTED{background:linear-gradient(135deg,#6b7280,#4b5563)}
    .status.CANCELLED{background:linear-gradient(135deg,#374151,#1f2937)}
    .status.EXPIRED{background:linear-gradient(135deg,#ef4444,#dc2626)}
    .status.PENDING{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff}

    @media (max-width:991px){
      .main{left:0;width:100%}
      .navigation{width:0;opacity:0}
      .grid4{grid-template-columns:repeat(2,1fr)}
      .cols{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
<div class="container">
  <!-- FR: Barre latérale (textes en anglais uniquement) -->
  <div class="navigation">
    <ul>
      <li style="background:transparent">
        <a href="index.php">
          <span class="icon"><ion-icon name="barbell-outline"></ion-icon></span>
          <span class="title">MyGym — Admin</span>
        </a>
      </li>
      <li><a href="index.php" class="active"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li><a href="users.php"><span class="icon"><ion-icon name="people-outline"></ion-icon></span><span class="title">Users</span></a></li> <!-- (EN) Utilisateurs -->
      <li><a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">Activities & Classes</span></a></li> <!-- (EN) Activités & Cours -->
      <li><a href="subscriptions.php"><span class="icon"><ion-icon name="card-outline"></ion-icon></span><span class="title">Subscriptions & Payments</span></a></li> <!-- (EN) Abonnements & Paiements -->
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Logout</span></a></li> <!-- (EN) Déconnexion -->
    </ul>
  </div>

  <!-- FR: Zone principale -->
  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div class="topbar-right">
        <div class="title-top">Welcome, <?= htmlspecialchars($userName) ?></div>
        <div class="theme-toggle" onclick="toggleTheme()" title="Toggle dark mode"></div>
      </div>
    </div>

    <div class="wrap">
      <!-- FR: Cartes KPI -->
      <div class="grid4">
        <div class="card">
          <div>
            <div class="numbers"><?= (int)$stats['MEMBER'] ?></div>
            <div class="cardName">Members</div> <!-- (EN) Membres -->
          </div>
          <div class="iconBx"><ion-icon name="people-outline"></ion-icon></div>
        </div>
        <div class="card">
          <div>
            <div class="numbers"><?= (int)$stats['COACH'] ?></div>
            <div class="cardName">Coaches</div> <!-- (EN) Coachs -->
          </div>
          <div class="iconBx"><ion-icon name="person-outline"></ion-icon></div>
        </div>
        <div class="card">
          <div>
            <div class="numbers"><?= (int)$weeklyCourses ?></div>
            <div class="cardName">Classes / week</div> <!-- (EN) Cours / semaine -->
          </div>
          <div class="iconBx"><ion-icon name="calendar-outline"></ion-icon></div>
        </div>
        <div class="card">
          <div>
            <div class="numbers"><?= euro_from_cents($monthRevenueCents) ?></div>
            <div class="cardName">Revenue (month)</div> <!-- (EN) Revenus (mois) -->
          </div>
          <div class="iconBx"><ion-icon name="cash-outline"></ion-icon></div>
        </div>
      </div>

      <!-- FR: Deux colonnes -->
      <div class="cols">
        <!-- FR: Abonnements récents -->
        <div class="panel">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <h2 style="color:var(--primary)">Recent subscriptions</h2> <!-- (EN) Abonnements récents -->
            <div style="color:#666;font-size:.9rem">
              Revenue period: <?= htmlspecialchars((new DateTime($firstDay))->format('d/m/Y')) ?>
              → <?= htmlspecialchars((new DateTime($lastDay))->format('d/m/Y')) ?>
            </div>
          </div>
          <table>
            <thead><tr><td>Member</td><td>Plan</td><td>Start</td><td>Status</td></tr></thead> <!-- (EN) Membre/Type/Début/Statut -->
            <tbody>
              <?php if (!$recentSubs): ?>
                <tr><td colspan="4" style="color:#666;text-align:center">No recent subscriptions.</td></tr> <!-- (EN) Aucun abonnement récent -->
              <?php else: foreach ($recentSubs as $r): ?>
                <tr>
                  <td><?= htmlspecialchars($r['fullname']) ?></td>
                  <td><?= htmlspecialchars($r['plan_name']) ?></td>
                  <td><?= htmlspecialchars($r['start_date'] ?: ($r['created_at'] ?? '—')) ?></td>
                  <td><span class="status <?= htmlspecialchars($r['status']) ?>"><?= htmlspecialchars($r['status']) ?></span></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <!-- FR: Revenus mensuels (liste) -->
        <div class="panel">
          <h2 style="color:var(--primary)">Monthly revenue</h2> <!-- (EN) Revenus par mois -->
          <?php if (!$notes): ?>
            <p style="color:#666">No data yet.</p> <!-- (EN) Pas encore de données -->
          <?php else: ?>
            <ul style="margin:10px 0 0 18px;line-height:1.8">
              <?php foreach ($notes as $line): ?>
                <li><?= htmlspecialchars($line) ?></li>
              <?php endforeach; ?>
            </ul>
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
