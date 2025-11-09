<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('COACH','ADMIN');
require_once __DIR__ . '/../backend/db.php';

$coach = $_SESSION['user'] ?? null;
$coachId = (int)($coach['id'] ?? 0);
$coachName = $coach['fullname'] ?? 'Coach';
if ($coachId <= 0) { http_response_code(401); exit('Access denied.'); }

if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$CSRF = $_SESSION['csrf'];

$ok  = $_GET['ok']  ?? null;
$err = $_GET['err'] ?? null;

function redirect_with(string $param, string $msg): never {
  header('Location: '.$_SERVER['PHP_SELF'].'?'.$param.'='.urlencode($msg));
  exit;
}

/* POST actions */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
      throw new RuntimeException('Invalid CSRF.');
    }
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'assign') {
      $memberId = (int)($_POST['member_id'] ?? 0);
      if ($memberId <= 0) throw new RuntimeException('Invalid member.');

      $st = $pdo->prepare("SELECT id FROM users WHERE id=:id AND (role='MEMBER' OR role='MEMBRE')");
      $st->execute([':id'=>$memberId]);
      if (!$st->fetchColumn()) throw new RuntimeException('User not eligible.');

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

      $ex = $pdo->prepare("SELECT 1 FROM coach_members WHERE member_id=:m LIMIT 1");
      $ex->execute([':m'=>$memberId]);
      if ($ex->fetchColumn()) throw new RuntimeException("This member is already assigned to a coach.");

      $ins = $pdo->prepare("INSERT INTO coach_members (coach_id, member_id, assigned_at) VALUES (:c,:m,NOW())");
      $ins->execute([':c'=>$coachId, ':m'=>$memberId]);

      redirect_with('ok', 'Member successfully assigned.');
    }

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

/* Data */
$assignedCount = (int)$pdo->query("SELECT COUNT(*) FROM coach_members WHERE coach_id={$coachId}")->fetchColumn();

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
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Coach</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #0a0a0a;
      color: #f5f7fb;
      min-height: 100vh;
      background: radial-gradient(55% 80% at 50% 0%, rgba(220, 38, 38, 0.22), transparent 65%),
                  radial-gradient(60% 90% at 75% 15%, rgba(127, 29, 29, 0.18), transparent 70%),
                  linear-gradient(180deg, rgba(10, 10, 10, 0.98) 0%, rgba(10, 10, 10, 1) 100%);
    }
    .container { display: flex; min-height: 100vh; }
    .sidebar {
      width: 280px; background: rgba(17, 17, 17, 0.95);
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      padding: 2rem 1.5rem; position: fixed; height: 100vh; overflow-y: auto;
    }
    .logo { display: flex; align-items: center; gap: 1rem; margin-bottom: 3rem; }
    .logo-icon {
      width: 48px; height: 48px;
      background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%);
      border-radius: 12px; display: flex; align-items: center; justify-content: center;
      font-size: 1.5rem; box-shadow: 0 10px 30px rgba(220,38,38,0.4);
    }
    .logo-text h1 {
      font-size: 1.5rem; font-weight: 800;
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
    }
    .logo-text p { font-size: 0.75rem; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.1em; }
    .nav-menu { list-style: none; margin: 2rem 0; }
    .nav-item { margin-bottom: 0.5rem; }
    .nav-link {
      display: flex; align-items: center; gap: 1rem; padding: 1rem; color: #9ca3af;
      text-decoration: none; border-radius: 12px; transition: all 0.3s; font-weight: 500;
    }
    .nav-link:hover { background: rgba(255, 255, 255, 0.05); color: #fff; }
    .nav-link.active {
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
      color: #fff; box-shadow: 0 4px 20px rgba(220,38,38,0.3);
    }
    .nav-link ion-icon { font-size: 1.25rem; }
    .logout-btn {
      display: flex; align-items: center; gap: 1rem; padding: 1rem;
      background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px; color: #9ca3af; text-decoration: none; transition: all 0.3s;
      font-weight: 500; margin-top: 2rem;
    }
    .logout-btn:hover { background: rgba(220, 38, 38, 0.2); color: #fff; border-color: #dc2626; }
    .main-content { margin-left: 280px; flex: 1; padding: 2rem; }
    .header { margin-bottom: 2rem; }
    .header h1 { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
    .section {
      background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px; padding: 2rem; margin-bottom: 2rem;
    }
    .section-title { font-size: 1.25rem; font-weight: 600; margin-bottom: 1.5rem; }
    .alert { padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 500; }
    .alert.ok { background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); color: #4ade80; }
    .alert.err { background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #f87171; }
    .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
    .stat-card {
      background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px; padding: 1.5rem;
    }
    .stat-value { font-size: 2.5rem; font-weight: 800; color: #dc2626; margin-bottom: 0.5rem; }
    .stat-label { color: #9ca3af; font-size: 0.9rem; }
    table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
    thead td {
      font-weight: 600; color: #9ca3af; font-size: 0.85rem; text-transform: uppercase;
      letter-spacing: 0.05em; padding-bottom: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    tbody tr { border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.2s; }
    tbody tr:hover { background: rgba(220, 38, 38, 0.05); }
    td { padding: 1rem 0.75rem; vertical-align: middle; }
    .btn {
      background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
      color: #fff; border: none; border-radius: 10px; padding: 0.65rem 1.25rem;
      font-weight: 600; cursor: pointer; transition: all 0.3s; font-family: 'Poppins', sans-serif;
      font-size: 0.9rem;
    }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 10px 30px rgba(220, 38, 38, 0.4); }
    .btn-dark { background: rgba(255, 255, 255, 0.1); }
    .btn-dark:hover { background: rgba(255, 255, 255, 0.15); }
    .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
    .badge { background: rgba(220, 38, 38, 0.2); color: #dc2626; padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; }
    @media (max-width: 991px) {
      .sidebar { width: 0; opacity: 0; }
      .main-content { margin-left: 0; }
      .stats-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="container">
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
          <li class="nav-item"><a href="index.php" class="nav-link"><ion-icon name="grid"></ion-icon><span>Dashboard</span></a></li>
          <li class="nav-item"><a href="courses.php" class="nav-link"><ion-icon name="calendar"></ion-icon><span>My Classes</span></a></li>
          <li class="nav-item"><a href="members.php" class="nav-link active"><ion-icon name="people"></ion-icon><span>My Members</span></a></li>
          <li class="nav-item"><a href="profile.php" class="nav-link"><ion-icon name="person-circle"></ion-icon><span>Profile</span></a></li>
        </ul>
        <a href="/MyGym/backend/logout.php" class="logout-btn"><ion-icon name="log-out"></ion-icon><span>Logout</span></a>
      </nav>
    </aside>
    <main class="main-content">
      <div class="header">
        <h1>My Members</h1>
        <p style="color: #9ca3af;">Manage your assigned members and coaching relationships</p>
      </div>

      <?php if ($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert err"><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-value"><?= (int)$assignedCount ?></div>
          <div class="stat-label">Assigned Members</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= (int)$poolCount ?></div>
          <div class="stat-label">Available (PLUS/PRO)</div>
        </div>
        <div class="stat-card">
          <div class="stat-value"><?= (int)count($myMembers) ?></div>
          <div class="stat-label">Total in List</div>
        </div>
      </div>

      <!-- Eligible members -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">Eligible Members (PLUS/PRO, No Coach)</h2>
          <span class="badge"><?= (int)$poolCount ?> available</span>
        </div>
        <table>
          <thead>
            <tr><td style="width:70px">ID</td><td>Full Name</td><td>Username</td><td>Email</td><td style="width:140px;text-align:right">Actions</td></tr>
          </thead>
          <tbody>
          <?php if (!$eligible): ?>
            <tr><td colspan="5" style="color:#6b7280;text-align:center;padding:2rem">No eligible members without a coach</td></tr>
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
                  <button class="btn" type="submit">Assign</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>

      <!-- My members -->
      <div class="section">
        <h2 class="section-title">My Assigned Members</h2>
        <table>
          <thead>
            <tr><td style="width:70px">ID</td><td>Full Name</td><td>Username</td><td>Email</td><td style="width:140px;text-align:right">Actions</td></tr>
          </thead>
          <tbody>
          <?php if (!$myMembers): ?>
            <tr><td colspan="5" style="color:#6b7280;text-align:center;padding:2rem">No members assigned yet</td></tr>
          <?php else: foreach ($myMembers as $m): ?>
            <tr>
              <td><?= (int)$m['id'] ?></td>
              <td><?= htmlspecialchars($m['fullname']) ?></td>
              <td><?= htmlspecialchars($m['username']) ?></td>
              <td><?= htmlspecialchars($m['email']) ?></td>
              <td style="text-align:right">
                <form method="post" onsubmit="return confirm('Remove this member from your coaching?');" style="display:inline">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                  <input type="hidden" name="action" value="unassign">
                  <input type="hidden" name="member_id" value="<?= (int)$m['id'] ?>">
                  <button class="btn btn-dark" type="submit">Remove</button>
                </form>
              </td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
</body>
</html>
