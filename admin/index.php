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

      <!-- Stats Grid -->
      <div class="stats-grid">
        <div class="stat-card stat-members">
          <div class="stat-header">
            <div>
              <div class="stat-label">Total Members</div>
              <div class="stat-value"><?= number_format($totalMembers) ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="people"></ion-icon>
            </div>
          </div>
          <div class="stat-trend">
            <ion-icon name="trending-up"></ion-icon>
            <span>+12% this month</span>
          </div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width: <?= min(($totalMembers / max($totalUsers, 1)) * 100, 100) ?>%;"></div>
          </div>
        </div>

        <div class="stat-card stat-coaches">
          <div class="stat-header">
            <div>
              <div class="stat-label">Active Coaches</div>
              <div class="stat-value"><?= number_format($totalCoaches) ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="fitness"></ion-icon>
            </div>
          </div>
          <div class="stat-trend">
            <ion-icon name="trending-up"></ion-icon>
            <span>Professional trainers</span>
          </div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width: 100%;"></div>
          </div>
        </div>

        <div class="stat-card stat-subscriptions">
          <div class="stat-header">
            <div>
              <div class="stat-label">Active Subscriptions</div>
              <div class="stat-value"><?= number_format($activeSubscriptions) ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="card"></ion-icon>
            </div>
          </div>
          <div class="stat-trend">
            <ion-icon name="trending-up"></ion-icon>
            <span>+8% growth</span>
          </div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width: <?= min(($activeSubscriptions / max($totalMembers, 1)) * 100, 100) ?>%;"></div>
          </div>
        </div>

        <div class="stat-card stat-revenue">
          <div class="stat-header">
            <div>
              <div class="stat-label">Monthly Revenue</div>
              <div class="stat-value">$<?= number_format($totalRevenue) ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="wallet"></ion-icon>
            </div>
          </div>
          <div class="stat-trend positive">
            <ion-icon name="arrow-up-circle"></ion-icon>
            <span>Strong performance</span>
          </div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width: 92%;"></div>
          </div>
        </div>
      </div>

      <!-- Quick Actions & Alerts -->
      <div class="dashboard-row">
        <!-- Quick Actions -->
        <div class="quick-actions-panel">
          <div class="section-header">
            <h2 class="section-title">Quick Actions</h2>
          </div>
          <div class="quick-actions-grid">
            <a href="users.php" class="quick-action-card">
              <div class="quick-action-icon">
                <ion-icon name="person-add"></ion-icon>
              </div>
              <div class="quick-action-info">
                <h3>Add New User</h3>
                <p>Register members or coaches</p>
              </div>
            </a>

            <a href="courses.php" class="quick-action-card">
              <div class="quick-action-icon">
                <ion-icon name="add-circle"></ion-icon>
              </div>
              <div class="quick-action-info">
                <h3>Create Class</h3>
                <p>Schedule new activities</p>
              </div>
            </a>

            <a href="subscriptions.php" class="quick-action-card">
              <div class="quick-action-icon">
                <ion-icon name="checkmark-done"></ion-icon>
              </div>
              <div class="quick-action-info">
                <h3>Approve Plans</h3>
                <p><?= $pendingSubscriptions ?> pending approval</p>
              </div>
              <?php if ($pendingSubscriptions > 0): ?>
              <span class="quick-action-badge"><?= $pendingSubscriptions ?></span>
              <?php endif; ?>
            </a>

            <a href="subscriptions.php" class="quick-action-card">
              <div class="quick-action-icon">
                <ion-icon name="receipt"></ion-icon>
              </div>
              <div class="quick-action-info">
                <h3>View Reports</h3>
                <p>Analytics & insights</p>
              </div>
            </a>
          </div>
        </div>

        <!-- Performance Chart -->
        <div class="performance-chart">
          <div class="section-header">
            <h2 class="section-title">Member Growth</h2>
          </div>
          <div class="chart-container">
            <div class="chart-bars">
              <div class="chart-bar" style="--height: 65%;">
                <div class="chart-bar-fill"></div>
                <span class="chart-label">Jan</span>
              </div>
              <div class="chart-bar" style="--height: 72%;">
                <div class="chart-bar-fill"></div>
                <span class="chart-label">Feb</span>
              </div>
              <div class="chart-bar" style="--height: 68%;">
                <div class="chart-bar-fill"></div>
                <span class="chart-label">Mar</span>
              </div>
              <div class="chart-bar" style="--height: 78%;">
                <div class="chart-bar-fill"></div>
                <span class="chart-label">Apr</span>
              </div>
              <div class="chart-bar" style="--height: 85%;">
                <div class="chart-bar-fill"></div>
                <span class="chart-label">May</span>
              </div>
              <div class="chart-bar" style="--height: 92%;">
                <div class="chart-bar-fill"></div>
                <span class="chart-label">Jun</span>
              </div>
            </div>
            <div class="chart-stats">
              <div class="chart-stat">
                <span class="chart-stat-value"><?= number_format($totalMembers) ?></span>
                <span class="chart-stat-label">Total Members</span>
              </div>
              <div class="chart-stat">
                <span class="chart-stat-value">+12%</span>
                <span class="chart-stat-label">Growth Rate</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent Activity Section -->
      <div class="dashboard-row">
        <!-- Recent User Registrations -->
        <div class="section activity-section">
          <div class="section-header">
            <h2 class="section-title">
              <ion-icon name="person-add-outline"></ion-icon>
              Recent Registrations
            </h2>
            <a href="users.php" class="view-all-link">View All</a>
          </div>
          <ul class="activity-list">
            <?php if (empty($recentUsers)): ?>
              <li class="activity-item">
                <div class="activity-info">
                  <p style="color: #9ca3af;">No recent registrations</p>
                </div>
              </li>
            <?php else: ?>
              <?php foreach ($recentUsers as $user): ?>
                <li class="activity-item">
                  <div class="activity-avatar">
                    <ion-icon name="person-circle"></ion-icon>
                  </div>
                  <div class="activity-info">
                    <h4><?= htmlspecialchars($user['fullname']) ?></h4>
                    <p><?= htmlspecialchars($user['email']) ?> • <?= date('M d, Y', strtotime($user['created_at'])) ?></p>
                  </div>
                  <span class="badge badge-<?= $user['role'] === 'COACH' ? 'info' : 'success' ?>">
                    <?= htmlspecialchars($user['role']) ?>
                  </span>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>

        <!-- Recent Class Bookings -->
        <div class="section activity-section">
          <div class="section-header">
            <h2 class="section-title">
              <ion-icon name="calendar-outline"></ion-icon>
              Recent Bookings
            </h2>
            <a href="courses.php" class="view-all-link">View All</a>
          </div>
          <ul class="activity-list">
            <?php if (empty($recentBookings)): ?>
              <li class="activity-item">
                <div class="activity-info">
                  <p style="color: #9ca3af;">No recent bookings</p>
                </div>
              </li>
            <?php else: ?>
              <?php foreach ($recentBookings as $booking): ?>
                <li class="activity-item">
                  <div class="activity-avatar">
                    <ion-icon name="barbell"></ion-icon>
                  </div>
                  <div class="activity-info">
                    <h4><?= htmlspecialchars($booking['fullname']) ?></h4>
                    <p><?= htmlspecialchars($booking['course_name']) ?> • <?= date('M d, Y', strtotime($booking['created_at'])) ?></p>
                  </div>
                  <span class="badge badge-success">Booked</span>
                </li>
              <?php endforeach; ?>
            <?php endif; ?>
          </ul>
        </div>
      </div>

      <!-- Recent Subscriptions -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">
            <ion-icon name="card-outline"></ion-icon>
            Recent Subscription Approvals
          </h2>
          <a href="subscriptions.php" class="view-all-link">View All</a>
        </div>
        <ul class="activity-list">
          <?php if (empty($recentSubs)): ?>
            <li class="activity-item">
              <div class="activity-info">
                <p style="color: #9ca3af;">No recent subscriptions</p>
              </div>
            </li>
          <?php else: ?>
            <?php foreach ($recentSubs as $sub): ?>
              <li class="activity-item">
                <div class="activity-avatar">
                  <ion-icon name="card"></ion-icon>
                </div>
                <div class="activity-info">
                  <h4><?= htmlspecialchars($sub['fullname']) ?></h4>
                  <p><?= htmlspecialchars($sub['plan_name']) ?> • <?= date('M d, Y', strtotime($sub['created_at'])) ?></p>
                </div>
                <span class="badge badge-<?= $sub['status'] === 'ACTIVE' ? 'success' : ($sub['status'] === 'PENDING' ? 'warning' : 'primary') ?>">
                  <?= htmlspecialchars($sub['status']) ?>
                </span>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </main>
  </div>
</body>
</html>
