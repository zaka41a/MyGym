<style>
/* ========================================
   MEMBER SPACE - UNIQUE RED/WHITE DESIGN
   Modern & Bold Theme
   ======================================== */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  --member-primary: #ef4444;
  --member-secondary: #dc2626;
  --member-accent: #f87171;
  --member-dark: #7f1d1d;
  --member-darker: #450a0a;
  --member-surface: #991b1b;
  --member-border: #b91c1c;
  --member-text: #fef2f2;
  --member-text-dim: #fecaca;
  --member-success: #ffffff;
  --member-warning: #fbbf24;
  --member-danger: #ef4444;
}

body {
  font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
  color: #1e293b;
  line-height: 1.6;
  min-height: 100vh;
  position: relative;
}

.container {
  display: flex;
  min-height: 100vh;
  background: transparent;
}

/* ========================================
   SIDEBAR
   ======================================== */

.sidebar {
  width: 280px;
  background: #ffffff;
  border-right: 1px solid #e5e7eb;
  padding: 2rem 0;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  box-shadow: 4px 0 12px rgba(0, 0, 0, 0.03);
  position: relative;
}

.sidebar::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 0;
  height: 0;
  display: none;
}

.logo {
  padding: 0 1.5rem 2rem;
  border-bottom: 1px solid var(--member-border);
  margin-bottom: 2rem;
}

.logo svg {
  filter: drop-shadow(0 0 20px rgba(239, 68, 68, 0.4));
}

nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
}

.nav-menu {
  list-style: none;
  padding: 0 1rem;
}

.nav-item {
  margin-bottom: 0.5rem;
}

.nav-link {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem 1.25rem;
  color: #64748b;
  text-decoration: none;
  border-radius: 12px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-weight: 500;
  position: relative;
}

.nav-link::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 3px;
  background: linear-gradient(180deg, var(--member-primary), var(--member-secondary));
  transform: scaleY(0);
  transition: transform 0.3s;
}

.nav-link:hover {
  background: #f3f4f6;
  color: var(--member-primary);
  transform: translateX(4px);
}

.nav-link:hover::before {
  transform: scaleY(1);
}

.nav-link.active {
  background: #f9fafb;
  color: var(--member-primary);
  font-weight: 600;
}

.nav-link.active::before {
  transform: scaleY(1);
}

.nav-link ion-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.nav-link.locked {
  opacity: 0.5;
  cursor: not-allowed;
}

.nav-link.locked:hover {
  transform: none;
  background: transparent;
}

.logout-btn {
  margin: 1.5rem 1rem 0;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #dc2626, #991b1b);
  color: white;
  text-decoration: none;
  border-radius: 12px;
  display: flex;
  align-items: center;
  gap: 1rem;
  font-weight: 600;
  transition: all 0.3s;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
}

.logout-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(239, 68, 68, 0.3);
}

.logout-btn ion-icon {
  font-size: 1.5rem;
}

/* ========================================
   MAIN CONTENT
   ======================================== */

.main-content {
  flex: 1;
  padding: 2.5rem 3rem;
  overflow-y: auto;
  background: transparent;
}

/* ========================================
   HEADER
   ======================================== */

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 3rem;
  padding-bottom: 2rem;
  border-bottom: 1px solid var(--member-border);
}

.header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}

.header p {
  color: #64748b;
  font-size: 1.1rem;
}

.header-date {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.5rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  color: #1e293b;
  font-weight: 600;
}

.header-date ion-icon {
  font-size: 1.25rem;
  color: #ef4444;
}

/* ========================================
   STATS GRID
   ======================================== */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2.5rem;
  margin-bottom: 3rem;
}

.stat-card {
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 1.5rem 0;
  position: relative;
  overflow: visible;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: none;
}

.stat-card::before {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--member-primary), transparent);
  opacity: 0.3;
  transition: opacity 0.3s;
}

.stat-card:hover {
  transform: translateX(4px);
  border-color: transparent;
  box-shadow: none;
}

.stat-card:hover::before {
  opacity: 1;
}

.stat-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  background: transparent;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: var(--member-primary);
  box-shadow: none;
  border: 2px solid rgba(239, 68, 68, 0.3);
}

.stat-label {
  font-size: 0.875rem;
  color: #1e293b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
  margin-bottom: 0.75rem;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 900;
  color: #1e293b;
  line-height: 1;
  text-shadow: none;
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 0.875rem;
  color: var(--member-text-dim);
}

.stat-trend ion-icon {
  font-size: 1.125rem;
  color: var(--member-success);
}

.stat-trend.positive {
  color: var(--member-success);
}

.stat-bar {
  width: 100%;
  height: 6px;
  background: #e5e7eb;
  border-radius: 10px;
  overflow: hidden;
  margin-top: 1rem;
}

.stat-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--member-primary), var(--member-accent));
  border-radius: 10px;
  transition: width 0.8s ease;
  box-shadow: 0 0 10px rgba(239, 68, 68, 0.6);
}

/* ========================================
   SECTIONS
   ======================================== */

.section {
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 2rem 0;
  margin-bottom: 2rem;
  backdrop-filter: none;
  position: relative;
}

.section::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--member-border), transparent);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--member-border);
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #1e293b;
}

.section-title ion-icon {
  font-size: 1.75rem;
  color: var(--member-primary);
}

/* ========================================
   BUTTONS
   ======================================== */

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 0.875rem 1.75rem;
  background: linear-gradient(135deg, var(--member-primary), var(--member-secondary));
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(239, 68, 68, 0.5);
}

.btn ion-icon {
  font-size: 1.25rem;
}

.btn-primary {
  background: linear-gradient(135deg, #dc2626 0%, #ef4444 50%, #f87171 100%);
  box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
  font-size: 1.05rem;
  font-weight: 700;
  padding: 1rem 2rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  position: relative;
  overflow: hidden;
}

.btn-primary::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s;
}

.btn-primary:hover::before {
  left: 100%;
}

.btn-primary:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 12px 32px rgba(220, 38, 38, 0.6);
}

.btn-ghost {
  background: transparent;
  border: 1px solid var(--member-border);
  color: var(--member-text-dim);
  box-shadow: none;
}

.btn-ghost:hover {
  border-color: var(--member-primary);
  color: var(--member-primary);
  background: rgba(239, 68, 68, 0.1);
}

/* ========================================
   BADGES
   ======================================== */

.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid;
}

.badge-success {
  background: #f0fdf4;
  border-color: #22c55e;
  color: #16a34a;
}

.badge-warning {
  background: #fef3c7;
  border-color: #fbbf24;
  color: #d97706;
}

.badge-danger {
  background: #fef2f2;
  border-color: #ef4444;
  color: #dc2626;
}

/* ========================================
   ALERTS
   ======================================== */

.alert {
  padding: 1.25rem 1.5rem;
  border-radius: 12px;
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  font-weight: 500;
  border: 1px solid;
  animation: slideInDown 0.4s ease-out;
}

.alert ion-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.alert-success {
  background: #f0fdf4;
  border-color: #22c55e;
  color: #16a34a;
}

.alert-error {
  background: #fef2f2;
  border-color: #ef4444;
  color: #dc2626;
}

/* ========================================
   FORMS
   ======================================== */

.form-group, .input-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: #1e293b;
  font-size: 0.95rem;
}

input[type="text"],
input[type="email"],
input[type="password"],
input[type="number"],
input[type="date"],
input[type="time"],
input[type="file"],
select,
textarea {
  width: 100%;
  padding: 0.875rem 1.25rem;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  color: #1e293b;
  font-size: 1rem;
  transition: all 0.3s;
}

input:focus,
select:focus,
textarea:focus {
  outline: none;
  border-color: var(--member-primary);
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
  background: #ffffff;
}

/* ========================================
   DASHBOARD ROW
   ======================================== */

.dashboard-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2rem;
  margin-bottom: 2rem;
}

/* ========================================
   MEMBER STATS HERO (Dashboard)
   ======================================== */

.member-stats-hero {
  display: grid;
  grid-template-columns: repeat(7, auto);
  align-items: center;
  gap: 2rem;
  padding: 2.5rem 2rem;
  margin-bottom: 3rem;
  position: relative;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.member-stats-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 0;
  display: none;
}


.member-stat-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 1rem;
  transition: all 0.3s;
}

.member-stat-card:hover {
  transform: translateY(-4px);
}

.member-stat-icon {
  width: 72px;
  height: 72px;
  border-radius: 50%;
  background: transparent;
  border: 3px solid var(--stat-color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2.25rem;
  color: var(--stat-color);
  transition: all 0.3s;
}

.member-stat-card:hover .member-stat-icon {
  transform: rotate(8deg) scale(1.1);
  box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4);
}

.member-stat-content {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.member-stat-value {
  font-size: 2.25rem;
  font-weight: 900;
  color: #1e293b;
  line-height: 1;
  text-shadow: none;
}

.member-stat-value.small {
  font-size: 1.5rem;
}

.member-stat-label {
  font-size: 0.875rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 600;
}

.member-stat-subtitle {
  font-size: 0.8rem;
  color: #94a3b8;
  font-weight: 500;
}

.member-stat-divider {
  width: 2px;
  height: 80px;
  background: linear-gradient(180deg, transparent, var(--member-primary), transparent);
  opacity: 0.3;
}

/* ========================================
   QUICK ACTIONS
   ======================================== */

.quick-actions-panel {
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 2rem 0;
  position: relative;
}

.quick-actions-panel::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--member-border), transparent);
}

.quick-actions-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
}

.quick-action-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1.5rem;
  background: #f9fafb;
  border: 1px solid var(--member-border);
  border-radius: 14px;
  text-decoration: none;
  transition: all 0.3s;
}

.quick-action-card:hover {
  background: #f3f4f6;
  border-color: var(--member-primary);
  transform: translateX(4px);
}

.quick-action-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: #ffffff;
  border: 2px solid var(--member-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--member-primary);
}

.quick-action-info h3 {
  font-size: 1rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.25rem;
}

.quick-action-info p {
  font-size: 0.875rem;
  color: #64748b;
}

/* ========================================
   ACTIVITY LIST
   ======================================== */

.activity-list {
  list-style: none;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem 0;
  background: transparent;
  border: none;
  border-radius: 0;
  margin-bottom: 0;
  transition: all 0.3s;
  position: relative;
  border-bottom: 1px solid rgba(239, 68, 68, 0.1);
}

.activity-item:last-child {
  border-bottom: none;
}

.activity-item:hover {
  background: #f9fafb;
  padding-left: 1rem;
}

.activity-item:hover .activity-icon {
  transform: scale(1.1) rotate(5deg);
  box-shadow: 0 4px 16px rgba(239, 68, 68, 0.4);
}

.activity-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: transparent;
  border: 2px solid var(--member-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--member-primary);
  flex-shrink: 0;
  transition: all 0.3s;
}

.activity-info {
  flex: 1;
}

.activity-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 0.5rem;
}

.activity-meta {
  font-size: 0.875rem;
  color: #64748b;
  margin-bottom: 0.25rem;
}

/* ========================================
   RESPONSIVE
   ======================================== */

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .dashboard-row {
    grid-template-columns: 1fr;
  }

  .quick-actions-grid {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .container {
    flex-direction: column;
  }

  .sidebar {
    width: 100%;
    height: auto;
    position: relative;
  }

  .main-content {
    padding: 2rem 1.5rem;
  }

  .header {
    flex-direction: column;
    gap: 1.5rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
  }

  .header h1 {
    font-size: 2rem;
  }
}

/* ========================================
   ANIMATIONS
   ======================================== */

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.stats-grid > * {
  animation: slideUp 0.6s ease-out;
  animation-fill-mode: both;
}

.stats-grid > *:nth-child(1) { animation-delay: 0.1s; }
.stats-grid > *:nth-child(2) { animation-delay: 0.2s; }
.stats-grid > *:nth-child(3) { animation-delay: 0.3s; }
.stats-grid > *:nth-child(4) { animation-delay: 0.4s; }

/* ========================================
   PROFILE HERO SECTION
   ======================================== */

.profile-hero {
  display: flex;
  align-items: center;
  gap: 3rem;
  padding: 3rem 0;
  margin-bottom: 3rem;
  position: relative;
}

.profile-hero::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--member-border), transparent);
}

.profile-hero-avatar {
  position: relative;
  flex-shrink: 0;
}

.profile-hero-avatar img {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid var(--member-primary);
  box-shadow: 0 12px 32px rgba(239, 68, 68, 0.4);
}

.avatar-badge {
  position: absolute;
  bottom: 8px;
  right: 8px;
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, var(--member-primary), var(--member-secondary));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 700;
  color: white;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.5);
  border: 3px solid var(--member-darker);
}

.profile-hero-info {
  flex: 1;
}

.profile-hero-info h2 {
  font-size: 2.25rem;
  font-weight: 800;
  color: #1e293b;
  margin-bottom: 0.5rem;
  line-height: 1.2;
  text-shadow: none;
}

.profile-role {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--member-primary);
  font-weight: 600;
  font-size: 1.1rem;
  margin-bottom: 1.5rem;
}

.profile-role ion-icon {
  font-size: 1.25rem;
}

.profile-stats-inline {
  display: flex;
  gap: 2rem;
}

.stat-inline {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #64748b;
  font-size: 1rem;
}

.stat-inline ion-icon {
  font-size: 1.25rem;
  color: var(--member-primary);
}

.profile-completion-ring {
  position: relative;
  flex-shrink: 0;
}

.ring-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.ring-number {
  display: block;
  font-size: 1.75rem;
  font-weight: 800;
  color: #1e293b;
  line-height: 1;
  margin-bottom: 0.25rem;
  text-shadow: none;
}

.ring-label {
  display: block;
  font-size: 0.75rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* ========================================
   FLUID SECTIONS - No Borders
   ======================================== */

.fluid-section {
  margin-bottom: 3rem;
}

.section-header-minimal {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 2rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

.section-header-minimal ion-icon {
  font-size: 1.75rem;
  color: var(--member-primary);
}

.section-header-minimal h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1e293b;
  margin: 0;
  text-shadow: none;
}

/* ========================================
   UPLOAD AREA - Drag & Drop Style
   ======================================== */

.upload-form {
  display: flex;
  gap: 1.5rem;
  align-items: flex-end;
}

.upload-area {
  flex: 1;
  position: relative;
  padding: 3rem 2rem;
  border: 2px dashed rgba(239, 68, 68, 0.3);
  border-radius: 16px;
  text-align: center;
  background: rgba(239, 68, 68, 0.02);
  transition: all 0.3s;
  cursor: pointer;
}

.upload-area:hover {
  border-color: var(--member-primary);
  background: rgba(239, 68, 68, 0.05);
}

.upload-area ion-icon {
  font-size: 3rem;
  color: var(--member-primary);
  margin-bottom: 1rem;
}

.upload-area p {
  color: #1e293b;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.upload-area input[type="file"] {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  opacity: 0;
  cursor: pointer;
}

.upload-hint {
  display: block;
  font-size: 0.875rem;
  color: #1e293b;
}

.btn-upload {
  padding: 1rem 2rem;
  min-width: 180px;
}

/* ========================================
   MODERN FORM - Clean Input Style
   ======================================== */

.modern-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.input-group {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.input-group label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: #1e293b;
  font-weight: 600;
  font-size: 0.95rem;
}

.input-group label ion-icon {
  font-size: 1.25rem;
  color: var(--member-primary);
}

.optional-badge {
  margin-left: auto;
  padding: 0.25rem 0.75rem;
  background: #f3f4f6;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  font-size: 0.75rem;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.input-group input {
  padding: 1rem 1.25rem;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 12px;
  color: #1e293b;
  font-size: 1rem;
  transition: all 0.3s;
}

.input-group input:focus {
  outline: none;
  border-color: var(--member-primary);
  background: #ffffff;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.input-group input::placeholder {
  color: #e5e5e5;
  opacity: 0.6;
}

.btn-primary {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1.25rem 2.5rem;
  background: linear-gradient(135deg, var(--member-primary), var(--member-secondary));
  color: white;
  font-weight: 700;
  font-size: 1.05rem;
  border: none;
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.3s;
  box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4);
}

.btn-primary ion-icon {
  font-size: 1.25rem;
}

/* Responsive profile */
@media (max-width: 1024px) {
  .profile-hero {
    flex-direction: column;
    text-align: center;
  }

  .profile-stats-inline {
    justify-content: center;
  }

  .upload-form {
    flex-direction: column;
  }

  .btn-upload {
    width: 100%;
  }
}

@media (max-width: 768px) {
  .profile-hero-info h2 {
    font-size: 1.75rem;
  }

  .profile-stats-inline {
    flex-direction: column;
    gap: 1rem;
  }
}

/* ========================================
   WEEKLY FOCUS CHART
   ======================================== */

.performance-chart {
  flex: 1;
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 2rem 0;
  position: relative;
}

.performance-chart::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--member-border), transparent);
}

.chart-container {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.chart-bars {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  height: 200px;
  gap: 0.75rem;
  padding: 1rem 0;
  border-bottom: 2px solid rgba(239, 68, 68, 0.2);
}

.chart-bar {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  height: 100%;
  justify-content: flex-end;
}

.chart-bar-fill {
  width: 100%;
  height: var(--height);
  background: linear-gradient(180deg, #ef4444, #dc2626);
  border-radius: 8px 8px 0 0;
  transition: all 0.3s;
  box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
}

.chart-bar:hover .chart-bar-fill {
  background: linear-gradient(180deg, #f87171, #ef4444);
  transform: scaleY(1.05);
  box-shadow: 0 8px 20px rgba(239, 68, 68, 0.5);
}

.chart-label {
  font-size: 0.75rem;
  color: #cbd5e1;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.chart-stats {
  display: flex;
  justify-content: space-around;
  gap: 2rem;
  padding-top: 1.5rem;
}

.chart-stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  text-align: center;
}

.chart-stat-value {
  font-size: 2rem;
  font-weight: 900;
  color: #1e293b;
  text-shadow: none;
}

.chart-stat-label {
  font-size: 0.875rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 600;
}

/* ========================================
   RESPONSIVE - MEMBER SPECIAL DESIGNS
   ======================================== */

@media (max-width: 1200px) {
  .member-stats-hero {
    grid-template-columns: repeat(3, auto);
    gap: 1.5rem;
  }
  
  .member-stat-divider:nth-child(6) {
    display: none;
  }
}

@media (max-width: 768px) {
  .member-stats-hero {
    grid-template-columns: 1fr;
    gap: 2rem;
  }
  
  .member-stat-divider {
    display: none;
  }
  
  .member-stat-card {
    flex-direction: row;
    text-align: left;
    justify-content: flex-start;
  }
  
  .member-stat-content {
    align-items: flex-start;
  }
  
  .chart-bars {
    height: 150px;
  }
  
  .chart-stats {
    flex-direction: column;
    gap: 1rem;
  }
}

/* ========================================
   CLASSES OVERVIEW HERO (courses.php)
   ======================================== */

.classes-overview-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  padding: 2.5rem 2rem;
  margin-bottom: 3rem;
  position: relative;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.classes-overview-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 0;
  display: none;
}

.classes-hero-stat {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex: 1;
}

.classes-hero-icon {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: transparent;
  border: 3px solid var(--icon-color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: var(--icon-color);
  transition: all 0.3s;
  flex-shrink: 0;
}

.classes-hero-stat:hover .classes-hero-icon {
  transform: rotate(-10deg) scale(1.1);
  box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4);
}

.classes-hero-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.classes-hero-value {
  font-size: 2.5rem;
  font-weight: 900;
  color: #1e293b;
  line-height: 1;
  text-shadow: none;
}

.classes-hero-label {
  font-size: 0.875rem;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 600;
}

.classes-hero-desc {
  font-size: 0.75rem;
  color: #94a3b8;
}

.classes-hero-divider {
  width: 2px;
  height: 70px;
  background: linear-gradient(180deg, transparent, var(--member-primary), transparent);
  opacity: 0.3;
  flex-shrink: 0;
}

/* ========================================
   SUBSCRIPTION STATUS HERO (subscribe.php)
   ======================================== */

.subscription-status-hero {
  display: flex;
  align-items: center;
  gap: 3rem;
  padding: 3rem 2rem;
  margin-bottom: 3rem;
  position: relative;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
  border: 1px solid #e5e7eb;
}

.subscription-status-hero::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 0;
  display: none;
}

.subscription-hero-main {
  display: flex;
  align-items: center;
  gap: 2rem;
  flex: 1;
}

.subscription-hero-icon {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background: #ffffff;
  border: 3px solid var(--member-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  color: var(--member-primary);
  flex-shrink: 0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.subscription-hero-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.subscription-hero-plan {
  font-size: 3rem;
  font-weight: 900;
  color: #1e293b;
  line-height: 1;
  text-shadow: none;
}

.subscription-hero-label {
  font-size: 0.875rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 600;
}

.subscription-hero-status {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  width: fit-content;
}

.subscription-hero-status.active {
  background: rgba(16, 185, 129, 0.15);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.subscription-hero-status.inactive {
  background: rgba(239, 68, 68, 0.15);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.3);
}

.subscription-hero-divider {
  width: 2px;
  height: 100px;
  background: linear-gradient(180deg, transparent, var(--member-primary), transparent);
  opacity: 0.3;
  flex-shrink: 0;
}

.subscription-hero-stats {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.subscription-hero-stat {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.subscription-hero-stat ion-icon {
  font-size: 2rem;
  color: var(--member-primary);
}

.subscription-stat-value {
  font-size: 1.5rem;
  font-weight: 900;
  color: #1e293b;
  line-height: 1;
  text-shadow: none;
}

.subscription-stat-label {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

@media (max-width: 768px) {
  .classes-overview-hero {
    flex-direction: column;
    align-items: stretch;
  }
  
  .classes-hero-divider {
    display: none;
  }
  
  .subscription-status-hero {
    flex-direction: column;
    align-items: stretch;
  }
  
  .subscription-hero-divider {
    display: none;
  }
  
  .subscription-hero-main {
    flex-direction: column;
    text-align: center;
  }
  
  .subscription-hero-content {
    align-items: center;
  }
}
</style>
