<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('COACH','ADMIN'); // FR: Admin autorisé (debug)
require_once __DIR__ . '/../backend/db.php';

/* ---------- Contexte utilisateur ----------
   FR: Récupère l'identité du coach connecté pour l'affichage/topbar */
$coachId   = (int)($_SESSION['user']['id'] ?? 0);
$coachName = $_SESSION['user']['fullname'] ?? 'Coach';
if ($coachId <= 0) { http_response_code(401); exit('Access denied.'); } // FR: message affiché -> traduit

/* ---------- Avatar (BDD -> fallback fichiers) ----------
   FR: Construit l’URL d’avatar (BDD puis fichiers), sinon placeholder */
$rootDir      = dirname(__DIR__);                 // .../MyGym
$uploadDirFS  = $rootDir . '/uploads/avatars';
$uploadDirWeb = '/MyGym/uploads/avatars';
$avatarUrl    = null;

try {
  $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id=:id");
  $stmt->execute([':id'=>$coachId]);
  $avatarDb = (string)($stmt->fetchColumn() ?: '');
  if ($avatarDb !== '') { $avatarUrl = $uploadDirWeb . '/' . basename($avatarDb) . '?t=' . time(); }
} catch (Throwable $e) { /* FR: on ignore et on tente les fichiers */ }
if (!$avatarUrl) {
  foreach (['jpg','png','webp'] as $ext) {
    $p = $uploadDirFS . "/user_{$coachId}.{$ext}";
    if (is_file($p)) { $avatarUrl = $uploadDirWeb . "/user_{$coachId}.{$ext}?t=" . time(); break; }
  }
}
if (!$avatarUrl) { $avatarUrl = 'https://via.placeholder.com/36x36?text=%20'; }

/* ---------- Constantes “métier” ----------
   FR: Quota de membres suivis par coach (affiché en KPI) */
const COACH_MAX_MEMBERS = 5; // quota par coach

/* ---------- 1) Membres assignés à ce coach ----------
   FR: Liste brute + compteur pour KPI */
$assignedMembers = [];
$assignedCount   = 0;
try {
  $assignedMembers = $pdo->prepare("
    SELECT u.id, u.fullname, u.email
    FROM coach_members cm
    JOIN users u ON u.id = cm.member_id
    WHERE cm.coach_id = :c
    ORDER BY u.fullname ASC
  ");
  $assignedMembers->execute([':c'=>$coachId]);
  $assignedMembers = $assignedMembers->fetchAll(PDO::FETCH_ASSOC);
  $assignedCount   = count($assignedMembers);
} catch (Throwable $e) {
  $assignedMembers = [];
  $assignedCount   = 0; // FR: table absente => 0
}

/* ---------- 2) Membres abonnés (assignés + abonnement actif) ----------
   FR: Calcule combien de membres assignés ont un abonnement actif */
$subscribedAssignedCount = 0;
$membersWithStatus = []; // FR: pour le tableau latéral
try {
  // FR: ACTIVE + approuvé (approved_by non NULL) + dates valides
  $stmt = $pdo->prepare("
    SELECT u.id, u.fullname,
           EXISTS(
             SELECT 1 FROM subscriptions s
             WHERE s.user_id = u.id
               AND s.status = 'ACTIVE'
               AND s.approved_by IS NOT NULL
               AND (s.end_date IS NULL OR s.end_date >= CURRENT_DATE())
           ) AS has_active_sub
    FROM coach_members cm
    JOIN users u ON u.id = cm.member_id
    WHERE cm.coach_id = :c
    ORDER BY u.fullname ASC
  ");
  $stmt->execute([':c'=>$coachId]);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  foreach ($rows as $r) {
    $ok = (int)$r['has_active_sub'] === 1;
    $membersWithStatus[] = ['id'=>$r['id'], 'fullname'=>$r['fullname'], 'active'=>$ok];
    if ($ok) $subscribedAssignedCount++;
  }
} catch (Throwable $e) {
  // FR: fallback si table subscriptions/coach_members manquante
  foreach ($assignedMembers as $m) {
    $membersWithStatus[] = ['id'=>$m['id'], 'fullname'=>$m['fullname'], 'active'=>false];
  }
  $subscribedAssignedCount = 0;
}

/* ---------- 3) Places restantes pour ce coach ----------
   FR: KPI = (quota - déjà assignés), borné à 0 */
$spotsLeft = max(0, COACH_MAX_MEMBERS - $assignedCount); // X/5

/* ---------- 4) Prochain cours (date du prochain créneau) ----------
   FR: Récupère la première session future du coach */
$nextSession = null;
try {
  $stmt = $pdo->prepare("
    SELECT s.id, s.start_at, s.end_at, s.capacity,
           a.name AS activity,
           (SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id) AS booked
    FROM sessions s
    LEFT JOIN activities a ON a.id = s.activity_id
    WHERE s.coach_id = :c
      AND s.start_at >= NOW()
    ORDER BY s.start_at ASC
    LIMIT 1
  ");
  $stmt->execute([':c'=>$coachId]);
  $nextSession = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
} catch (Throwable $e) { $nextSession = null; }

$nextLabel = '—'; // FR: libellé de la date affichée
if ($nextSession && !empty($nextSession['start_at'])) {
  try {
    $dt = new DateTime($nextSession['start_at']);
    $nextLabel = $dt->format('d/m H:i');
  } catch (Throwable $e) { $nextLabel = '—'; }
}

/* ---------- 5) Tableau “Prochains cours” (les 5 prochains) ----------
   FR: Liste courte pour le tableau des prochaines sessions */
$upcoming = [];
try {
  $stmt = $pdo->prepare("
    SELECT s.id, s.start_at, s.end_at, s.capacity,
           a.name AS activity,
           u.fullname AS coach_name,
           (SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id) AS booked
    FROM sessions s
    LEFT JOIN activities a ON a.id = s.activity_id
    LEFT JOIN users u ON u.id = s.coach_id
    WHERE s.coach_id = :c
      AND s.start_at >= NOW()
    ORDER BY s.start_at ASC
    LIMIT 5
  ");
  $stmt->execute([':c'=>$coachId]);
  $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $upcoming = [];
}

/* ---------- Helpers UI ----------
   FR: Construit un libellé HH:MM → HH:MM à partir des timestamps */
function period_label(?string $start, ?string $end): string {
  if (!$start) return '—';
  try {
    $s = new DateTime($start);
    if ($end) {
      $e = new DateTime($end);
      return $s->format('H:i') . ' → ' . $e->format('H:i');
    }
    return $s->format('H:i');
  } catch (Throwable $e) {
    return '—';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MyGym — Coach</title> <!-- FR: Titre de l’onglet -->
  <!-- FR: Librairie d’icônes -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <!-- FR: Styles intégrés (dashboard coach premium) -->
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
    .cards{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}
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
    .cardHeader{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
    .cardHeader h2{background:var(--gradient-primary);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
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

    .badge{
      padding:4px 12px;border-radius:20px;font-weight:600;font-size:.8rem;
      display:inline-block;box-shadow:0 2px 8px rgba(0,0,0,.1);
    }
    .badge.ok{background:linear-gradient(135deg,#10b981,#059669);color:#fff}
    .muted{color:var(--text-secondary)}

    @media (max-width:991px){
      .main{left:0;width:100%}
      .navigation{width:0;opacity:0}
      .cards{grid-template-columns:1fr}
      .cols{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
<div class="container">
  <!-- FR: Navigation latérale (libellés traduits) -->
  <div class="navigation">
    <ul>
      <li style="background:transparent">
        <a href="index.php"><span class="icon"><ion-icon name="barbell-outline"></ion-icon></span><span class="title">MyGym — Coach</span></a>
      </li>
      <li class="active"><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li><a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">My Classes</span></a></li> <!-- traduit -->
      <li><a href="members.php"><span class="icon"><ion-icon name="people-outline"></ion-icon></span><span class="title">My Members</span></a></li> <!-- traduit -->
      <li><a href="profile.php"><span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span><span class="title">Profile</span></a></li> <!-- traduit -->
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Logout</span></a></li> <!-- traduit -->
    </ul>
  </div>

  <!-- FR: Barre supérieure + avatar -->
  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div class="topbar-right">
        <div class="title-top">Welcome, <?= htmlspecialchars($coachName) ?></div>
        <div class="theme-toggle" onclick="toggleTheme()" title="Toggle dark mode"></div>
        <a href="profile.php"><img class="avatarTop" src="<?= htmlspecialchars($avatarUrl) ?>" alt="Avatar"></a>
      </div>
    </div>

    <div class="wrap">
      <!-- FR: Cartes KPI -->
      <div class="cards">
        <!-- 1) Prochain cours -->
        <div class="card">
          <div>
            <div class="numbers"><?= htmlspecialchars($nextLabel) ?></div>
            <div class="cardName">Next class<?= $nextSession && $nextSession['activity'] ? ' — '.htmlspecialchars($nextSession['activity']) : '' ?></div> <!-- traduit -->
          </div>
          <div class="iconBx"><ion-icon name="calendar-outline"></ion-icon></div>
        </div>

        <!-- 2) Places coach (restantes / max) -->
        <div class="card">
          <div>
            <div class="numbers"><?= (int)$spotsLeft ?>/<?= (int)COACH_MAX_MEMBERS ?></div>
            <div class="cardName">Coach slots (assigned members)</div> <!-- traduit -->
          </div>
          <div class="iconBx"><ion-icon name="analytics-outline"></ion-icon></div>
        </div>

        <!-- 3) Membres abonnés -->
        <div class="card">
          <div>
            <div class="numbers"><?= (int)$subscribedAssignedCount ?></div>
            <div class="cardName">Subscribed members</div> <!-- traduit -->
          </div>
          <div class="iconBx"><ion-icon name="people-outline"></ion-icon></div>
        </div>
      </div>

      <!-- FR: Deux colonnes (prochains cours / membres abonnés) -->
      <div class="cols">
        <!-- Prochains cours -->
        <div class="panel">
          <div class="cardHeader">
            <h2>Upcoming classes</h2> <!-- traduit -->
            <a href="courses.php" class="btn">Schedule</a> <!-- traduit -->
          </div>
          <table>
            <thead>
              <tr>
                <td>Date</td>
                <td>Period</td>   <!-- traduit -->
                <td>Type</td>
                <td>Members</td>  <!-- traduit -->
                <td>Coach</td>
              </tr>
            </thead>
            <tbody>
              <?php if (!$upcoming): ?>
                <tr><td colspan="5" class="muted" style="text-align:center">No classes scheduled.</td></tr> <!-- traduit -->
              <?php else: foreach ($upcoming as $s): ?>
                <?php
                  $dateTxt = '—';
                  try { $dateTxt = (new DateTime((string)$s['start_at']))->format('d/m H:i'); } catch(Throwable $e){}
                  $period  = period_label($s['start_at'] ?? null, $s['end_at'] ?? null);
                  $booked  = (int)($s['booked'] ?? 0);
                  $cap     = (int)($s['capacity'] ?? 0);
                ?>
                <tr>
                  <td><?= htmlspecialchars($dateTxt) ?></td>
                  <td><?= htmlspecialchars($period) ?></td>
                  <td><?= htmlspecialchars($s['activity'] ?? '—') ?></td>
                  <td><?= $booked ?>/<?= $cap ?></td>
                  <td><?= htmlspecialchars($s['coach_name'] ?? $coachName) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>

        <!-- Mes membres abonnés -->
        <div class="panel">
          <div class="cardHeader">
            <h2>My subscribed members</h2> <!-- traduit -->
            <a href="members.php" class="btn">View all</a> <!-- traduit -->
          </div>
          <table>
            <thead><tr><td>Member</td><td>Status</td></tr></thead> <!-- en -->
            <tbody>
              <?php if (!$membersWithStatus): ?>
                <tr><td colspan="2" class="muted" style="text-align:center">No assigned member.</td></tr> <!-- traduit -->
              <?php else: foreach ($membersWithStatus as $m): ?>
                <tr>
                  <td><?= htmlspecialchars($m['fullname']) ?></td>
                  <td>
                    <?php if ($m['active']): ?>
                      <span class="badge ok">Active</span> <!-- traduit -->
                    <?php else: ?>
                      <span class="badge" style="background:#ddd;color:#111">Inactive</span> <!-- traduit -->
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
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
