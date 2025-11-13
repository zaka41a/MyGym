<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('COACH','ADMIN');
require_once __DIR__ . '/../backend/db.php';

const COACH_MAX_MEMBERS = 5;

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

$availableSlots = max(0, COACH_MAX_MEMBERS - $assignedCount);
$visibleEligible = count($eligible);
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
  <?php include __DIR__ . '/../shared/coach-styles.php'; ?>
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <div class="logo">
        <svg width="180" height="50" viewBox="0 0 220 60" fill="none" xmlns="http://www.w3.org/2000/svg">
          <g transform="translate(5, 15)">
            <rect x="0" y="5" width="6" height="20" rx="1.5" fill="url(#gradientCoach1)"/>
            <rect x="6" y="8" width="2" height="14" rx="0.5" fill="#4338ca"/>
            <rect x="8" y="12" width="34" height="6" rx="3" fill="url(#gradientCoach1)"/>
            <rect x="42" y="8" width="2" height="14" rx="0.5" fill="#4338ca"/>
            <rect x="44" y="5" width="6" height="20" rx="1.5" fill="url(#gradientCoach1)"/>
          </g>
          <text x="65" y="32" font-family="system-ui, -apple-system, 'Segoe UI', Arial, sans-serif" font-size="28" font-weight="900" fill="url(#textGradientCoach)" letter-spacing="2">MyGym</text>
          <text x="65" y="46" font-family="system-ui, -apple-system, 'Segoe UI', Arial, sans-serif" font-size="10" font-weight="600" fill="#94a3b8" letter-spacing="3">COACH PORTAL</text>
          <defs>
            <linearGradient id="gradientCoach1" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#6366f1"/>
              <stop offset="100%" stop-color="#4f46e5"/>
            </linearGradient>
            <linearGradient id="textGradientCoach" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#6366f1"/>
              <stop offset="50%" stop-color="#8b5cf6"/>
              <stop offset="100%" stop-color="#6366f1"/>
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
        <div>
          <h1>My Members</h1>
          <p style="color:#9ca3af;">Manage assignments and track who is ready for coaching.</p>
        </div>
        <div class="header-date">
          <ion-icon name="calendar-outline"></ion-icon>
          <span><?= date('l, F j') ?></span>
        </div>
      </div>

      <!-- Members Capacity Hero -->
      <div class="capacity-hero">
        <div class="capacity-ring-container">
          <svg class="capacity-ring" width="180" height="180" viewBox="0 0 180 180">
            <circle cx="90" cy="90" r="75" fill="none" stroke="rgba(99, 102, 241, 0.1)" stroke-width="12"/>
            <circle cx="90" cy="90" r="75" fill="none" stroke="url(#capacityGradient)" stroke-width="12"
                    stroke-dasharray="471.24"
                    stroke-dashoffset="<?= 471.24 - (471.24 * ($assignedCount / COACH_MAX_MEMBERS)) ?>"
                    stroke-linecap="round"
                    transform="rotate(-90 90 90)"/>
            <defs>
              <linearGradient id="capacityGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                <stop offset="0%" stop-color="#6366f1"/>
                <stop offset="100%" stop-color="#8b5cf6"/>
              </linearGradient>
            </defs>
          </svg>
          <div class="capacity-ring-text">
            <div class="capacity-current"><?= $assignedCount ?></div>
            <div class="capacity-total">/ <?= COACH_MAX_MEMBERS ?></div>
            <div class="capacity-label">Members</div>
          </div>
        </div>
        <div class="capacity-info">
          <div class="capacity-info-item">
            <ion-icon name="briefcase"></ion-icon>
            <div>
              <div class="capacity-info-value"><?= $availableSlots ?></div>
              <div class="capacity-info-label">Available Slots</div>
            </div>
          </div>
          <div class="capacity-info-item">
            <ion-icon name="medal"></ion-icon>
            <div>
              <div class="capacity-info-value"><?= (int)$poolCount ?></div>
              <div class="capacity-info-label">Eligible Pool (PLUS/PRO)</div>
            </div>
          </div>
          <div class="capacity-info-item">
            <ion-icon name="eye"></ion-icon>
            <div>
              <div class="capacity-info-value"><?= (int)$visibleEligible ?></div>
              <div class="capacity-info-label">Visible Prospects</div>
            </div>
          </div>
        </div>
      </div>

      <?php if ($ok): ?><div class="alert alert-success"><ion-icon name="checkmark-circle"></ion-icon><?= htmlspecialchars($ok) ?></div><?php endif; ?>
      <?php if ($err): ?><div class="alert alert-error"><ion-icon name="alert-circle"></ion-icon><?= htmlspecialchars($err) ?></div><?php endif; ?>

      <!-- Eligible members -->
      <div class="section activity-section">
        <div class="section-header">
          <h2 class="section-title">
            <ion-icon name="medal"></ion-icon>
            Eligible Members (PLUS/PRO, No Coach)
          </h2>
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
      <div class="section activity-section">
        <div class="section-header">
          <h2 class="section-title">
            <ion-icon name="people"></ion-icon>
            My Assigned Members
          </h2>
          <span class="badge badge-success"><?= (int)$assignedCount ?> active</span>
        </div>
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
                  <button class="btn btn-ghost" type="submit">Remove</button>
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
