<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Member Theme - Red & White</title>
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
      background: linear-gradient(135deg, #ef4444, #dc2626, #f87171);
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
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(220, 38, 38, 0.1));
      border: 2px solid rgba(239, 68, 68, 0.3);
      border-radius: 20px;
      padding: 3rem;
      margin-bottom: 3rem;
      text-align: center;
    }
    .success-box h2 {
      color: #ef4444;
      margin-bottom: 1.5rem;
      font-size: 2rem;
    }
    .success-box p {
      font-size: 1.2rem;
      color: #cbd5e1;
      line-height: 1.8;
    }
    .comparison {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3rem;
      margin: 4rem 0;
    }
    .theme-card {
      background: rgba(30, 30, 30, 0.5);
      border-radius: 20px;
      padding: 3rem;
      border: 2px solid rgba(255, 255, 255, 0.1);
    }
    .theme-card h3 {
      font-size: 1.8rem;
      margin-bottom: 2rem;
      text-align: center;
    }
    .theme-card.old h3 {
      color: #10b981;
    }
    .theme-card.new h3 {
      color: #ef4444;
    }
    .color-sample {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem;
      margin: 0.75rem 0;
      background: rgba(255, 255, 255, 0.05);
      border-radius: 12px;
    }
    .color-box {
      width: 60px;
      height: 60px;
      border-radius: 12px;
      border: 2px solid rgba(255, 255, 255, 0.2);
    }
    .color-info {
      flex: 1;
    }
    .color-name {
      font-weight: 700;
      font-size: 1.1rem;
      margin-bottom: 0.25rem;
    }
    .color-hex {
      color: #94a3b8;
      font-family: 'Courier New', monospace;
      font-size: 0.9rem;
    }
    .features {
      margin-top: 4rem;
      padding: 3rem;
      background: linear-gradient(135deg, rgba(239, 68, 68, 0.15), rgba(220, 38, 38, 0.15));
      border-radius: 20px;
    }
    .features h2 {
      color: #ef4444;
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
      color: #ef4444;
      font-size: 1.2rem;
      margin-bottom: 0.5rem;
    }
    .feature-item p {
      color: #94a3b8;
      font-size: 0.95rem;
    }
    .btn-view {
      display: inline-block;
      padding: 1rem 2rem;
      background: linear-gradient(135deg, #ef4444, #dc2626);
      color: white;
      text-decoration: none;
      border-radius: 12px;
      font-weight: 700;
      transition: all 0.3s;
      margin: 2rem auto;
      display: block;
      width: fit-content;
    }
    .btn-view:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
    }
  </style>
</head>
<body>
  <div class="container">
    <h1>🔴 Member Theme Updated!</h1>
    <p class="subtitle">From Green/Cyan to Bold Red & White</p>

    <div class="success-box">
      <h2>✨ Theme Transformation Complete</h2>
      <p>
        L'espace membre utilise maintenant un thème rouge et blanc audacieux et professionnel.
        Toutes les couleurs vertes/cyan ont été remplacées par des rouges élégants avec du texte blanc
        pour une excellente lisibilité.
      </p>
    </div>

    <div class="comparison">
      <div class="theme-card old">
        <h3>❌ Avant - Vert/Cyan</h3>
        <div class="color-sample">
          <div class="color-box" style="background: linear-gradient(135deg, #10b981, #059669);"></div>
          <div class="color-info">
            <div class="color-name">Primary Green</div>
            <div class="color-hex">#10b981 → #059669</div>
          </div>
        </div>
        <div class="color-sample">
          <div class="color-box" style="background: #14b8a6;"></div>
          <div class="color-info">
            <div class="color-name">Secondary Cyan</div>
            <div class="color-hex">#14b8a6</div>
          </div>
        </div>
        <div class="color-sample">
          <div class="color-box" style="background: #064e3b;"></div>
          <div class="color-info">
            <div class="color-name">Dark Green</div>
            <div class="color-hex">#064e3b</div>
          </div>
        </div>
      </div>

      <div class="theme-card new">
        <h3>✅ Après - Rouge/Blanc</h3>
        <div class="color-sample">
          <div class="color-box" style="background: linear-gradient(135deg, #ef4444, #dc2626);"></div>
          <div class="color-info">
            <div class="color-name">Primary Red</div>
            <div class="color-hex">#ef4444 → #dc2626</div>
          </div>
        </div>
        <div class="color-sample">
          <div class="color-box" style="background: #f87171;"></div>
          <div class="color-info">
            <div class="color-name">Light Red</div>
            <div class="color-hex">#f87171</div>
          </div>
        </div>
        <div class="color-sample">
          <div class="color-box" style="background: #7f1d1d;"></div>
          <div class="color-info">
            <div class="color-name">Dark Red</div>
            <div class="color-hex">#7f1d1d</div>
          </div>
        </div>
      </div>
    </div>

    <div class="features">
      <h2>🎨 Changements Appliqués</h2>
      <div class="features-grid">
        <div class="feature-item">
          <div class="icon">🎨</div>
          <h4>Variables CSS</h4>
          <p>Toutes les variables --member-* mises à jour vers rouge</p>
        </div>
        <div class="feature-item">
          <div class="icon">🖼️</div>
          <h4>Logos SVG</h4>
          <p>Gradients dans tous les logos changés en rouge</p>
        </div>
        <div class="feature-item">
          <div class="icon">🎯</div>
          <h4>Boutons</h4>
          <p>Logout button avec gradient rouge foncé</p>
        </div>
        <div class="feature-item">
          <div class="icon">🔴</div>
          <h4>Sidebar</h4>
          <p>Background gradient rouge foncé (#991b1b → #7f1d1d)</p>
        </div>
        <div class="feature-item">
          <div class="icon">⚪</div>
          <h4>Texte Blanc</h4>
          <p>Texte principal en blanc pur (#ffffff) pour contraste</p>
        </div>
        <div class="feature-item">
          <div class="icon">💍</div>
          <h4>Progress Rings</h4>
          <p>SVG rings avec gradients rouge dans profile.php</p>
        </div>
      </div>
    </div>

    <a href="/MyGym/member/index.php" class="btn-view">
      Voir l'Espace Member avec le Nouveau Thème Rouge →
    </a>

    <div style="text-align: center; margin-top: 4rem; padding: 2rem;">
      <p style="color: #94a3b8; font-size: 1.1rem;">
        <strong>Pages mises à jour:</strong><br>
        member/index.php • member/courses.php • member/profile.php • member/subscribe.php
      </p>
      <p style="color: #94a3b8; font-size: 0.95rem; margin-top: 1rem;">
        Fichier de styles: shared/member-styles.php
      </p>
    </div>
  </div>
</body>
</html>
