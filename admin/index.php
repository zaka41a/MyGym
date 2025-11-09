<?php
declare(strict_types=1);

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
      margin-bottom: 3rem;
    }

    .header h1 {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .header p {
      color: #9ca3af;
      font-size: 1rem;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.5rem;
      margin-bottom: 3rem;
    }

    @keyframes slideInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .stat-card {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 20px;
      padding: 1.75rem;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
      animation: slideInUp 0.6s ease-out;
    }

    .stat-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #dc2626, #ef4444);
      transform: scaleX(0);
      transform-origin: left;
      transition: transform 0.4s;
    }

    .stat-card:hover::before {
      transform: scaleX(1);
    }

    .stat-card:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(220, 38, 38, 0.5);
      transform: translateY(-8px) scale(1.02);
      box-shadow: 0 20px 40px rgba(220, 38, 38, 0.2),
                  0 0 0 1px rgba(220, 38, 38, 0.1);
    }

    .stat-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      margin-bottom: 1.5rem;
    }

    .stat-icon {
      width: 56px;
      height: 56px;
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #dc2626;
      font-size: 1.75rem;
      transition: all 0.3s;
      box-shadow: 0 8px 16px rgba(220, 38, 38, 0.2);
    }

    .stat-card:hover .stat-icon {
      transform: rotate(10deg) scale(1.1);
      box-shadow: 0 12px 24px rgba(220, 38, 38, 0.3);
    }

    .stat-value {
      font-size: 2.75rem;
      font-weight: 800;
      margin-bottom: 0.25rem;
      background: linear-gradient(135deg, #fff 0%, #f5f7fb 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }

    .stat-label {
      color: #9ca3af;
      font-size: 0.8rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .stat-trend {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.875rem;
      color: #10b981;
      margin-bottom: 1rem;
    }

    .stat-trend ion-icon {
      font-size: 1.1rem;
    }

    .stat-trend.positive {
      color: #10b981;
    }

    .stat-bar {
      height: 6px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 999px;
      overflow: hidden;
      margin-top: 1rem;
    }

    .stat-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, #dc2626, #ef4444);
      border-radius: 999px;
      transition: width 1s ease-out;
      box-shadow: 0 0 10px rgba(220, 38, 38, 0.5);
    }

    /* Recent Activity */
    .section {
      background: rgba(255, 255, 255, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
    }

    .section-header {
      display: flex;
      align-items: center;
      justify-content: between;
      margin-bottom: 1.5rem;
    }

    .section-title {
      font-size: 1.25rem;
      font-weight: 600;
    }

    .activity-list {
      list-style: none;
    }

    .activity-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem;
      background: rgba(0, 0, 0, 0.3);
      border: 1px solid rgba(255, 255, 255, 0.05);
      border-radius: 12px;
      margin-bottom: 0.75rem;
      transition: all 0.3s;
    }

    .activity-item:hover {
      background: rgba(0, 0, 0, 0.5);
      border-color: rgba(220, 38, 38, 0.3);
    }

    .activity-info h4 {
      font-size: 0.95rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }

    .activity-info p {
      font-size: 0.875rem;
      color: #9ca3af;
    }

    .badge {
      padding: 0.375rem 0.875rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .badge-success {
      background: rgba(16, 185, 129, 0.2);
      color: #10b981;
    }

    .badge-warning {
      background: rgba(245, 158, 11, 0.2);
      color: #f59e0b;
    }

    .badge-primary {
      background: rgba(220, 38, 38, 0.2);
      color: #dc2626;
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
        <h1>Welcome back, <?= htmlspecialchars($userName) ?>!</h1>
        <p>Here's what's happening with your gym today.</p>
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
            <div class="stat-bar-fill" style="width: 78%;"></div>
          </div>
        </div>

        <div class="stat-card stat-coaches">
          <div class="stat-header">
            <div>
              <div class="stat-label">Active Coaches</div>
              <div class="stat-value"><?= number_format($totalCoaches) ?></div>
            </div>
            <div class="stat-icon">
              <ion-icon name="person"></ion-icon>
            </div>
          </div>
          <div class="stat-trend">
            <ion-icon name="trending-up"></ion-icon>
            <span>+3 new coaches</span>
          </div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width: 65%;"></div>
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
            <div class="stat-bar-fill" style="width: 85%;"></div>
          </div>
        </div>

        <div class="stat-card stat-retention">
          <div class="stat-header">
            <div>
              <div class="stat-label">Retention Rate</div>
              <div class="stat-value">94%</div>
            </div>
            <div class="stat-icon">
              <ion-icon name="trophy"></ion-icon>
            </div>
          </div>
          <div class="stat-trend positive">
            <ion-icon name="checkmark-circle"></ion-icon>
            <span>Excellent performance</span>
          </div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width: 94%;"></div>
          </div>
        </div>
      </div>

      <!-- Recent Subscriptions -->
      <div class="section">
        <div class="section-header">
          <h2 class="section-title">Recent Subscriptions</h2>
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
