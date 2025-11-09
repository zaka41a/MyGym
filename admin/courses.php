<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('ADMIN');
require_once __DIR__ . '/../backend/db.php';

$DEBUG = false;
if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors', '1'); }

function col_exists(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare("
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
    LIMIT 1
  ");
  $q->execute([':t'=>$table, ':c'=>$col]);
  return (bool)$q->fetchColumn();
}

// Period (default: current month)
$start = new DateTime('first day of this month 00:00:00');
$end   = new DateTime('last day of this month 23:59:59');
if (!empty($_GET['start'])) { try { $start = new DateTime($_GET['start'].' 00:00:00'); } catch (Throwable $e) {} }
if (!empty($_GET['end']))   { try { $end   = new DateTime($_GET['end']  .' 23:59:59'); } catch (Throwable $e) {} }

$sqlError = null;
$sessions = [];

try {
  $hasStart   = col_exists($pdo, 'sessions', 'start_at');
  $hasEnd     = col_exists($pdo, 'sessions', 'end_at');
  $hasCap     = col_exists($pdo, 'sessions', 'capacity');
  $hasActId   = col_exists($pdo, 'sessions', 'activity_id');
  $hasCoachId = col_exists($pdo, 'sessions', 'coach_id');

  if (!$hasStart) {
    throw new RuntimeException("Column sessions.start_at not found.");
  }

  $select = ["s.id", "s.start_at"];
  if ($hasEnd) $select[] = "s.end_at";
  if ($hasCap) $select[] = "s.capacity";

  $select[] = "a.code AS activity_code";
  $select[] = "a.name AS activity_name";
  $joinAct = $hasActId ? "LEFT JOIN activities a ON a.id = s.activity_id" : "LEFT JOIN activities a ON 1=0";

  $select[] = "u.fullname AS coach_name";
  $joinCoach = $hasCoachId ? "LEFT JOIN users u ON u.id = s.coach_id" : "LEFT JOIN users u ON 1=0";

  $hasRes       = col_exists($pdo, 'reservations', 'session_id');
  $hasResStatus = $hasRes && col_exists($pdo, 'reservations', 'status');
  $bookedSql = "0 AS booked";
  if ($hasRes) {
    $bookedSql = $hasResStatus
      ? "(SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id AND r.status IN ('BOOKED','ATTENDED')) AS booked"
      : "(SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id) AS booked";
  }
  $select[] = $bookedSql;

  $sql = "
    SELECT ".implode(",\n           ", $select)."
    FROM sessions s
    $joinAct
    $joinCoach
    WHERE s.start_at BETWEEN :d1 AND :d2
    ORDER BY s.start_at ASC
  ";

  $st = $pdo->prepare($sql);
  $st->execute([
    ':d1' => $start->format('Y-m-d H:i:s'),
    ':d2' => $end->format('Y-m-d H:i:s'),
  ]);
  $sessions = $st->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  $sqlError = $e->getMessage();
  if ($DEBUG) error_log("ADMIN/courses.php SQL ERROR: ".$sqlError);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Activities & Classes</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif;
      background: #0a0a0a;
      color: #f5f7fb;
      min-height: 100vh;
      background: radial-gradient(55% 80% at 50% 0%, rgba(220, 38, 38, 0.22), transparent 65%),
                  radial-gradient(60% 90% at 75% 15%, rgba(127, 29, 29, 0.18), transparent 70%),
                  linear-gradient(180deg, rgba(10, 10, 10, 0.98) 0%, rgba(10, 10, 10, 1) 100%);
    }

    .container {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 280px;
      background: rgba(17, 17, 17, 0.95);
      border-right: 1px solid rgba(255, 255, 255, 0.1);
      padding: 2rem 1.5rem;
      position: fixed;
      height: 100vh;
      overflow-y: auto;
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 3rem;
    }

    .logo-icon {
      width: 48px;
      height: 48px;
      background: linear-gradient(135deg, #dc2626 0%, #7f1d1d 100%);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      box-shadow: 0 10px 30px rgba(220,38,38,0.4);
    }

    .logo-text h1 {
      font-size: 1.5rem;
      font-weight: 800;
      background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .logo-text p {
      font-size: 0.75rem;
      color: #9ca3af;
      text-transform: uppercase;
      letter-spacing: 0.1em;
    }

    .nav-menu {
      list-style: none;
      margin: 2rem 0;
    }

    .nav-item {
      margin-bottom: 0.5rem;
    }

    .nav-link {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      color: #9ca3af;
      text-decoration: none;
      border-radius: 12px;
      transition: all 0.3s;
      font-weight: 500;
    }

    .nav-link:hover {
      background: rgba(255, 255, 255, 0.05);
      color: #fff;
    }

    .nav-link.active {
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
      color: #fff;
      box-shadow: 0 4px 20px rgba(220,38,38,0.3);
    }

    .nav-link ion-icon {
      font-size: 1.25rem;
    }

    .logout-btn {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      color: #9ca3af;
      text-decoration: none;
      transition: all 0.3s;
      font-weight: 500;
      margin-top: 2rem;
    }

    .logout-btn:hover {
      background: rgba(220, 38, 38, 0.2);
      color: #fff;
      border-color: #dc2626;
    }

    /* Main Content */
    .main-content {
      margin-left: 280px;
      flex: 1;
      padding: 2rem;
    }

    .header {
      margin-bottom: 2rem;
    }

    .header h1 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    /* Section */
    .section {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
    }

    /* Alert */
    .alert-error {
      padding: 1rem;
      border-radius: 12px;
      margin-bottom: 1.5rem;
      background: rgba(239, 68, 68, 0.2);
      border: 1px solid rgba(239, 68, 68, 0.4);
      color: #ef4444;
    }

    /* Table */
    table {
      width: 100%;
      border-collapse: collapse;
    }

    thead td {
      font-weight: 600;
      color: #9ca3af;
      font-size: 0.85rem;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      padding-bottom: 12px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    tbody tr {
      border-bottom: 1px solid rgba(255, 255, 255, 0.05);
      transition: all 0.2s;
    }

    tbody tr:hover {
      background: rgba(220, 38, 38, 0.05);
    }

    td {
      padding: 1rem 0.75rem;
      vertical-align: middle;
    }

    @media (max-width: 991px) {
      .sidebar {
        width: 0;
        opacity: 0;
      }
      .main-content {
        margin-left: 0;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
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
          <li class="nav-item">
            <a href="index.php" class="nav-link">
              <ion-icon name="grid"></ion-icon>
              <span>Dashboard</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="users.php" class="nav-link">
              <ion-icon name="people"></ion-icon>
              <span>Users</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="courses.php" class="nav-link active">
              <ion-icon name="barbell"></ion-icon>
              <span>Activities & Classes</span>
            </a>
          </li>
          <li class="nav-item">
            <a href="subscriptions.php" class="nav-link">
              <ion-icon name="card"></ion-icon>
              <span>Subscriptions</span>
            </a>
          </li>
        </ul>

        <a href="/MyGym/backend/logout.php" class="logout-btn">
          <ion-icon name="log-out"></ion-icon>
          <span>Logout</span>
        </a>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
      <div class="header">
        <h1>Activities & Classes</h1>
        <p style="color: #9ca3af;">View all scheduled sessions and attendance.</p>
      </div>

      <div class="section">
        <h2 class="section-title">
          All Sessions (<?= htmlspecialchars($start->format('d/m/Y')) ?> → <?= htmlspecialchars($end->format('d/m/Y')) ?>)
        </h2>

        <?php if ($sqlError): ?>
          <div class="alert-error">SQL error: <?= htmlspecialchars($sqlError) ?></div>
        <?php endif; ?>

        <div style="overflow-x:auto">
          <table>
            <thead>
              <tr>
                <td style="width:140px">Date</td>
                <td style="width:120px">Time</td>
                <td>Activity</td>
                <td style="width:160px">Coach</td>
                <td style="width:90px">Booked</td>
                <td style="width:90px">Capacity</td>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($sessions)): ?>
              <tr><td colspan="6" style="text-align:center;color:#9ca3af">No sessions in this period.</td></tr>
            <?php else: foreach ($sessions as $s):
                $date  = !empty($s['start_at']) ? (new DateTime($s['start_at']))->format('d/m/Y') : '—';
                $heure = '—';
                if (!empty($s['start_at'])) {
                  $sd = new DateTime($s['start_at']); $hi = $sd->format('H:i');
                  $hf = !empty($s['end_at']) ? (new DateTime($s['end_at']))->format('H:i') : '—';
                  $heure = $hi.' → '.$hf;
                }
                $cap   = (int)($s['capacity']      ?? 0);
                $book  = (int)($s['booked']        ?? 0);
                $act   = (string)($s['activity_name'] ?? '');
                $actC  = (string)($s['activity_code'] ?? '');
                $coach = (string)($s['coach_name']    ?? '—');
            ?>
              <tr>
                <td><?= htmlspecialchars($date) ?></td>
                <td><?= htmlspecialchars($heure) ?></td>
                <td><?= htmlspecialchars($act) ?> <span style="color:#9ca3af">(<?= htmlspecialchars($actC) ?>)</span></td>
                <td><?= htmlspecialchars($coach ?: '—') ?></td>
                <td><?= $book ?></td>
                <td><?= $cap ?></td>
              </tr>
            <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
