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

// Calculate stats for the period
$totalClasses = count($sessions);
$totalBookings = 0;
$totalCapacity = 0;
$activeClasses = 0;
$now = new DateTime();

foreach ($sessions as $s) {
  $totalBookings += (int)($s['booked'] ?? 0);
  $totalCapacity += (int)($s['capacity'] ?? 0);

  // Count active classes (future sessions)
  if (!empty($s['start_at'])) {
    $sessionStart = new DateTime($s['start_at']);
    if ($sessionStart >= $now) {
      $activeClasses++;
    }
  }
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
  <?php include __DIR__ . '/../shared/admin-styles.php'; ?>
  <style>
    /* Additional styles for activity badges */
    .activity-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.375rem 0.875rem;
      border-radius: 8px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border: 1px solid;
    }

    .activity-badge-yoga {
      background: rgba(139, 92, 246, 0.2);
      color: #a78bfa;
      border-color: rgba(139, 92, 246, 0.3);
    }

    .activity-badge-crossfit {
      background: rgba(220, 38, 38, 0.2);
      color: #dc2626;
      border-color: rgba(220, 38, 38, 0.3);
    }

    .activity-badge-pilates {
      background: rgba(236, 72, 153, 0.2);
      color: #ec4899;
      border-color: rgba(236, 72, 153, 0.3);
    }

    .activity-badge-spin {
      background: rgba(234, 179, 8, 0.2);
      color: #eab308;
      border-color: rgba(234, 179, 8, 0.3);
    }

    .activity-badge-boxing {
      background: rgba(239, 68, 68, 0.2);
      color: #ef4444;
      border-color: rgba(239, 68, 68, 0.3);
    }

    .activity-badge-default {
      background: rgba(59, 130, 246, 0.2);
      color: #3b82f6;
      border-color: rgba(59, 130, 246, 0.3);
    }

    /* Schedule display */
    .schedule-info {
      display: flex;
      flex-direction: column;
      gap: 0.25rem;
    }

    .schedule-date {
      font-weight: 600;
      color: #fff;
    }

    .schedule-time {
      font-size: 0.875rem;
      color: #9ca3af;
      display: flex;
      align-items: center;
      gap: 0.375rem;
    }

    .schedule-time ion-icon {
      font-size: 1rem;
      color: #dc2626;
    }

    /* Coach display */
    .coach-info {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .coach-avatar {
      width: 36px;
      height: 36px;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.3) 0%, rgba(239, 68, 68, 0.3) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.875rem;
      color: #dc2626;
      border: 2px solid rgba(220, 38, 38, 0.2);
    }

    .coach-name {
      font-weight: 500;
    }

    /* Capacity display */
    .capacity-display {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
    }

    .capacity-progress {
      width: 100%;
      height: 6px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 999px;
      overflow: hidden;
    }

    .capacity-progress-fill {
      height: 100%;
      background: linear-gradient(90deg, #10b981, #059669);
      border-radius: 999px;
      transition: width 0.3s ease;
    }

    .capacity-progress-fill.warning {
      background: linear-gradient(90deg, #f59e0b, #d97706);
    }

    .capacity-progress-fill.full {
      background: linear-gradient(90deg, #dc2626, #991b1b);
    }

    .capacity-text {
      font-weight: 600;
      font-size: 0.875rem;
    }

    /* Alternating row colors */
    tbody tr:nth-child(even) {
      background: rgba(255, 255, 255, 0.02);
    }

    tbody tr:hover {
      background: rgba(220, 38, 38, 0.08);
      transform: scale(1.005);
    }

    /* No data state */
    .no-data {
      text-align: center;
      padding: 3rem;
      color: #6b7280;
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

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-label">Total Classes</div>
              <div class="stat-value"><?= $totalClasses ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="calendar"></ion-icon>
            </div>
          </div>
          <div class="stat-trend">
            <ion-icon name="time"></ion-icon>
            <span>In selected period</span>
          </div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width: 85%;"></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-label">Active Classes</div>
              <div class="stat-value"><?= $activeClasses ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="barbell"></ion-icon>
            </div>
          </div>
          <div class="stat-trend positive">
            <ion-icon name="trending-up"></ion-icon>
            <span>Upcoming sessions</span>
          </div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width: <?= $totalClasses > 0 ? min(100, ($activeClasses / $totalClasses) * 100) : 0 ?>%;"></div>
          </div>
        </div>

        <div class="stat-card">
          <div class="stat-header">
            <div>
              <div class="stat-label">Total Bookings</div>
              <div class="stat-value"><?= $totalBookings ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="people"></ion-icon>
            </div>
          </div>
          <div class="stat-trend positive">
            <ion-icon name="checkmark-circle"></ion-icon>
            <span><?= $totalCapacity > 0 ? round(($totalBookings / $totalCapacity) * 100) : 0 ?>% capacity filled</span>
          </div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width: <?= $totalCapacity > 0 ? min(100, ($totalBookings / $totalCapacity) * 100) : 0 ?>%;"></div>
          </div>
        </div>
      </div>

      <!-- Sessions Section -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">
            All Sessions (<?= htmlspecialchars($start->format('d/m/Y')) ?> → <?= htmlspecialchars($end->format('d/m/Y')) ?>)
          </h2>
        </div>

        <?php if ($sqlError): ?>
          <div class="alert alert-error">SQL error: <?= htmlspecialchars($sqlError) ?></div>
        <?php endif; ?>

        <div style="overflow-x:auto">
          <table>
            <thead>
              <tr>
                <td>Schedule</td>
                <td>Activity</td>
                <td>Coach</td>
                <td style="text-align: center;">Attendance</td>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($sessions)): ?>
              <tr>
                <td colspan="4" class="no-data">
                  <ion-icon name="calendar-outline" style="font-size: 3rem; color: #4b5563; margin-bottom: 0.5rem;"></ion-icon>
                  <div>No sessions in this period.</div>
                </td>
              </tr>
            <?php else: foreach ($sessions as $s):
                $date  = !empty($s['start_at']) ? (new DateTime($s['start_at']))->format('M d, Y') : '—';
                $heure = '—';
                if (!empty($s['start_at'])) {
                  $sd = new DateTime($s['start_at']); $hi = $sd->format('H:i');
                  $hf = !empty($s['end_at']) ? (new DateTime($s['end_at']))->format('H:i') : '—';
                  $heure = $hi.' → '.$hf;
                }
                $cap   = (int)($s['capacity']      ?? 0);
                $book  = (int)($s['booked']        ?? 0);
                $act   = (string)($s['activity_name'] ?? '');
                $actC  = strtoupper((string)($s['activity_code'] ?? ''));
                $coach = (string)($s['coach_name']    ?? '');

                // Determine badge class based on activity code
                $badgeClass = 'activity-badge-default';
                if (stripos($actC, 'YOGA') !== false) $badgeClass = 'activity-badge-yoga';
                elseif (stripos($actC, 'CROSSFIT') !== false || stripos($actC, 'CF') !== false) $badgeClass = 'activity-badge-crossfit';
                elseif (stripos($actC, 'PILATES') !== false) $badgeClass = 'activity-badge-pilates';
                elseif (stripos($actC, 'SPIN') !== false || stripos($actC, 'CYCLING') !== false) $badgeClass = 'activity-badge-spin';
                elseif (stripos($actC, 'BOX') !== false) $badgeClass = 'activity-badge-boxing';

                // Calculate capacity percentage
                $percentage = $cap > 0 ? ($book / $cap) * 100 : 0;
                $progressClass = '';
                if ($percentage >= 100) $progressClass = 'full';
                elseif ($percentage >= 80) $progressClass = 'warning';

                // Get coach initials for avatar
                $coachInitials = '?';
                if ($coach) {
                  $parts = explode(' ', trim($coach));
                  if (count($parts) >= 2) {
                    $coachInitials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
                  } else {
                    $coachInitials = strtoupper(substr($coach, 0, 2));
                  }
                }
            ?>
              <tr>
                <td>
                  <div class="schedule-info">
                    <div class="schedule-date"><?= htmlspecialchars($date) ?></div>
                    <div class="schedule-time">
                      <ion-icon name="time-outline"></ion-icon>
                      <?= htmlspecialchars($heure) ?>
                    </div>
                  </div>
                </td>
                <td>
                  <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                    <strong><?= htmlspecialchars($act) ?></strong>
                    <span class="activity-badge <?= $badgeClass ?>">
                      <ion-icon name="fitness"></ion-icon>
                      <?= htmlspecialchars($actC) ?>
                    </span>
                  </div>
                </td>
                <td>
                  <?php if ($coach): ?>
                  <div class="coach-info">
                    <div class="coach-avatar"><?= $coachInitials ?></div>
                    <span class="coach-name"><?= htmlspecialchars($coach) ?></span>
                  </div>
                  <?php else: ?>
                  <span style="color: #6b7280;">Not assigned</span>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="capacity-display">
                    <div class="capacity-text">
                      <span style="color: #10b981;"><?= $book ?></span>
                      <span style="color: #6b7280;"> / </span>
                      <span><?= $cap ?></span>
                    </div>
                    <div class="capacity-progress">
                      <div class="capacity-progress-fill <?= $progressClass ?>" style="width: <?= min(100, $percentage) ?>%;"></div>
                    </div>
                  </div>
                </td>
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
