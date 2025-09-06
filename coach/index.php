<?php
declare(strict_types=1);
session_start();

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
  <!-- FR: Styles intégrés (dashboard coach) -->
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap");
    :root{
      --primary:#e50914;--primary-600:#cc0812;--white:#fff;--gray:#f5f5f5;--border:#e9e9e9;
      --black1:#222;--black2:#999;--shadow:0 7px 25px rgba(0,0,0,.08);
      --green:#28a745;--amber:#ffb703;
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Ubuntu",system-ui,Segoe UI,Roboto,sans-serif}
    body{background:var(--gray);color:var(--black1);min-height:100vh;overflow-x:hidden}
    .container{position:relative;width:100%}

    /* FR: Barre latérale rouge */
    .navigation{position:fixed;width:300px;height:100%;background:var(--primary);box-shadow:var(--shadow)}
    .navigation ul{position:absolute;inset:0}
    .navigation li{list-style:none}
    .navigation a{display:flex;height:60px;align-items:center;color:#fff;text-decoration:none;padding-left:10px}
    .navigation .icon{min-width:50px;text-align:center}
    .navigation li:hover,.navigation li.active{background:var(--primary-600)}

    /* FR: Contenu principal */
    .main{position:absolute;left:300px;width:calc(100% - 300px);min-height:100vh;background:var(--white)}
    .topbar{height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid var(--border)}
    .title-top{color:var(--primary);font-weight:700}
    .topbar-right{display:flex;align-items:center;gap:10px}
    .avatarTop{width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #eee}
    .wrap{max-width:1200px;margin:0 auto;padding:20px}

    /* FR: Cartes KPI */
    .cards{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}
    .card{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow);display:flex;justify-content:space-between;align-items:center}
    .numbers{font-weight:800;font-size:1.8rem;color:var(--primary)}
    .cardName{color:#666}
    .iconBx{font-size:2rem;color:#bbb}

    /* FR: Colonnes et panneaux */
    .cols{display:grid;grid-template-columns:2fr 1fr;gap:18px;margin-top:20px}
    .panel{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)}
    .cardHeader{display:flex;align-items:center;justify-content:space-between}
    .btn{background:var(--primary);color:#fff;border:0;border-radius:10px;padding:.45rem .9rem;font-weight:700;cursor:pointer;text-decoration:none}

    table{width:100%;border-collapse:collapse;margin-top:12px}
    thead td{font-weight:700}
    tr{border-bottom:1px solid #eee}
    td{padding:10px;vertical-align:middle}
    .badge{display:inline-block;padding:2px 10px;border-radius:999px;font-weight:700}
    .ok{background:var(--green);color:#fff}
    .muted{color:#666}

    @media (max-width:991px){
      .main{left:0;width:100%}
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
        <div class="title-top">Hello, <?= htmlspecialchars($coachName) ?></div> <!-- traduit -->
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
</body>
</html>
