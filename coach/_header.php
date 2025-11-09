<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym — Coach</title>
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
    table { width: 100%; border-collapse: collapse; }
    thead td {
      font-weight: 600; color: #9ca3af; font-size: 0.85rem; text-transform: uppercase;
      letter-spacing: 0.05em; padding-bottom: 12px; border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    tbody tr { border-bottom: 1px solid rgba(255, 255, 255, 0.05); transition: all 0.2s; }
    tbody tr:hover { background: rgba(220, 38, 38, 0.05); }
    td { padding: 1rem 0.75rem; vertical-align: middle; }
    @media (max-width: 991px) { .sidebar { width: 0; opacity: 0; } .main-content { margin-left: 0; } }
  </style>
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <div class="logo">
        <div class="logo-icon"><ion-icon name="barbell"></ion-icon></div>
        <div class="logo-text"><h1>MyGym</h1><p>Coach Panel</p></div>
      </div>
      <nav>
        <ul class="nav-menu">
          <li class="nav-item"><a href="index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>"><ion-icon name="grid"></ion-icon><span>Dashboard</span></a></li>
          <li class="nav-item"><a href="courses.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'courses.php' ? 'active' : '' ?>"><ion-icon name="calendar"></ion-icon><span>My Classes</span></a></li>
          <li class="nav-item"><a href="members.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'members.php' ? 'active' : '' ?>"><ion-icon name="people"></ion-icon><span>My Members</span></a></li>
          <li class="nav-item"><a href="profile.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>"><ion-icon name="person-circle"></ion-icon><span>Profile</span></a></li>
        </ul>
        <a href="/MyGym/backend/logout.php" class="logout-btn"><ion-icon name="log-out"></ion-icon><span>Logout</span></a>
      </nav>
    </aside>
    <main class="main-content">
