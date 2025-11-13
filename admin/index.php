<?php
declare(strict_types=1);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../backend/auth.php';
requireRole('ADMIN');
require_once __DIR__ . '/../backend/db.php';

// Get user info
$user = $_SESSION['user'] ?? [];
$userName = $user['fullname'] ?? 'Administrator';

// Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role IN ('MEMBER','MEMBRE')");
$totalMembers = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'COACH'");
$totalCoaches = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'ACTIVE'");
$activeSubscriptions = $stmt->fetchColumn();

// Total revenue calculation (sum of plan prices for active subscriptions)
$stmt = $pdo->query("
  SELECT COALESCE(SUM(p.price_cents), 0)
  FROM subscriptions s
  JOIN plans p ON p.id = s.plan_id
  WHERE s.status = 'ACTIVE'
");
$totalRevenue = $stmt->fetchColumn() / 100; // Convert cents to euros

// Pending subscriptions
$stmt = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'PENDING'");
$pendingSubscriptions = $stmt->fetchColumn();

// Recent user registrations
$recentUsers = $pdo->query("
  SELECT id, fullname, email, role, created_at
  FROM users
  ORDER BY id DESC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Recent class bookings (if bookings table exists)
try {
  $recentBookings = $pdo->query("
    SELECT r.id, r.created_at,
           u.fullname,
           c.name AS course_name
    FROM reservations r
    JOIN users u ON u.id = r.user_id
    JOIN courses c ON c.id = r.course_id
    ORDER BY r.id DESC
    LIMIT 5
  ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
  $recentBookings = [];
}

// Recent subscriptions
$recentSubs = $pdo->query("
  SELECT s.id, s.status, s.created_at,
         u.fullname,
         p.name AS plan_name
  FROM subscriptions s
  JOIN users u ON u.id = s.user_id
  JOIN plans p ON p.id = s.plan_id
  ORDER BY s.id DESC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get total users for member growth percentage
$stmt = $pdo->query("SELECT COUNT(*) FROM users");
$totalUsers = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Admin Dashboard</title>
  <?php include __DIR__ . '/../shared/head-meta.php'; ?>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <?php include __DIR__ . '/../shared/admin-styles.php'; ?>
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
            <a href="index.php" class="nav-link active">
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
            <a href="courses.php" class="nav-link">
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
        <div>
          <h1>Welcome back, <?= htmlspecialchars($userName) ?>!</h1>
          <p>Here's what's happening with your gym today.</p>
        </div>
        <div class="header-date">
          <ion-icon name="calendar-outline"></ion-icon>
          <span><?= date('l, F j, Y') ?></span>
        </div>
      </div>

      <!-- System Overview Panel -->
      <div class="system-overview-panel">
        <div class="overview-header">
          <div class="overview-title-wrapper">
            <ion-icon name="stats-chart-outline"></ion-icon>
            <div>
              <h2>System Overview</h2>
              <p>Complete breakdown of users by role</p>
            </div>
          </div>
        </div>

        <div class="overview-cards-grid">
          <!-- Total Users Card -->
          <div class="overview-card total-card">
            <div class="overview-card-icon" style="background: linear-gradient(135deg, #dc2626, #991b1b);">
              <ion-icon name="people"></ion-icon>
            </div>
            <div class="overview-card-content">
              <div class="overview-card-value"><?= number_format($totalUsers) ?></div>
              <div class="overview-card-label">Total Users</div>
              <div class="overview-card-desc">All system users</div>
            </div>
          </div>

          <!-- Admins Card -->
          <div class="overview-card admin-card">
            <div class="overview-card-icon" style="background: linear-gradient(135deg, #7c3aed, #6d28d9);">
              <ion-icon name="shield-checkmark"></ion-icon>
            </div>
            <div class="overview-card-content">
              <?php
              $adminCount = 0;
              try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'ADMIN'");
                $adminCount = $stmt->fetchColumn();
              } catch (Throwable $e) {}
              ?>
              <div class="overview-card-value"><?= number_format($adminCount) ?></div>
              <div class="overview-card-label">Admins</div>
              <div class="overview-card-desc">Full access</div>
            </div>
          </div>

          <!-- Coaches Card -->
          <div class="overview-card coach-card">
            <div class="overview-card-icon" style="background: linear-gradient(135deg, #ea580c, #c2410c);">
              <ion-icon name="fitness"></ion-icon>
            </div>
            <div class="overview-card-content">
              <div class="overview-card-value"><?= number_format($totalCoaches) ?></div>
              <div class="overview-card-label">Coaches</div>
              <div class="overview-card-desc">Training staff</div>
            </div>
          </div>

          <!-- Members Card -->
          <div class="overview-card member-card">
            <div class="overview-card-icon" style="background: linear-gradient(135deg, #059669, #047857);">
              <ion-icon name="person"></ion-icon>
            </div>
            <div class="overview-card-content">
              <div class="overview-card-value"><?= number_format($totalMembers) ?></div>
              <div class="overview-card-label">Members</div>
              <div class="overview-card-desc">Active members</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Modern Quick Actions -->
      <div class="modern-quick-actions">
        <div class="quick-actions-header">
          <ion-icon name="flash-outline"></ion-icon>
          <h2>Quick Actions</h2>
        </div>

        <div class="modern-actions-grid">
          <a href="users.php" class="modern-action-card" style="--card-color: #dc2626;">
            <div class="modern-action-icon">
              <ion-icon name="person-add-outline"></ion-icon>
            </div>
            <div class="modern-action-content">
              <h3>Add User</h3>
              <p>Register members or coaches</p>
            </div>
            <div class="modern-action-arrow">
              <ion-icon name="arrow-forward"></ion-icon>
            </div>
          </a>

          <a href="courses.php" class="modern-action-card" style="--card-color: #ea580c;">
            <div class="modern-action-icon">
              <ion-icon name="calendar-outline"></ion-icon>
            </div>
            <div class="modern-action-content">
              <h3>Create Class</h3>
              <p>Schedule new activities</p>
            </div>
            <div class="modern-action-arrow">
              <ion-icon name="arrow-forward"></ion-icon>
            </div>
          </a>

          <a href="subscriptions.php" class="modern-action-card" style="--card-color: #7c3aed;">
            <div class="modern-action-icon">
              <ion-icon name="checkmark-done-outline"></ion-icon>
            </div>
            <div class="modern-action-content">
              <h3>Approve Plans</h3>
              <p><?= $pendingSubscriptions ?> pending approval</p>
            </div>
            <?php if ($pendingSubscriptions > 0): ?>
              <div class="modern-action-badge"><?= $pendingSubscriptions ?></div>
            <?php endif; ?>
            <div class="modern-action-arrow">
              <ion-icon name="arrow-forward"></ion-icon>
            </div>
          </a>

          <a href="subscriptions.php" class="modern-action-card" style="--card-color: #059669;">
            <div class="modern-action-icon">
              <ion-icon name="analytics-outline"></ion-icon>
            </div>
            <div class="modern-action-content">
              <h3>View Reports</h3>
              <p>Analytics & insights</p>
            </div>
            <div class="modern-action-arrow">
              <ion-icon name="arrow-forward"></ion-icon>
            </div>
          </a>
        </div>
      </div>

      <!-- Recent Activity Timeline -->
      <div class="activity-timeline-section">
        <div class="timeline-header">
          <div class="timeline-title-wrapper">
            <ion-icon name="time-outline"></ion-icon>
            <h2>Recent Activity</h2>
          </div>
          <a href="users.php" class="timeline-view-all">
            <span>View All</span>
            <ion-icon name="arrow-forward"></ion-icon>
          </a>
        </div>

        <div class="activity-timeline">
          <?php
          // Combine recent users and subscriptions
          $timeline = [];
          foreach ($recentUsers as $user) {
            $timeline[] = [
              'type' => 'user',
              'time' => $user['created_at'],
              'data' => $user
            ];
          }
          foreach ($recentSubs as $sub) {
            $timeline[] = [
              'type' => 'subscription',
              'time' => $sub['created_at'],
              'data' => $sub
            ];
          }
          usort($timeline, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
          });
          $timeline = array_slice($timeline, 0, 8);
          ?>

          <?php if (empty($timeline)): ?>
            <div class="timeline-empty">
              <ion-icon name="albums-outline"></ion-icon>
              <p>No recent activity</p>
            </div>
          <?php else: ?>
            <?php foreach ($timeline as $item): ?>
              <div class="timeline-item">
                <div class="timeline-dot" style="--dot-color: <?= $item['type'] === 'user' ? '#dc2626' : '#059669' ?>;"></div>
                <div class="timeline-content">
                  <div class="timeline-icon" style="background: <?= $item['type'] === 'user' ? 'rgba(220, 38, 38, 0.1)' : 'rgba(5, 150, 105, 0.1)' ?>;">
                    <ion-icon name="<?= $item['type'] === 'user' ? 'person-add' : 'card' ?>"></ion-icon>
                  </div>
                  <div class="timeline-info">
                    <h4><?= htmlspecialchars($item['data']['fullname']) ?></h4>
                    <?php if ($item['type'] === 'user'): ?>
                      <p>New <?= strtolower($item['data']['role']) ?> registered • <?= htmlspecialchars($item['data']['email']) ?></p>
                    <?php else: ?>
                      <p>Subscribed to <?= htmlspecialchars($item['data']['plan_name']) ?> •
                        <span class="timeline-status status-<?= strtolower($item['data']['status']) ?>">
                          <?= htmlspecialchars($item['data']['status']) ?>
                        </span>
                      </p>
                    <?php endif; ?>
                  </div>
                  <div class="timeline-time">
                    <?= date('M d, H:i', strtotime($item['time'])) ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </main>
  </div>
</body>
</html>
