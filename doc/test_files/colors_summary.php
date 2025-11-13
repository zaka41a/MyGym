<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MyGym - Espaces Redesignés avec Couleurs Uniques</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: #0a0a0a;
      color: #f1f5f9;
      padding: 3rem;
      line-height: 1.6;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    h1 {
      font-size: 3rem;
      font-weight: 900;
      text-align: center;
      margin-bottom: 1rem;
      background: linear-gradient(135deg, #dc2626, #6366f1, #10b981);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
    }
    .subtitle {
      text-align: center;
      color: #94a3b8;
      font-size: 1.25rem;
      margin-bottom: 4rem;
    }
    .spaces-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
      margin-bottom: 3rem;
    }
    .space-card {
      padding: 2.5rem;
      border-radius: 20px;
      border: 2px solid;
      position: relative;
      overflow: hidden;
    }
    .space-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
    }
    .space-card.admin {
      background: linear-gradient(135deg, rgba(220, 38, 38, 0.1), rgba(153, 27, 27, 0.1));
      border-color: #dc2626;
    }
    .space-card.admin::before {
      background: linear-gradient(90deg, #dc2626, #ef4444);
    }
    .space-card.coach {
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
      border-color: #6366f1;
    }
    .space-card.coach::before {
      background: linear-gradient(90deg, #6366f1, #8b5cf6);
    }
    .space-card.member {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(20, 184, 166, 0.1));
      border-color: #10b981;
    }
    .space-card.member::before {
      background: linear-gradient(90deg, #10b981, #14b8a6);
    }
    .space-icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }
    .space-title {
      font-size: 1.75rem;
      font-weight: 800;
      margin-bottom: 0.5rem;
    }
    .space-card.admin .space-title { color: #dc2626; }
    .space-card.coach .space-title { color: #6366f1; }
    .space-card.member .space-title { color: #10b981; }
    .space-subtitle {
      color: #94a3b8;
      margin-bottom: 2rem;
    }
    .color-list {
      list-style: none;
    }
    .color-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem 0;
      font-size: 0.95rem;
    }
    .color-swatch {
      width: 32px;
      height: 32px;
      border-radius: 8px;
      border: 2px solid rgba(255, 255, 255, 0.2);
      flex-shrink: 0;
    }
    .color-label {
      font-weight: 600;
      color: #e5e7eb;
      min-width: 90px;
    }
    .color-value {
      color: #94a3b8;
      font-family: 'Monaco', 'Courier New', monospace;
      font-size: 0.875rem;
    }
    .pages-section {
      margin-top: 4rem;
    }
    .pages-section h2 {
      font-size: 2rem;
      font-weight: 800;
      margin-bottom: 2rem;
      text-align: center;
      color: #f1f5f9;
    }
    .pages-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
    }
    .pages-col h3 {
      font-size: 1.25rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      padding-bottom: 0.75rem;
      border-bottom: 3px solid;
    }
    .pages-col.admin h3 {
      color: #dc2626;
      border-color: #dc2626;
    }
    .pages-col.coach h3 {
      color: #6366f1;
      border-color: #6366f1;
    }
    .pages-col.member h3 {
      color: #10b981;
      border-color: #10b981;
    }
    .page-links {
      list-style: none;
    }
    .page-link {
      display: block;
      padding: 0.875rem 1.25rem;
      margin-bottom: 0.5rem;
      background: rgba(255, 255, 255, 0.03);
      border-radius: 10px;
      text-decoration: none;
      color: #e5e7eb;
      transition: all 0.3s;
      border: 1px solid transparent;
    }
    .pages-col.admin .page-link:hover {
      background: rgba(220, 38, 38, 0.1);
      border-color: #dc2626;
      color: #dc2626;
      transform: translateX(4px);
    }
    .pages-col.coach .page-link:hover {
      background: rgba(99, 102, 241, 0.1);
      border-color: #6366f1;
      color: #6366f1;
      transform: translateX(4px);
    }
    .pages-col.member .page-link:hover {
      background: rgba(16, 185, 129, 0.1);
      border-color: #10b981;
      color: #10b981;
      transform: translateX(4px);
    }
    @media (max-width: 1024px) {
      .spaces-grid, .pages-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🎨 MyGym - Couleurs Uniques par Espace</h1>
    <p class="subtitle">Chaque espace a maintenant son identité visuelle distincte</p>

    <div class="spaces-grid">
      <!-- Admin Space -->
      <div class="space-card admin">
        <div class="space-icon">🔴</div>
        <h3 class="space-title">Admin</h3>
        <p class="space-subtitle">Corporate & Data-Focused</p>
        <ul class="color-list">
          <li class="color-item">
            <div class="color-swatch" style="background: #dc2626;"></div>
            <span class="color-label">Primary:</span>
            <span class="color-value">#dc2626</span>
          </li>
          <li class="color-item">
            <div class="color-swatch" style="background: #ef4444;"></div>
            <span class="color-label">Secondary:</span>
            <span class="color-value">#ef4444</span>
          </li>
          <li class="color-item">
            <div class="color-swatch" style="background: #991b1b;"></div>
            <span class="color-label">Dark:</span>
            <span class="color-value">#991b1b</span>
          </li>
        </ul>
      </div>

      <!-- Coach Space -->
      <div class="space-card coach">
        <div class="space-icon">🔵</div>
        <h3 class="space-title">Coach</h3>
        <p class="space-subtitle">Modern & Professional</p>
        <ul class="color-list">
          <li class="color-item">
            <div class="color-swatch" style="background: #6366f1;"></div>
            <span class="color-label">Primary:</span>
            <span class="color-value">#6366f1</span>
          </li>
          <li class="color-item">
            <div class="color-swatch" style="background: #8b5cf6;"></div>
            <span class="color-label">Secondary:</span>
            <span class="color-value">#8b5cf6</span>
          </li>
          <li class="color-item">
            <div class="color-swatch" style="background: #4f46e5;"></div>
            <span class="color-label">Dark:</span>
            <span class="color-value">#4f46e5</span>
          </li>
        </ul>
      </div>

      <!-- Member Space -->
      <div class="space-card member">
        <div class="space-icon">🟢</div>
        <h3 class="space-title">Member</h3>
        <p class="space-subtitle">Athletic & Energetic</p>
        <ul class="color-list">
          <li class="color-item">
            <div class="color-swatch" style="background: #10b981;"></div>
            <span class="color-label">Primary:</span>
            <span class="color-value">#10b981</span>
          </li>
          <li class="color-item">
            <div class="color-swatch" style="background: #14b8a6;"></div>
            <span class="color-label">Secondary:</span>
            <span class="color-value">#14b8a6</span>
          </li>
          <li class="color-item">
            <div class="color-swatch" style="background: #064e3b;"></div>
            <span class="color-label">Dark:</span>
            <span class="color-value">#064e3b</span>
          </li>
        </ul>
      </div>
    </div>

    <div class="pages-section">
      <h2>📄 Pages Disponibles</h2>
      <div class="pages-grid">
        <!-- Admin Pages -->
        <div class="pages-col admin">
          <h3>🔴 Admin (Rouge)</h3>
          <ul class="page-links">
            <li><a href="/MyGym/admin/" class="page-link">Dashboard</a></li>
            <li><a href="/MyGym/admin/users.php" class="page-link">Users</a></li>
            <li><a href="/MyGym/admin/courses.php" class="page-link">Courses</a></li>
            <li><a href="/MyGym/admin/subscriptions.php" class="page-link">Subscriptions</a></li>
          </ul>
        </div>

        <!-- Coach Pages -->
        <div class="pages-col coach">
          <h3>🔵 Coach (Bleu/Violet)</h3>
          <ul class="page-links">
            <li><a href="/MyGym/coach/" class="page-link">Dashboard</a></li>
            <li><a href="/MyGym/coach/courses.php" class="page-link">My Classes</a></li>
            <li><a href="/MyGym/coach/members.php" class="page-link">My Members</a></li>
            <li><a href="/MyGym/coach/profile.php" class="page-link">Profile ✨</a></li>
          </ul>
        </div>

        <!-- Member Pages -->
        <div class="pages-col member">
          <h3>🟢 Member (Vert/Cyan)</h3>
          <ul class="page-links">
            <li><a href="/MyGym/member/" class="page-link">Dashboard</a></li>
            <li><a href="/MyGym/member/courses.php" class="page-link">My Classes</a></li>
            <li><a href="/MyGym/member/subscribe.php" class="page-link">Subscription</a></li>
            <li><a href="/MyGym/member/profile.php" class="page-link">Profile</a></li>
          </ul>
        </div>
      </div>
    </div>

    <div style="margin-top: 4rem; padding: 2rem; background: rgba(99, 102, 241, 0.1); border: 2px solid #6366f1; border-radius: 16px; text-align: center;">
      <h3 style="color: #6366f1; margin-bottom: 1rem;">✨ Design Unique pour Chaque Espace</h3>
      <p style="color: #94a3b8; margin-bottom: 1.5rem;">
        Admin (Rouge) • Coach (Bleu/Violet) • Member (Vert/Cyan)
      </p>
      <p style="color: #94a3b8; font-size: 0.95rem;">
        Chaque espace a maintenant sa propre identité visuelle avec des couleurs, gradients et styles distincts.
      </p>
    </div>
  </div>
</body>
</html>
