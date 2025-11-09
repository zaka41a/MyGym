<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('COACH','ADMIN');                         // FR: Accès coach et admin (contrôle de rôle)
require_once __DIR__ . '/../backend/db.php';

$coach = $_SESSION['user'] ?? null;
$coachId = (int)($coach['id'] ?? 0);
$coachName = $coach['fullname'] ?? 'Coach';
if ($coachId <= 0) { http_response_code(401); exit('Access denied.'); } // traduit

/* ---------- CSRF ----------
   FR: Génère un token CSRF pour sécuriser les formulaires POST */
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

/* ---------- Messages ----------
   FR: Récupère messages de succès/erreur passés en query string */
$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

/* ---------- Helpers ----------
   FR: Redirection pratique avec message (ok/err) */
function redirect_with(string $param, string $msg): never {
  header('Location: '.$_SERVER['PHP_SELF'].'?'.$param.'='.urlencode($msg));
  exit;
}

/* ---------- POST actions ----------
   FR: Traite les actions de formulaire: assigner / retirer un membre */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF.');
    }
    $action = (string)($_POST['action'] ?? '');

    // FR: Assigner un membre à CE coach (depuis le tableau des éligibles)
    if ($action === 'assign') {
      $memberId = (int)($_POST['member_id'] ?? 0);
      if ($memberId <= 0) throw new RuntimeException('Invalid member.');

      // 1) FR: Vérifier que c’est bien un MEMBER/MEMBRE
      $st = $pdo->prepare("SELECT id FROM users WHERE id=:id AND (role='MEMBER' OR role='MEMBRE')");
      $st->execute([':id'=>$memberId]);
      if (!$st->fetchColumn()) throw new RuntimeException('User not eligible.');

      // 2) FR: Vérifier abonnement actif validé ET de type PLUS/PRO
      $chk = $pdo->prepare("
        SELECT 1
          FROM subscriptions s
          JOIN plans p ON p.id = s.plan_id
         WHERE s.user_id = :uid
           AND s.status = 'ACTIVE'
           AND s.approved_by IS NOT NULL
           AND (s.end_date IS NULL OR s.end_date >= CURRENT_DATE())
           AND UPPER(p.code) IN ('PLUS','PRO')
         LIMIT 1
      ");
      $chk->execute([':uid'=>$memberId]);
      if (!$chk->fetchColumn()) {
        throw new RuntimeException("The member does not have an eligible active subscription (PLUS/PRO).");
      }

      // 3) FR: Vérifier qu’il n’a PAS déjà un coach
      $ex = $pdo->prepare("SELECT 1 FROM coach_members WHERE member_id=:m LIMIT 1");
      $ex->execute([':m'=>$memberId]);
      if ($ex->fetchColumn()) throw new RuntimeException("This member is already assigned to a coach.");

      // 4) FR: Assigner
      $ins = $pdo->prepare("INSERT INTO coach_members (coach_id, member_id, assigned_at) VALUES (:c,:m,NOW())");
      $ins->execute([':c'=>$coachId, ':m'=>$memberId]);

      redirect_with('ok', 'Member successfully assigned.');
    }

    // FR: Retirer un membre du coach
    if ($action === 'unassign') {
      $memberId = (int)($_POST['member_id'] ?? 0);
      if ($memberId <= 0) throw new RuntimeException('Invalid member.');

      $del = $pdo->prepare("DELETE FROM coach_members WHERE coach_id=:c AND member_id=:m");
      $del->execute([':c'=>$coachId, ':m'=>$memberId]);

      if ($del->rowCount() === 0) throw new RuntimeException("This member was not assigned to you.");
      redirect_with('ok', 'Member removed.');
    }

    throw new RuntimeException('Unknown action.');
  } catch (Throwable $e) {
    redirect_with('err', $e->getMessage());
  }
}

/* ---------- Données ----------
   FR: Prépare les données pour l’affichage (KPIs, listes) */

// FR: Nombre de membres déjà assignés à ce coach
$assignedCount = (int)$pdo->query("SELECT COUNT(*) FROM coach_members WHERE coach_id={$coachId}")->fetchColumn();

// FR: Taille du “pool” des membres éligibles (actifs validés PLUS/PRO) SANS coach
$poolCount = 0;
try {
  $poolCount = (int)$pdo->query("
    SELECT COUNT(*) FROM (
      SELECT u.id
        FROM users u
        JOIN subscriptions s ON s.user_id = u.id
        JOIN plans p        ON p.id = s.plan_id
       WHERE (u.role='MEMBER' OR u.role='MEMBRE')
         AND s.status = 'ACTIVE'
         AND s.approved_by IS NOT NULL
         AND (s.end_date IS NULL OR s.end_date >= CURRENT_DATE())
         AND UPPER(p.code) IN ('PLUS','PRO')
         AND NOT EXISTS (SELECT 1 FROM coach_members cm WHERE cm.member_id = u.id)
       GROUP BY u.id
    ) t
  ")->fetchColumn();
} catch (Throwable $e) { $poolCount = 0; }

// FR: Liste des membres éligibles SANS coach (limite 50)
$eligible = [];
try {
  $st = $pdo->query("
    SELECT u.id, u.fullname, u.username, u.email
      FROM users u
      JOIN subscriptions s ON s.user_id = u.id
      JOIN plans p        ON p.id = s.plan_id
     WHERE (u.role='MEMBER' OR u.role='MEMBRE')
       AND s.status = 'ACTIVE'
       AND s.approved_by IS NOT NULL
       AND (s.end_date IS NULL OR s.end_date >= CURRENT_DATE())
       AND UPPER(p.code) IN ('PLUS','PRO')
       AND NOT EXISTS (SELECT 1 FROM coach_members cm WHERE cm.member_id = u.id)
  GROUP BY u.id, u.fullname, u.username, u.email
  ORDER BY u.fullname ASC
     LIMIT 50
  ");
  $eligible = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $eligible = [];
}

// FR: Mes membres (assignés à ce coach). On garde l’historique (même s’ils ne sont plus actifs).
$myMembers = [];
try {
  $st = $pdo->prepare("
    SELECT u.id, u.fullname, u.username, u.email
      FROM coach_members cm
      JOIN users u ON u.id = cm.member_id
     WHERE cm.coach_id = :c
  ORDER BY u.fullname ASC
  ");
  $st->execute([':c'=>$coachId]);
  $myMembers = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  $myMembers = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>My Members — Coach</title> <!-- FR: Titre de la page -->
  <!-- FR: Librairie d’icônes -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <!-- FR: Styles intégrés pour la page coach/membres -->
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap");
    :root{--primary:#e50914;--primary-600:#cc0812;--white:#fff;--gray:#f5f5f5;--border:#e9e9e9;--black1:#222;--black2:#999;--shadow:0 7px 25px rgba(0,0,0,.08)}
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Ubuntu",system-ui,Segoe UI,Roboto,sans-serif}
    body{background:var(--gray)}
    .container{position:relative;width:100%}
    .navigation{position:fixed;width:300px;height:100%;background:var(--primary);box-shadow:var(--shadow)}
    .navigation ul{position:absolute;inset:0}
    .navigation li{list-style:none}
    .navigation a{display:flex;height:60px;align-items:center;color:#fff;text-decoration:none;padding-left:10px}
    .navigation .icon{min-width:50px;text-align:center}
    .navigation li:hover,.navigation li.active{background:var(--primary-600)}
    .main{position:absolute;left:300px;width:calc(100% - 300px);min-height:100vh;background:#fff}
    .topbar{height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid var(--border)}
    .wrap{max-width:1100px;margin:0 auto;padding:20px}
    .panel{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow);margin-top:18px}
    .muted{color:#666}
    .alert{padding:10px;border-radius:8px;margin:10px 0}
    .ok{background:#e8f5e9;border:1px solid #c8e6c9}
    .err{background:#fdecea;border:1px solid #f5c6cb}
    table{width:100%;border-collapse:collapse}
    thead td{font-weight:700}
    tr{border-bottom:1px solid #eee}
    td{padding:10px;vertical-align:middle}
    .btn{background:var(--primary);color:#fff;border:0;border-radius:8px;padding:.45rem .9rem;font-weight:700;cursor:pointer;text-decoration:none}
    .btn--ghost{background:#333}
    .kpis{display:grid;grid-template-columns:repeat(3,1fr);gap:12px}
    .kpi{border:1px solid var(--border);border-radius:12px;padding:14px}
    .kpi .num{font-weight:800;font-size:1.6rem;color:var(--primary)}
    @media (max-width:900px){.main{left:0;width:100%}.kpis{grid-template-columns:1fr}}
  </style>
</head>
<body>
<div class="container">
  <!-- FR: Barre latérale (navigation coach) -->
  <div class="navigation">
    <ul>
      <li><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li><a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">My Classes</span></a></li> <!-- traduit -->
      <li class="active"><a href="members.php"><span class="icon"><ion-icon name="people-outline"></ion-icon></span><span class="title">My Members</span></a></li> <!-- traduit -->
      <li><a href="profile.php"><span class="icon"><ion-icon name="person-circle-outline"></ion-icon></span><span class="title">Profile</span></a></li> <!-- traduit -->
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Logout</span></a></li> <!-- traduit -->
    </ul>
  </div>

  <!-- FR: Zone principale (topbar + contenu) -->
  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div style="font-weight:700;color:#e50914">My Members — <?= htmlspecialchars($coachName) ?></div> <!-- traduit -->
    </div>

    <div class="wrap">
      <!-- FR: Messages globaux -->
      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- FR: KPIs de synthèse -->
      <div class="panel">
        <div class="kpis">
          <div class="kpi">
            <div class="num"><?= (int)$assignedCount ?></div>
            <div class="muted">Assigned</div> <!-- traduit -->
          </div>
          <div class="kpi">
            <div class="num"><?= (int)$poolCount ?></div>
            <div class="muted">Active subscribers without coach (PLUS/PRO)</div> <!-- traduit -->
          </div>
          <div class="kpi">
            <div class="num"><?= (int)count($myMembers) ?></div>
            <div class="muted">My members (list)</div> <!-- traduit -->
          </div>
        </div>
      </div>

      <!-- FR: Membres éligibles SANS coach -->
      <div class="panel">
        <div style="display:flex;justify-content:space-between;align-items:center">
          <h2 style="margin:0">Eligible members (active, approved, PLUS/PRO) — without coach</h2> <!-- traduit -->
          <div class="muted"><?= (int)$poolCount ?> available</div> <!-- traduit -->
        </div>
        <table style="margin-top:10px">
          <thead>
            <tr><td style="width:70px">ID</td><td>Member</td><td>Username</td><td>Email</td><td style="width:160px;text-align:right">Action</td></tr> <!-- traduit -->
          </thead>
          <tbody>
          <?php if (!$eligible): ?>
            <tr><td colspan="5" class="muted" style="text-align:center">No eligible member without coach.</td></tr> <!-- traduit -->
          <?php else: foreach ($eligible as $m): ?>
            <tr>
              <td><?= (int)$m['id'] ?></td>
              <td><?= htmlspecialchars($m['fullname']) ?></td>
              <td><?= htmlspecialchars($m['username']) ?></td>
              <td><?= htmlspecialchars($m['email']) ?></td>
              <td style="text-align:right">
                <form method="post" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="action" value="assign">
                  <input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>">
                  <button class="btn" type="submit">Assign</button> <!-- traduit -->
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- FR: Mes membres (assignés) -->
      <div class="panel">
        <h2 style="margin:0">My members</h2> <!-- traduit -->
        <table style="margin-top:10px">
          <thead>
            <tr><td style="width:70px">ID</td><td>Member</td><td>Username</td><td>Email</td><td style="width:200px;text-align:right">Action</td></tr> <!-- traduit -->
          </thead>
          <tbody>
          <?php if (!$myMembers): ?>
            <tr><td colspan="5" class="muted" style="text-align:center">No assigned member.</td></tr> <!-- traduit -->
          <?php else: foreach ($myMembers as $m): ?>
            <tr>
              <td><?= (int)$m['id'] ?></td>
              <td><?= htmlspecialchars($m['fullname']) ?></td>
              <td><?= htmlspecialchars($m['username']) ?></td>
              <td><?= htmlspecialchars($m['email']) ?></td>
              <td style="text-align:right">
                <form method="post" onsubmit="return confirm('Remove this member from your coaching?');" style="display:inline"> <!-- confirm traduit -->
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="action" value="unassign">
                  <input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>">
                  <button class="btn btn--ghost" type="submit">Remove</button> <!-- traduit -->
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
</body>
</html>
