<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Special Designs - MyGym Coach Pages</title>
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
      max-width: 1200px;
      margin: 0 auto;
    }
    h1 {
      font-size: 3.5rem;
      font-weight: 900;
      background: linear-gradient(135deg, #6366f1, #8b5cf6, #14b8a6);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      background-clip: text;
      margin-bottom: 1rem;
      text-align: center;
    }
    .subtitle {
      color: #94a3b8;
      font-size: 1.5rem;
      margin-bottom: 4rem;
      text-align: center;
    }
    .success-box {
      background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
      border: 2px solid rgba(99, 102, 241, 0.3);
      border-radius: 20px;
      padding: 3rem;
      margin-bottom: 3rem;
      text-align: center;
    }
    .success-box h2 {
      color: #6366f1;
      margin-bottom: 1.5rem;
      font-size: 2rem;
    }
    .success-box p {
      font-size: 1.2rem;
      color: #cbd5e1;
      line-height: 1.8;
    }
    .design-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 3rem;
      margin: 4rem 0;
    }
    .design-card {
      background: rgba(99, 102, 241, 0.05);
      border: 2px solid rgba(99, 102, 241, 0.2);
      border-radius: 20px;
      padding: 3rem;
      transition: all 0.3s;
    }
    .design-card:hover {
      transform: translateY(-8px);
      border-color: #6366f1;
      box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);
    }
    .design-card h3 {
      color: #6366f1;
      font-size: 1.8rem;
      margin-bottom: 1rem;
      display: flex;
      align-items: center;
      gap: 1rem;
    }
    .design-card .icon {
      font-size: 2.5rem;
    }
    .design-card p {
      color: #cbd5e1;
      font-size: 1.1rem;
      line-height: 1.8;
      margin-bottom: 1.5rem;
    }
    .design-card ul {
      list-style: none;
      padding: 0;
      margin: 1.5rem 0;
    }
    .design-card li {
      padding: 0.75rem 0;
      color: #94a3b8;
      border-bottom: 1px solid rgba(99, 102, 241, 0.1);
    }
    .design-card li::before {
      content: '✨';
      margin-right: 0.75rem;
    }
    .btn-view {
      display: inline-block;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: white;
      text-decoration: none;
      border-radius: 12px;
      font-weight: 700;
      transition: all 0.3s;
      margin-top: 1rem;
    }
    .btn-view:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
    }
    .features {
      margin-top: 4rem;
      padding: 3rem;
      background: linear-gradient(135deg, rgba(139, 92, 246, 0.1), rgba(99, 102, 241, 0.1));
      border-radius: 20px;
    }
    .features h2 {
      color: #8b5cf6;
      font-size: 2rem;
      margin-bottom: 2rem;
      text-align: center;
    }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 2rem;
    }
    .feature-item {
      text-align: center;
      padding: 2rem;
    }
    .feature-item .icon {
      font-size: 3rem;
      margin-bottom: 1rem;
    }
    .feature-item h4 {
      color: #6366f1;
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
    }
    .feature-item p {
      color: #94a3b8;
      font-size: 0.95rem;
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>✨ Special Hero Designs</h1>
    <p class="subtitle">Unique, frameless layouts for Coach pages</p>

    <div class="success-box">
      <h2>🎨 Mission Accomplished!</h2>
      <p>
        Created two completely unique hero sections with special designs for each coach page.
        No rectangular frames, just clean horizontal layouts with colored icons, SVG rings,
        and smooth animations. Professional and modern!
      </p>
    </div>

    <div class="design-grid">
      <div class="design-card">
        <h3>
          <span class="icon">📅</span>
          Schedule Hero
        </h3>
        <p>
          <strong>For: coach/courses.php</strong><br>
          Horizontal layout displaying 4 key scheduling metrics with color-coded circular icons.
        </p>
        <ul>
          <li>Blue icon for Upcoming sessions</li>
          <li>Purple icon for Completed sessions</li>
          <li>Pink icon for Capacity Utilization %</li>
          <li>Green icon for Available Seats</li>
          <li>Vertical gradient dividers between stats</li>
          <li>Icons scale and rotate on hover</li>
        </ul>
        <a href="/MyGym/coach/courses.php" class="btn-view">View Schedule Hero →</a>
      </div>

      <div class="design-card">
        <h3>
          <span class="icon">👥</span>
          Capacity Hero
        </h3>
        <p>
          <strong>For: coach/members.php</strong><br>
          Features a large SVG progress ring showing member capacity (X/5) with detailed stats.
        </p>
        <ul>
          <li>Animated SVG ring with blue-purple gradient</li>
          <li>Large number display (current/max)</li>
          <li>Available Slots with briefcase icon</li>
          <li>Eligible Pool (PLUS/PRO) with medal icon</li>
          <li>Visible Prospects with eye icon</li>
          <li>Hover slides items to the right</li>
        </ul>
        <a href="/MyGym/coach/members.php" class="btn-view">View Capacity Hero →</a>
      </div>
    </div>

    <div class="features">
      <h2>🚀 Design Features</h2>
      <div class="features-grid">
        <div class="feature-item">
          <div class="icon">🎯</div>
          <h4>No Frames</h4>
          <p>Transparent backgrounds, only subtle gradient lines at the bottom</p>
        </div>
        <div class="feature-item">
          <div class="icon">🎨</div>
          <h4>Unique Layouts</h4>
          <p>Each page has its own special hero design tailored to its purpose</p>
        </div>
        <div class="feature-item">
          <div class="icon">⚡</div>
          <h4>Smooth Animations</h4>
          <p>Scale, rotate, and slide effects on hover for premium feel</p>
        </div>
        <div class="feature-item">
          <div class="icon">🎭</div>
          <h4>Color Coded</h4>
          <p>Different colors for each metric to improve visual hierarchy</p>
        </div>
        <div class="feature-item">
          <div class="icon">📱</div>
          <h4>Responsive</h4>
          <p>Stacks vertically on mobile, hides dividers on tablets</p>
        </div>
        <div class="feature-item">
          <div class="icon">💎</div>
          <h4>SVG Graphics</h4>
          <p>Capacity ring uses SVG with animated stroke-dashoffset</p>
        </div>
      </div>
    </div>

    <div style="text-align: center; margin-top: 4rem; padding: 2rem;">
      <p style="color: #94a3b8; font-size: 1.1rem;">
        <a href="/MyGym/test_no_frames.php" style="color: #6366f1; text-decoration: none; font-weight: 700;">
          ← Back to Frameless Design Overview
        </a>
      </p>
    </div>
  </div>
</body>
</html>
