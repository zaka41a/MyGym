<style>
/* ========================================
   COACH DASHBOARD - UNIQUE PROFESSIONAL DESIGN
   Dark theme with blue/purple accents
   ======================================== */

* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

:root {
  --coach-primary: #6366f1;
  --coach-secondary: #8b5cf6;
  --coach-accent: #ec4899;
  --coach-dark: #0f172a;
  --coach-darker: #020617;
  --coach-surface: #1e293b;
  --coach-border: #334155;
  --coach-text: #f1f5f9;
  --coach-text-dim: #94a3b8;
  --coach-success: #10b981;
  --coach-warning: #f59e0b;
  --coach-danger: #ef4444;
}

body {
  font-family: 'Inter', 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
  background: linear-gradient(135deg, var(--coach-darker) 0%, var(--coach-dark) 100%);
  color: var(--coach-text);
  line-height: 1.6;
  min-height: 100vh;
}

/* ========================================
   CONTAINER & LAYOUT
   ======================================== */

.container {
  display: flex;
  min-height: 100vh;
  background: var(--coach-darker);
}

/* ========================================
   SIDEBAR - Modern Vertical Navigation
   ======================================== */

.sidebar {
  width: 280px;
  background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%);
  border-right: 1px solid var(--coach-border);
  padding: 2rem 0;
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
}

.logo {
  padding: 0 1.5rem 2rem;
  border-bottom: 1px solid var(--coach-border);
  margin-bottom: 2rem;
}

.logo svg {
  filter: drop-shadow(0 0 20px rgba(99, 102, 241, 0.3));
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
  color: var(--coach-text-dim);
  text-decoration: none;
  border-radius: 12px;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  font-weight: 500;
  position: relative;
  overflow: hidden;
}

.nav-link::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  height: 100%;
  width: 3px;
  background: linear-gradient(180deg, var(--coach-primary), var(--coach-secondary));
  transform: scaleY(0);
  transition: transform 0.3s;
}

.nav-link:hover {
  background: rgba(99, 102, 241, 0.1);
  color: var(--coach-text);
  transform: translateX(4px);
}

.nav-link:hover::before {
  transform: scaleY(1);
}

.nav-link.active {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.15));
  color: var(--coach-primary);
  font-weight: 600;
}

.nav-link.active::before {
  transform: scaleY(1);
}

.nav-link ion-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.logout-btn {
  margin: 1.5rem 1rem 0;
  padding: 1rem 1.25rem;
  background: linear-gradient(135deg, #ef4444, #dc2626);
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
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.8) 0%, rgba(2, 6, 23, 0.9) 100%);
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
  border-bottom: 1px solid var(--coach-border);
}

.header h1 {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, var(--coach-primary), var(--coach-secondary));
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.5rem;
  letter-spacing: -0.02em;
}

.header p {
  color: var(--coach-text-dim);
  font-size: 1.1rem;
}

.header-date {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.875rem 1.5rem;
  background: rgba(99, 102, 241, 0.1);
  border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 12px;
  color: var(--coach-primary);
  font-weight: 600;
}

.header-date ion-icon {
  font-size: 1.25rem;
}

/* ========================================
   STATS CARDS - Unique Coach Design
   ======================================== */

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2.5rem;
  margin-bottom: 3rem;
}

.coach-stat-card, .stat-card {
  background: transparent;
  border: none;
  border-radius: 0;
  padding: 1.5rem 0;
  position: relative;
  overflow: visible;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: none;
}

.coach-stat-card::before, .stat-card::before {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 2px;
  background: linear-gradient(90deg, transparent, var(--coach-primary), transparent);
  opacity: 0.3;
  transition: opacity 0.3s;
}

.coach-stat-card:hover, .stat-card:hover {
  transform: translateX(4px);
  border-color: transparent;
  box-shadow: none;
}

.coach-stat-card:hover::before, .stat-card:hover::before {
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
  color: var(--coach-primary);
  box-shadow: none;
  border: 2px solid rgba(99, 102, 241, 0.3);
}

.stat-label {
  font-size: 0.875rem;
  color: #cbd5e1;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 700;
  margin-bottom: 0.75rem;
}

.stat-value {
  font-size: 2.5rem;
  font-weight: 900;
  color: #ffffff;
  line-height: 1;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.stat-trend {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid rgba(255, 255, 255, 0.05);
  font-size: 0.875rem;
  color: var(--coach-text-dim);
}

.stat-trend ion-icon {
  font-size: 1.125rem;
  color: var(--coach-success);
}

.stat-trend.positive {
  color: var(--coach-success);
}

.stat-trend.negative {
  color: var(--coach-danger);
}

/* Progress Ring */
.stat-progress {
  position: absolute;
  top: 1.5rem;
  right: 1.5rem;
  width: 60px;
  height: 60px;
}

.stat-progress svg {
  transform: rotate(-90deg);
}

.stat-progress circle {
  fill: none;
  stroke-width: 4;
}

.stat-progress .progress-bg {
  stroke: rgba(99, 102, 241, 0.1);
}

.stat-progress .progress-fill {
  stroke: url(#gradient);
  stroke-linecap: round;
  transition: stroke-dashoffset 1s ease;
}

/* ========================================
   BEAUTIFUL HERO STATS CARDS
   ======================================== */

.coach-hero-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.hero-stat-card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 24px;
  padding: 2rem;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(20px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.hero-stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, var(--coach-primary), transparent);
  opacity: 0;
  transition: opacity 0.3s;
}

.hero-stat-card:hover {
  transform: translateY(-8px);
  border-color: rgba(99, 102, 241, 0.4);
  box-shadow: 0 20px 48px rgba(99, 102, 241, 0.25), 0 0 0 1px rgba(99, 102, 241, 0.1);
}

.hero-stat-card:hover::before {
  opacity: 1;
}

.hero-stat-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.5rem;
}

.hero-stat-icon-wrapper {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  transition: all 0.3s;
}

.hero-stat-card:hover .hero-stat-icon-wrapper {
  transform: scale(1.1) rotate(-5deg);
  box-shadow: 0 12px 32px rgba(99, 102, 241, 0.4);
}

.hero-stat-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 1rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  backdrop-filter: blur(10px);
}

.hero-stat-badge ion-icon {
  font-size: 1rem;
}

.badge-primary {
  background: rgba(99, 102, 241, 0.15);
  color: #a5b4fc;
  border: 1px solid rgba(99, 102, 241, 0.3);
}

.badge-purple {
  background: rgba(139, 92, 246, 0.15);
  color: #c4b5fd;
  border: 1px solid rgba(139, 92, 246, 0.3);
}

.badge-success {
  background: rgba(16, 185, 129, 0.15);
  color: #6ee7b7;
  border: 1px solid rgba(16, 185, 129, 0.3);
}

.badge-warning {
  background: rgba(245, 158, 11, 0.15);
  color: #fcd34d;
  border: 1px solid rgba(245, 158, 11, 0.3);
}

.hero-stat-content {
  margin-bottom: 1.5rem;
}

.hero-stat-label {
  font-size: 0.875rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 600;
  margin-bottom: 0.75rem;
}

.hero-stat-value {
  font-size: 2.5rem;
  font-weight: 900;
  color: #ffffff;
  line-height: 1.1;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
  margin-bottom: 0.75rem;
}

.value-divider {
  font-size: 2rem;
  color: #475569;
  margin: 0 0.25rem;
}

.value-subtext {
  font-size: 1.25rem;
  color: #64748b;
  font-weight: 600;
  margin-left: 0.5rem;
}

.hero-stat-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #cbd5e1;
}

.hero-stat-meta ion-icon {
  font-size: 1.125rem;
  color: var(--coach-primary);
}

.hero-stat-footer {
  padding-top: 1.5rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
  display: flex;
  align-items: center;
  justify-content: space-between;
}

/* Next Class Card Specific */
.capacity-indicator {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.capacity-text {
  font-size: 1.25rem;
  font-weight: 700;
  color: #ffffff;
}

.capacity-label {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.progress-ring {
  position: relative;
  width: 46px;
  height: 46px;
}

.progress-ring svg circle {
  transition: stroke-dashoffset 0.5s ease;
}

.empty-state {
  justify-content: center;
  gap: 0.75rem;
  color: #64748b;
  font-size: 0.875rem;
}

.empty-state ion-icon {
  font-size: 1.25rem;
  color: #475569;
}

/* Capacity Card Specific */
.capacity-bar-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
  width: 100%;
}

.capacity-bar {
  flex: 1;
  height: 8px;
  background: rgba(100, 116, 139, 0.2);
  border-radius: 10px;
  overflow: hidden;
}

.capacity-bar-fill {
  height: 100%;
  border-radius: 10px;
  transition: width 0.6s ease;
  box-shadow: 0 0 12px rgba(139, 92, 246, 0.5);
}

.capacity-percent {
  font-size: 1rem;
  font-weight: 700;
  color: #a78bfa;
  min-width: 45px;
  text-align: right;
}

/* Engagement Card Specific */
.engagement-indicator {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  width: 100%;
}

.engagement-dot {
  width: 12px;
  height: 12px;
  border-radius: 50%;
  background: rgba(100, 116, 139, 0.3);
  transition: all 0.3s;
}

.engagement-dot.active {
  background: linear-gradient(135deg, #10b981, #059669);
  box-shadow: 0 0 12px rgba(16, 185, 129, 0.6);
  animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

.engagement-label {
  margin-left: auto;
  font-size: 0.875rem;
  font-weight: 600;
  color: #10b981;
  padding: 0.375rem 0.875rem;
  background: rgba(16, 185, 129, 0.1);
  border-radius: 8px;
}

/* Sessions Card Specific */
.session-timeline {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
}

.timeline-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: linear-gradient(135deg, #f59e0b, #d97706);
  box-shadow: 0 0 8px rgba(245, 158, 11, 0.6);
  animation: fade-in 0.5s ease forwards;
  opacity: 0;
}

@keyframes fade-in {
  to {
    opacity: 1;
  }
}

.timeline-more {
  margin-left: 0.5rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #fbbf24;
  padding: 0.25rem 0.75rem;
  background: rgba(245, 158, 11, 0.15);
  border-radius: 8px;
}

/* ========================================
   CONTENT SECTIONS
   ======================================== */

.section {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8));
  border: 1px solid var(--coach-border);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2rem;
  backdrop-filter: blur(10px);
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid var(--coach-border);
}

.section-title {
  font-size: 1.5rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  color: var(--coach-text);
}

.section-title ion-icon {
  font-size: 1.75rem;
  color: var(--coach-primary);
}

/* ========================================
   TABLES - Modern Design
   ======================================== */

.table-container {
  overflow-x: auto;
  border-radius: 12px;
  background: rgba(15, 23, 42, 0.5);
}

table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
}

thead {
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
}

th {
  padding: 1.25rem 1.5rem;
  text-align: left;
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--coach-primary);
  border-bottom: 2px solid var(--coach-border);
}

tbody tr {
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
  transition: all 0.3s;
}

tbody tr:hover {
  background: rgba(99, 102, 241, 0.05);
}

td {
  padding: 1.25rem 1.5rem;
  color: var(--coach-text-dim);
}

td:first-child {
  color: var(--coach-text);
  font-weight: 600;
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

.badge-primary {
  background: rgba(99, 102, 241, 0.15);
  border-color: var(--coach-primary);
  color: var(--coach-primary);
}

.badge-success {
  background: rgba(16, 185, 129, 0.15);
  border-color: var(--coach-success);
  color: var(--coach-success);
}

.badge-warning {
  background: rgba(245, 158, 11, 0.15);
  border-color: var(--coach-warning);
  color: var(--coach-warning);
}

.badge-danger {
  background: rgba(239, 68, 68, 0.15);
  border-color: var(--coach-danger);
  color: var(--coach-danger);
}

.badge ion-icon {
  font-size: 1rem;
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
  background: linear-gradient(135deg, var(--coach-primary), var(--coach-secondary));
  color: white;
  border: none;
  border-radius: 12px;
  font-weight: 600;
  font-size: 0.95rem;
  cursor: pointer;
  transition: all 0.3s;
  text-decoration: none;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(99, 102, 241, 0.4);
}

.btn ion-icon {
  font-size: 1.25rem;
}

.btn-secondary {
  background: linear-gradient(135deg, var(--coach-surface), var(--coach-dark));
  border: 1px solid var(--coach-border);
  box-shadow: none;
}

.btn-secondary:hover {
  border-color: var(--coach-primary);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
}

.btn-ghost {
  background: transparent;
  border: 1px solid var(--coach-border);
  color: var(--coach-text-dim);
  box-shadow: none;
}

.btn-ghost:hover {
  border-color: var(--coach-primary);
  color: var(--coach-primary);
  background: rgba(99, 102, 241, 0.1);
}

/* ========================================
   CARDS - Unique Layout
   ======================================== */

.card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8));
  border: 1px solid var(--coach-border);
  border-radius: 16px;
  padding: 1.75rem;
  transition: all 0.3s;
}

.card:hover {
  border-color: var(--coach-primary);
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.card-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--coach-text);
}

.card-body {
  color: var(--coach-text-dim);
  line-height: 1.7;
}

/* ========================================
   FORMS
   ======================================== */

.form-group {
  margin-bottom: 1.5rem;
}

label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 600;
  color: var(--coach-text);
  font-size: 0.95rem;
}

input[type="text"],
input[type="email"],
input[type="password"],
input[type="number"],
input[type="date"],
input[type="time"],
select,
textarea {
  width: 100%;
  padding: 0.875rem 1.25rem;
  background: rgba(15, 23, 42, 0.8);
  border: 1px solid var(--coach-border);
  border-radius: 12px;
  color: var(--coach-text);
  font-size: 1rem;
  transition: all 0.3s;
}

input:focus,
select:focus,
textarea:focus {
  outline: none;
  border-color: var(--coach-primary);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
  background: rgba(15, 23, 42, 1);
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

.alert ion-icon {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.alert-success {
  background: rgba(16, 185, 129, 0.15);
  border-color: var(--coach-success);
  color: var(--coach-success);
}

.alert-error {
  background: rgba(239, 68, 68, 0.15);
  border-color: var(--coach-danger);
  color: var(--coach-danger);
}

.alert-warning {
  background: rgba(245, 158, 11, 0.15);
  border-color: var(--coach-warning);
  color: var(--coach-warning);
}

/* ========================================
   SCHEDULE HERO (Courses Page)
   ======================================== */

.schedule-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 2.5rem 0;
  margin-bottom: 3rem;
  position: relative;
}

.schedule-hero::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--coach-border), transparent);
}

.schedule-stat {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  flex: 1;
}

.schedule-stat-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: transparent;
  border: 3px solid var(--color);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: var(--color);
  transition: all 0.3s;
}

.schedule-stat:hover .schedule-stat-icon {
  transform: scale(1.1) rotate(5deg);
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
}

.schedule-stat-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.schedule-stat-value {
  font-size: 2.5rem;
  font-weight: 900;
  color: #ffffff;
  line-height: 1;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.schedule-stat-label {
  font-size: 0.875rem;
  color: #cbd5e1;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 600;
}

.schedule-divider {
  width: 2px;
  height: 60px;
  background: linear-gradient(180deg, transparent, var(--coach-primary), transparent);
  opacity: 0.3;
}

/* ========================================
   CAPACITY HERO (Members Page)
   ======================================== */

.capacity-hero {
  display: flex;
  align-items: center;
  gap: 4rem;
  padding: 3rem 0;
  margin-bottom: 3rem;
  position: relative;
}

.capacity-hero::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--coach-border), transparent);
}

.capacity-ring-container {
  position: relative;
  flex-shrink: 0;
}

.capacity-ring {
  filter: drop-shadow(0 8px 24px rgba(99, 102, 241, 0.3));
}

.capacity-ring-text {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  text-align: center;
}

.capacity-current {
  font-size: 3rem;
  font-weight: 900;
  color: #ffffff;
  line-height: 1;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.capacity-total {
  font-size: 1.5rem;
  font-weight: 700;
  color: #94a3b8;
  margin-top: 0.25rem;
}

.capacity-label {
  font-size: 0.875rem;
  color: #cbd5e1;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 600;
  margin-top: 0.5rem;
}

.capacity-info {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.capacity-info-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem 0;
  border-bottom: 1px solid rgba(99, 102, 241, 0.1);
  transition: all 0.3s;
}

.capacity-info-item:hover {
  transform: translateX(8px);
  border-bottom-color: var(--coach-primary);
}

.capacity-info-item:last-child {
  border-bottom: none;
}

.capacity-info-item ion-icon {
  font-size: 2.5rem;
  flex-shrink: 0;
}

.capacity-info-value {
  font-size: 2rem;
  font-weight: 900;
  color: #ffffff;
  line-height: 1;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.capacity-info-label {
  font-size: 0.875rem;
  color: #cbd5e1;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-weight: 600;
  margin-top: 0.5rem;
}

/* ========================================
   RESPONSIVE
   ======================================== */

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }

  .schedule-hero {
    flex-wrap: wrap;
    gap: 2rem;
  }

  .schedule-divider {
    display: none;
  }

  .capacity-hero {
    gap: 3rem;
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

  .schedule-hero {
    flex-direction: column;
    align-items: stretch;
  }

  .schedule-stat {
    justify-content: flex-start;
  }

  .capacity-hero {
    flex-direction: column;
    align-items: center;
    text-align: center;
  }

  .capacity-info {
    width: 100%;
  }

  .capacity-info-item:hover {
    transform: translateY(-4px);
  }
}

/* ========================================
   ANIMATIONS
   ======================================== */

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
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

.animate-fade {
  animation: fadeIn 0.6s ease-out;
}

.animate-slide {
  animation: slideUp 0.6s ease-out;
}

/* Stagger animation for cards */
.stats-grid > * {
  animation: slideUp 0.6s ease-out;
  animation-fill-mode: both;
}

.stats-grid > *:nth-child(1) { animation-delay: 0.1s; }
.stats-grid > *:nth-child(2) { animation-delay: 0.2s; }
.stats-grid > *:nth-child(3) { animation-delay: 0.3s; }
.stats-grid > *:nth-child(4) { animation-delay: 0.4s; }

/* ========================================
   DASHBOARD ROW & QUICK ACTIONS
   ======================================== */

.dashboard-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 2rem;
  margin-bottom: 2rem;
}

.quick-actions-panel,
.performance-chart {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8));
  border: 1px solid var(--coach-border);
  border-radius: 20px;
  padding: 2rem;
  backdrop-filter: blur(10px);
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
  background: rgba(99, 102, 241, 0.05);
  border: 1px solid var(--coach-border);
  border-radius: 14px;
  text-decoration: none;
  transition: all 0.3s;
}

.quick-action-card:hover {
  background: rgba(99, 102, 241, 0.15);
  border-color: var(--coach-primary);
  transform: translateX(4px);
  box-shadow: 0 4px 16px rgba(99, 102, 241, 0.2);
}

.quick-action-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--coach-primary);
  flex-shrink: 0;
}

.quick-action-info h3 {
  font-size: 1rem;
  font-weight: 600;
  color: var(--coach-text);
  margin-bottom: 0.25rem;
}

.quick-action-info p {
  font-size: 0.875rem;
  color: var(--coach-text-dim);
}

/* ========================================
   CHARTS & VISUALIZATIONS
   ======================================== */

.chart-container {
  padding: 1.5rem 0;
}

.chart-bars {
  display: flex;
  align-items: flex-end;
  justify-content: space-around;
  height: 200px;
  gap: 1rem;
  margin-bottom: 2rem;
}

.chart-bar {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
}

.chart-bar-fill {
  width: 100%;
  height: var(--height);
  background: linear-gradient(180deg, var(--coach-primary), var(--coach-secondary));
  border-radius: 8px 8px 0 0;
  transition: height 0.6s ease;
  position: relative;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.chart-bar-fill::after {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: rgba(255, 255, 255, 0.3);
  border-radius: 8px 8px 0 0;
}

.chart-label {
  font-size: 0.75rem;
  color: var(--coach-text-dim);
  font-weight: 600;
  text-transform: uppercase;
}

.chart-stats {
  display: flex;
  justify-content: space-around;
  gap: 2rem;
  padding-top: 2rem;
  border-top: 1px solid var(--coach-border);
}

.chart-stat {
  text-align: center;
}

.chart-stat-value {
  display: block;
  font-size: 2rem;
  font-weight: 800;
  color: var(--coach-primary);
  margin-bottom: 0.5rem;
}

.chart-stat-label {
  display: block;
  font-size: 0.875rem;
  color: var(--coach-text-dim);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

/* ========================================
   ACTIVITY LISTS
   ======================================== */

.activity-section {
  grid-column: 1 / -1;
}

.activity-list {
  list-style: none;
}

.activity-item {
  display: flex;
  align-items: center;
  gap: 1.5rem;
  padding: 1.5rem;
  background: rgba(99, 102, 241, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-radius: 12px;
  margin-bottom: 1rem;
  transition: all 0.3s;
}

.activity-item:hover {
  background: rgba(99, 102, 241, 0.08);
  border-color: var(--coach-primary);
  transform: translateX(4px);
}

.activity-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(139, 92, 246, 0.2));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--coach-primary);
  flex-shrink: 0;
}

.activity-info {
  flex: 1;
}

.activity-title {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--coach-text);
  margin-bottom: 0.5rem;
}

.activity-meta {
  font-size: 0.875rem;
  color: var(--coach-text-dim);
  margin-bottom: 0.25rem;
}

.activity-meta:last-child {
  margin-bottom: 0;
}

.view-all-link {
  color: var(--coach-primary);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.95rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  transition: all 0.3s;
}

.view-all-link:hover {
  color: var(--coach-secondary);
  gap: 0.75rem;
}

.view-all-link::after {
  content: '→';
}

/* ========================================
   PROGRESS BARS (stat-bar)
   ======================================== */

.stat-bar {
  width: 100%;
  height: 6px;
  background: rgba(99, 102, 241, 0.1);
  border-radius: 10px;
  overflow: hidden;
  margin-top: 1rem;
}

.stat-bar-fill {
  height: 100%;
  background: linear-gradient(90deg, var(--coach-primary), var(--coach-secondary));
  border-radius: 10px;
  transition: width 0.8s ease;
  box-shadow: 0 0 10px rgba(99, 102, 241, 0.5);
}

/* ========================================
   MEMBER CARDS & AVATARS
   ======================================== */

.member-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
}

.member-card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8));
  border: 1px solid var(--coach-border);
  border-radius: 16px;
  padding: 1.75rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
  transition: all 0.3s;
}

.member-card:hover {
  border-color: var(--coach-primary);
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(99, 102, 241, 0.15);
}

.member-header {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.member-avatar {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--coach-primary), var(--coach-secondary));
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.member-info h3 {
  font-size: 1.1rem;
  font-weight: 600;
  color: var(--coach-text);
  margin-bottom: 0.25rem;
}

.member-info p {
  font-size: 0.875rem;
  color: var(--coach-text-dim);
}

/* ========================================
   PROFILE SECTIONS
   ======================================== */

.profile-grid {
  display: grid;
  grid-template-columns: 300px 1fr;
  gap: 2rem;
}

.profile-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.avatar-section {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.6), rgba(15, 23, 42, 0.8));
  border: 1px solid var(--coach-border);
  border-radius: 20px;
  padding: 2rem;
  text-align: center;
}

.avatar-display {
  width: 160px;
  height: 160px;
  margin: 0 auto 1.5rem;
  border-radius: 50%;
  border: 4px solid var(--coach-primary);
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
}

.avatar-display img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.form-row {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1.5rem;
  margin-bottom: 1.5rem;
}

.form-row > div {
  display: flex;
  flex-direction: column;
}

input[type="file"] {
  padding: 0.625rem;
  font-size: 0.95rem;
}

@media (max-width: 1024px) {
  .dashboard-row {
    grid-template-columns: 1fr;
  }

  .profile-grid {
    grid-template-columns: 1fr;
  }

  .quick-actions-grid {
    grid-template-columns: 1fr;
  }

  .form-row {
    grid-template-columns: 1fr;
  }
}

/* Support for old stat-card class (for backwards compat) */
.stat-card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.8), rgba(15, 23, 42, 0.9));
  border: 1px solid var(--coach-border);
  border-radius: 20px;
  padding: 2rem;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(10px);
}

.stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(90deg, var(--coach-primary), var(--coach-secondary), var(--coach-accent));
  opacity: 0;
  transition: opacity 0.3s;
}

.stat-card:hover {
  transform: translateY(-8px);
  border-color: var(--coach-primary);
  box-shadow: 0 20px 40px rgba(99, 102, 241, 0.2);
}

.stat-card:hover::before {
  opacity: 1;
}

/* ========================================
   AVATAR BLOCK & PROFILE SPECIFIC
   ======================================== */

.avatar-block {
  display: flex;
  align-items: flex-start;
  gap: 2rem;
  padding: 1.5rem 0;
}

.avatar-block img {
  width: 140px;
  height: 140px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid var(--coach-primary);
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.3);
  flex-shrink: 0;
}

.avatar-block form {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.avatar-block label {
  font-size: 0.95rem;
  margin-bottom: 0;
}

.avatar-block input[type="file"] {
  margin-top: 0.5rem;
}

.avatar-block .btn {
  align-self: flex-start;
  margin-top: 0.5rem;
}

.hint {
  font-size: 0.875rem;
  color: var(--coach-text-dim);
  font-style: italic;
  margin: 0;
}

/* Section title spacing */
.section .section-title {
  margin-bottom: 2rem;
}

/* Better form spacing */
.section form {
  max-width: 100%;
}

.section form .btn {
  margin-top: 2rem;
}

/* ========================================
   PROFILE HERO - Modern Layout
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
  background: linear-gradient(90deg, transparent, var(--coach-border), transparent);
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
  border: 4px solid var(--coach-primary);
  box-shadow: 0 12px 32px rgba(99, 102, 241, 0.4);
}

.avatar-badge {
  position: absolute;
  bottom: 8px;
  right: 8px;
  width: 44px;
  height: 44px;
  background: linear-gradient(135deg, var(--coach-primary), var(--coach-secondary));
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.875rem;
  font-weight: 700;
  color: white;
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.5);
  border: 3px solid var(--coach-darker);
}

.profile-hero-info {
  flex: 1;
}

.profile-hero-info h2 {
  font-size: 2.25rem;
  font-weight: 800;
  color: var(--coach-text);
  margin-bottom: 0.5rem;
  line-height: 1.2;
}

.profile-role {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  color: var(--coach-primary);
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
  color: var(--coach-text-dim);
  font-size: 1rem;
}

.stat-inline ion-icon {
  font-size: 1.25rem;
  color: var(--coach-primary);
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
  color: var(--coach-text);
  line-height: 1;
  margin-bottom: 0.25rem;
}

.ring-label {
  display: block;
  font-size: 0.75rem;
  color: var(--coach-text-dim);
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
  color: var(--coach-primary);
}

.section-header-minimal h3 {
  font-size: 1.5rem;
  font-weight: 700;
  color: var(--coach-text);
  margin: 0;
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
  border: 2px dashed rgba(99, 102, 241, 0.3);
  border-radius: 16px;
  text-align: center;
  background: rgba(99, 102, 241, 0.02);
  transition: all 0.3s;
  cursor: pointer;
}

.upload-area:hover {
  border-color: var(--coach-primary);
  background: rgba(99, 102, 241, 0.05);
}

.upload-area ion-icon {
  font-size: 3rem;
  color: var(--coach-primary);
  margin-bottom: 1rem;
}

.upload-area p {
  color: var(--coach-text);
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
  color: var(--coach-text-dim);
}

.btn-upload {
  padding: 1.25rem 2rem;
  white-space: nowrap;
}

/* ========================================
   MODERN FORM - Clean Inputs
   ======================================== */

.modern-form {
  max-width: 700px;
}

.input-group {
  margin-bottom: 2rem;
}

.input-group label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--coach-text);
  margin-bottom: 0.75rem;
}

.input-group label ion-icon {
  font-size: 1.25rem;
  color: var(--coach-primary);
}

.optional-badge {
  margin-left: auto;
  padding: 0.25rem 0.75rem;
  background: rgba(99, 102, 241, 0.1);
  border-radius: 20px;
  font-size: 0.75rem;
  color: var(--coach-primary);
  font-weight: 600;
}

.input-group input {
  width: 100%;
  padding: 1rem 1.25rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 12px;
  color: var(--coach-text);
  font-size: 1rem;
  transition: all 0.3s;
}

.input-group input:focus {
  outline: none;
  border-color: var(--coach-primary);
  background: rgba(15, 23, 42, 0.8);
  box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.input-group input::placeholder {
  color: var(--coach-text-dim);
  opacity: 0.6;
}

.btn-primary {
  padding: 1.25rem 3rem;
  font-size: 1.1rem;
  margin-top: 1rem;
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
  .avatar-block {
    flex-direction: column;
    text-align: center;
  }

  .avatar-block img {
    margin: 0 auto;
  }

  .avatar-block form {
    width: 100%;
  }

  .avatar-block .btn {
    width: 100%;
  }

  .profile-hero-info h2 {
    font-size: 1.75rem;
  }

  .profile-stats-inline {
    flex-direction: column;
    gap: 1rem;
  }
}

/* ========================================
   PROFESSIONAL ADD SLOT FORM
   ======================================== */

.add-slot-card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 24px;
  padding: 0;
  margin-bottom: 3rem;
  overflow: hidden;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  transition: all 0.3s;
}

.add-slot-card:hover {
  border-color: rgba(99, 102, 241, 0.4);
  box-shadow: 0 12px 48px rgba(99, 102, 241, 0.2);
}

.add-slot-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 2.5rem;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.05));
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.add-slot-title-wrapper {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.add-slot-icon {
  width: 56px;
  height: 56px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  color: white;
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
}

.add-slot-title {
  font-size: 1.75rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
  line-height: 1.2;
}

.add-slot-subtitle {
  font-size: 0.875rem;
  color: #94a3b8;
  margin: 0.25rem 0 0;
}

.add-slot-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: rgba(139, 92, 246, 0.15);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 12px;
  color: #c4b5fd;
  font-size: 0.875rem;
  font-weight: 600;
}

.add-slot-badge ion-icon {
  font-size: 1.125rem;
}

.add-slot-form {
  padding: 2.5rem;
}

.form-section {
  margin-bottom: 2.5rem;
}

.form-section-label {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-size: 1rem;
  font-weight: 700;
  color: #e2e8f0;
  margin-bottom: 1.5rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.form-section-label ion-icon {
  font-size: 1.5rem;
  color: var(--coach-primary);
}

.activity-selector {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.activity-divider {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: #64748b;
  font-size: 0.875rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.1em;
}

.activity-divider::before,
.activity-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: linear-gradient(90deg, transparent, rgba(99, 102, 241, 0.3), transparent);
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(100, 116, 139, 0.3);
  border-radius: 16px;
  padding: 0.75rem 1.25rem;
  transition: all 0.3s;
}

.input-wrapper:focus-within {
  border-color: var(--coach-primary);
  background: rgba(15, 23, 42, 0.8);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.input-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  background: rgba(99, 102, 241, 0.1);
  border-radius: 10px;
  margin-right: 1rem;
  flex-shrink: 0;
}

.input-icon ion-icon {
  font-size: 1.25rem;
  color: var(--coach-primary);
}

.input-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.floating-label {
  font-size: 0.75rem;
  color: #94a3b8;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.styled-input,
.styled-select {
  flex: 1;
  background: transparent;
  border: none;
  color: #ffffff;
  font-size: 1rem;
  font-weight: 500;
  padding: 0;
  outline: none;
  width: 100%;
}

.styled-input::placeholder {
  color: #475569;
}

.styled-select {
  cursor: pointer;
  appearance: none;
  background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236366f1' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 0.5rem center;
  padding-right: 2rem;
}

.styled-select option {
  background: #1e293b;
  color: #ffffff;
  padding: 0.5rem;
}

.datetime-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1rem;
}

@media (min-width: 768px) {
  .datetime-grid {
    grid-template-columns: repeat(3, 1fr);
  }
}

.capacity-selector {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.capacity-presets {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}

.preset-btn {
  padding: 0.75rem 1.5rem;
  background: rgba(99, 102, 241, 0.1);
  border: 1px solid rgba(99, 102, 241, 0.3);
  border-radius: 12px;
  color: #a5b4fc;
  font-size: 0.875rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
}

.preset-btn:hover {
  background: rgba(99, 102, 241, 0.2);
  border-color: var(--coach-primary);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}

.form-actions {
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
}

.btn-create-slot {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 1.25rem 2rem;
  background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
  border: none;
  border-radius: 16px;
  color: white;
  font-size: 1.125rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  position: relative;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.4);
}

.btn-create-slot:hover {
  transform: translateY(-3px);
  box-shadow: 0 12px 32px rgba(99, 102, 241, 0.5);
}

.btn-create-slot ion-icon {
  font-size: 1.5rem;
}

.btn-shine {
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.5s;
}

.btn-create-slot:hover .btn-shine {
  left: 100%;
}

@media (max-width: 768px) {
  .add-slot-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1.5rem;
  }

  .add-slot-form {
    padding: 1.5rem;
  }
}
</style>
