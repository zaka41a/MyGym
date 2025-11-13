<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coach Pages - Updated Successfully!</title>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #0f172a 0%, #020617 100%);
      color: #f1f5f9;
      padding: 3rem;
      line-height: 1.6;
    }
    .container {
      max-width: 900px;
      margin: 0 auto;
    }
    h1 {
      font-size: 3rem;
      font-weight: 900;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 1rem;
    }
    .subtitle {
      color: #94a3b8;
      font-size: 1.25rem;
      margin-bottom: 3rem;
    }
    .success-box {
      background: linear-gradient(135deg, rgba(16, 185, 129, 0.15), rgba(16, 185, 129, 0.05));
      border: 2px solid #10b981;
      border-radius: 16px;
      padding: 2rem;
      margin-bottom: 2rem;
    }
    .success-box h2 {
      color: #10b981;
      margin-bottom: 1rem;
      font-size: 1.5rem;
    }
    .feature-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
      margin: 2rem 0;
    }
    .feature-card {
      background: rgba(99, 102, 241, 0.1);
      border: 1px solid rgba(99, 102, 241, 0.3);
      border-radius: 12px;
      padding: 1.5rem;
      transition: all 0.3s;
    }
    .feature-card:hover {
      transform: translateY(-4px);
      border-color: #6366f1;
      box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
    }
    .feature-card h3 {
      color: #6366f1;
      margin-bottom: 0.5rem;
      font-size: 1.1rem;
    }
    .feature-card p {
      color: #94a3b8;
      font-size: 0.95rem;
    }
    .page-links {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1rem;
      margin-top: 2rem;
    }
    .page-link {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1.5rem;
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
      border: 2px solid rgba(99, 102, 241, 0.3);
      border-radius: 12px;
      text-decoration: none;
      color: #f1f5f9;
      transition: all 0.3s;
    }
    .page-link:hover {
      border-color: #6366f1;
      transform: translateX(4px);
      box-shadow: 0 4px 16px rgba(99, 102, 241, 0.3);
    }
    .page-link-icon {
      font-size: 2rem;
    }
    .page-link-text h3 {
      color: #6366f1;
      font-size: 1.1rem;
      margin-bottom: 0.25rem;
    }
    .page-link-text p {
      color: #94a3b8;
      font-size: 0.875rem;
    }
    .comparison {
      background: rgba(30, 41, 59, 0.6);
      border: 1px solid #334155;
      border-radius: 16px;
      padding: 2rem;
      margin-top: 3rem;
    }
    .comparison h2 {
      color: #f1f5f9;
      margin-bottom: 1.5rem;
    }
    .comparison-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
    }
    .comparison-col h3 {
      margin-bottom: 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid;
    }
    .comparison-col:first-child h3 {
      color: #dc2626;
      border-color: #dc2626;
    }
    .comparison-col:last-child h3 {
      color: #6366f1;
      border-color: #6366f1;
    }
    .comparison-col ul {
      list-style: none;
    }
    .comparison-col li {
      padding: 0.5rem 0;
      color: #94a3b8;
    }
    .comparison-col li::before {
      content: '✓ ';
      margin-right: 0.5rem;
      font-weight: bold;
    }
    .comparison-col:first-child li::before {
      color: #dc2626;
    }
    .comparison-col:last-child li::before {
      color: #6366f1;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🎉 Coach Pages Updated!</h1>
    <p class="subtitle">All 4 coach pages have been redesigned with a unique blue/purple theme</p>

    <div class="success-box">
      <h2>✅ What's New?</h2>
      <p>Your coach pages now feature a completely unique design that stands out from the admin pages. Here's what changed:</p>
    </div>

    <div class="feature-grid">
      <div class="feature-card">
        <h3>🎨 New Color Scheme</h3>
        <p>Blue (#6366f1) and Purple (#8b5cf6) gradients instead of red</p>
      </div>
      <div class="feature-card">
        <h3>✨ Modern Logo</h3>
        <p>Updated logo with "COACH PORTAL" subtitle in blue tones</p>
      </div>
      <div class="feature-card">
        <h3>🎯 Fixed Profile Picture</h3>
        <p>Avatar block now displays correctly with proper spacing</p>
      </div>
      <div class="feature-card">
        <h3>💎 Glassmorphism Effects</h3>
        <p>Backdrop blur and elegant card designs throughout</p>
      </div>
      <div class="feature-card">
        <h3>🌊 Smooth Animations</h3>
        <p>Hover effects, transitions, and staggered card animations</p>
      </div>
      <div class="feature-card">
        <h3>📊 Professional Stats</h3>
        <p>Beautiful stat cards with progress bars and trends</p>
      </div>
      <div class="feature-card">
        <h3>📱 Fully Responsive</h3>
        <p>Looks great on desktop, tablet, and mobile devices</p>
      </div>
      <div class="feature-card">
        <h3>♿ Better Accessibility</h3>
        <p>Improved contrast and keyboard navigation support</p>
      </div>
    </div>

    <h2 style="margin-top: 3rem; margin-bottom: 1.5rem; color: #f1f5f9;">📄 Test Your Pages</h2>

    <div class="page-links">
      <a href="/MyGym/coach/index.php" class="page-link">
        <span class="page-link-icon">📊</span>
        <div class="page-link-text">
          <h3>Dashboard</h3>
          <p>Overview & quick actions</p>
        </div>
      </a>

      <a href="/MyGym/coach/courses.php" class="page-link">
        <span class="page-link-icon">📅</span>
        <div class="page-link-text">
          <h3>My Classes</h3>
          <p>Manage your sessions</p>
        </div>
      </a>

      <a href="/MyGym/coach/members.php" class="page-link">
        <span class="page-link-icon">👥</span>
        <div class="page-link-text">
          <h3>My Members</h3>
          <p>Assign & track athletes</p>
        </div>
      </a>

      <a href="/MyGym/coach/profile.php" class="page-link">
        <span class="page-link-icon">👤</span>
        <div class="page-link-text">
          <h3>Profile</h3>
          <p>Update your info & avatar</p>
        </div>
      </a>
    </div>

    <div class="comparison">
      <h2>⚖️ Admin vs Coach Design</h2>
      <div class="comparison-grid">
        <div class="comparison-col">
          <h3>Admin Pages (Red Theme)</h3>
          <ul>
            <li>Red gradient (#dc2626)</li>
            <li>"PERFORMANCE CLUB" subtitle</li>
            <li>Sharp, corporate feel</li>
            <li>Data-focused layouts</li>
          </ul>
        </div>
        <div class="comparison-col">
          <h3>Coach Pages (Blue/Purple)</h3>
          <ul>
            <li>Blue/Purple gradient (#6366f1)</li>
            <li>"COACH PORTAL" subtitle</li>
            <li>Modern, friendly feel</li>
            <li>Action-focused layouts</li>
          </ul>
        </div>
      </div>
    </div>

    <div style="margin-top: 3rem; padding: 2rem; background: rgba(99, 102, 241, 0.1); border-radius: 12px; text-align: center;">
      <p style="color: #94a3b8; margin-bottom: 1rem;">Need to go back?</p>
      <a href="/MyGym/diagnose.php" style="color: #6366f1; text-decoration: none; font-weight: 600;">← Return to Diagnostic Tool</a>
    </div>
  </div>
</body>
</html>
