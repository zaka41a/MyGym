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
    backdrop-filter: blur(10px);
    border-right: 1px solid rgba(255, 255, 255, 0.1);
    padding: 2rem 1.5rem;
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    z-index: 100;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 3rem;
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
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    font-weight: 500;
    position: relative;
  }

  .nav-link::before {
    content: '';
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 3px;
    height: 0;
    background: linear-gradient(180deg, #dc2626, #ef4444);
    border-radius: 0 3px 3px 0;
    transition: height 0.3s;
  }

  .nav-link:hover::before {
    height: 60%;
  }

  .nav-link:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    padding-left: 1.25rem;
  }

  .nav-link.active {
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
    color: #fff;
    box-shadow: 0 4px 20px rgba(220,38,38,0.3);
  }

  .nav-link.active::before {
    height: 60%;
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
    transform: translateX(4px);
  }

  /* Main Content */
  .main-content {
    margin-left: 280px;
    flex: 1;
    padding: 2rem;
    animation: fadeIn 0.5s ease-out;
  }

  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }

  .header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 3rem;
    flex-wrap: wrap;
    gap: 1.5rem;
  }

  .header h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    background: linear-gradient(135deg, #fff 0%, #f5f7fb 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .header p {
    color: #9ca3af;
    font-size: 1rem;
  }

  .header-date {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 1.5rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #9ca3af;
    font-size: 0.95rem;
    font-weight: 500;
  }

  .header-date ion-icon {
    font-size: 1.25rem;
    color: #dc2626;
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

  /* Individual stat card colors */
  .stat-card.stat-members .stat-icon {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.2) 0%, rgba(37, 99, 235, 0.2) 100%);
    color: #3b82f6;
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.2);
  }

  .stat-card.stat-members:hover .stat-icon {
    box-shadow: 0 12px 24px rgba(59, 130, 246, 0.3);
  }

  .stat-card.stat-coaches .stat-icon {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(5, 150, 105, 0.2) 100%);
    color: #10b981;
    box-shadow: 0 8px 16px rgba(16, 185, 129, 0.2);
  }

  .stat-card.stat-coaches:hover .stat-icon {
    box-shadow: 0 12px 24px rgba(16, 185, 129, 0.3);
  }

  .stat-card.stat-subscriptions .stat-icon {
    background: linear-gradient(135deg, rgba(168, 85, 247, 0.2) 0%, rgba(147, 51, 234, 0.2) 100%);
    color: #a855f7;
    box-shadow: 0 8px 16px rgba(168, 85, 247, 0.2);
  }

  .stat-card.stat-subscriptions:hover .stat-icon {
    box-shadow: 0 12px 24px rgba(168, 85, 247, 0.3);
  }

  .stat-card.stat-revenue .stat-icon {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.2) 0%, rgba(217, 119, 6, 0.2) 100%);
    color: #f59e0b;
    box-shadow: 0 8px 16px rgba(245, 158, 11, 0.2);
  }

  .stat-card.stat-revenue:hover .stat-icon {
    box-shadow: 0 12px 24px rgba(245, 158, 11, 0.3);
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

  /* Dashboard Row Layout */
  .dashboard-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
  }

  @media (max-width: 991px) {
    .dashboard-row {
      grid-template-columns: 1fr;
    }
  }

  /* Section/Panel */
  .section {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    animation: slideInUp 0.6s ease-out 0.2s both;
  }

  .section-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
  }

  .section-title {
    font-size: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }

  .section-title ion-icon {
    color: #dc2626;
    font-size: 1.75rem;
  }

  .view-all-link {
    color: #dc2626;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  .view-all-link:hover {
    color: #ef4444;
    transform: translateX(4px);
  }

  /* Quick Actions Panel */
  .quick-actions-panel {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    animation: slideInUp 0.6s ease-out 0.3s both;
  }

  .quick-actions-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
  }

  .quick-action-card {
    position: relative;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 16px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    text-decoration: none;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
  }

  .quick-action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(180deg, #dc2626, #ef4444);
    transform: scaleY(0);
    transition: transform 0.3s;
  }

  .quick-action-card:hover::before {
    transform: scaleY(1);
  }

  .quick-action-card:hover {
    background: rgba(0, 0, 0, 0.5);
    border-color: rgba(220, 38, 38, 0.5);
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(220, 38, 38, 0.2);
  }

  .quick-action-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 1.5rem;
    color: #dc2626;
    transition: all 0.3s;
  }

  .quick-action-card:hover .quick-action-icon {
    transform: scale(1.1) rotate(5deg);
  }

  .quick-action-info h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.25rem;
  }

  .quick-action-info p {
    font-size: 0.8rem;
    color: #9ca3af;
  }

  .quick-action-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: linear-gradient(135deg, #dc2626, #ef4444);
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.6rem;
    border-radius: 999px;
    box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
  }

  /* Performance Chart */
  .performance-chart {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    animation: slideInUp 0.6s ease-out 0.4s both;
  }

  .chart-container {
    margin-top: 1.5rem;
  }

  .chart-bars {
    display: flex;
    align-items: flex-end;
    justify-content: space-between;
    gap: 1rem;
    height: 180px;
    margin-bottom: 2rem;
    padding: 0 0.5rem;
  }

  .chart-bar {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    position: relative;
  }

  .chart-bar-fill {
    width: 100%;
    height: var(--height, 0%);
    background: linear-gradient(180deg, #ef4444 0%, #dc2626 100%);
    border-radius: 8px 8px 0 0;
    position: relative;
    animation: growHeight 1.2s ease-out forwards;
    box-shadow: 0 -4px 20px rgba(220, 38, 38, 0.3);
    transition: all 0.3s;
  }

  @keyframes growHeight {
    from {
      height: 0;
      opacity: 0;
    }
    to {
      height: var(--height);
      opacity: 1;
    }
  }

  .chart-bar:hover .chart-bar-fill {
    background: linear-gradient(180deg, #f87171 0%, #ef4444 100%);
    box-shadow: 0 -6px 24px rgba(220, 38, 38, 0.5);
  }

  .chart-label {
    font-size: 0.75rem;
    color: #9ca3af;
    font-weight: 600;
    text-transform: uppercase;
    margin-top: 0.5rem;
  }

  .chart-stats {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
  }

  .chart-stat {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }

  .chart-stat-value {
    font-size: 2rem;
    font-weight: 700;
    background: linear-gradient(135deg, #fff 0%, #f5f7fb 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
  }

  .chart-stat-label {
    font-size: 0.8rem;
    color: #9ca3af;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  /* Activity Section */
  .activity-section {
    animation-delay: 0.5s;
  }

  /* Activity List */
  .activity-list {
    list-style: none;
  }

  .activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.25rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    margin-bottom: 0.75rem;
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
  }

  .activity-item::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    height: 100%;
    width: 3px;
    background: linear-gradient(180deg, #dc2626, #ef4444);
    transform: scaleY(0);
    transition: transform 0.3s;
  }

  .activity-item:hover::before {
    transform: scaleY(1);
  }

  .activity-item:hover {
    background: rgba(0, 0, 0, 0.5);
    border-color: rgba(220, 38, 38, 0.3);
    transform: translateX(6px);
  }

  .activity-avatar {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.2) 0%, rgba(239, 68, 68, 0.2) 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 2rem;
    color: #dc2626;
  }

  .activity-info {
    flex: 1;
  }

  .activity-info h4 {
    font-size: 1rem;
    font-weight: 600;
    margin-bottom: 0.25rem;
  }

  .activity-info p {
    font-size: 0.875rem;
    color: #9ca3af;
  }

  /* Badges */
  .badge {
    padding: 0.5rem 1rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    display: inline-block;
  }

  .badge-success {
    background: rgba(16, 185, 129, 0.2);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.3);
  }

  .badge-warning {
    background: rgba(245, 158, 11, 0.2);
    color: #f59e0b;
    border: 1px solid rgba(245, 158, 11, 0.3);
  }

  .badge-primary {
    background: rgba(220, 38, 38, 0.2);
    color: #dc2626;
    border: 1px solid rgba(220, 38, 38, 0.3);
  }

  .badge-info {
    background: rgba(59, 130, 246, 0.2);
    color: #3b82f6;
    border: 1px solid rgba(59, 130, 246, 0.3);
  }

  /* Tables */
  table {
    width: 100%;
    border-collapse: collapse;
  }

  thead td {
    font-weight: 600;
    color: #9ca3af;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding-bottom: 12px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
  }

  tbody tr {
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    transition: all 0.2s;
  }

  tbody tr:hover {
    background: rgba(220, 38, 38, 0.05);
  }

  td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
  }

  /* Buttons */
  .btn {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    color: #fff;
    border: 0;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s;
    font-size: 0.95rem;
  }

  .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
  }

  .btn-ghost {
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: #fff;
  }

  .btn-ghost:hover {
    background: rgba(255, 255, 255, 0.05);
    border-color: #dc2626;
  }

  .btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
  }

  /* Alerts */
  .alert {
    padding: 1rem 1.5rem;
    border-radius: 12px;
    margin-bottom: 1.5rem;
    border: 1px solid;
    animation: slideInUp 0.4s ease-out;
  }

  .alert-success {
    background: rgba(16, 185, 129, 0.2);
    border-color: rgba(16, 185, 129, 0.4);
    color: #10b981;
  }

  .alert-error {
    background: rgba(239, 68, 68, 0.2);
    border-color: rgba(239, 68, 68, 0.4);
    color: #ef4444;
  }

  /* Forms */
  .form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
  }

  .form-grid .full {
    grid-column: 1 / -1;
  }

  label {
    display: block;
    font-weight: 600;
    font-size: 0.875rem;
    color: #9ca3af;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }

  input, select, textarea {
    width: 100%;
    padding: 0.875rem;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 12px;
    color: #fff;
    outline: none;
    transition: all 0.3s;
    font-family: inherit;
  }

  input:focus, select:focus, textarea:focus {
    border-color: #dc2626;
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
  }

  /* Responsive */
  @media (max-width: 991px) {
    .sidebar {
      width: 0;
      opacity: 0;
    }
    .main-content {
      margin-left: 0;
    }
    .stats-grid {
      grid-template-columns: repeat(2, 1fr);
    }
    .quick-actions-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 768px) {
    .header {
      flex-direction: column;
      align-items: flex-start;
    }

    .header h1 {
      font-size: 2rem;
    }

    .header-date {
      align-self: stretch;
      justify-content: center;
    }

    .chart-bars {
      height: 150px;
      gap: 0.5rem;
    }
  }

  @media (max-width: 640px) {
    .stats-grid {
      grid-template-columns: 1fr;
    }
    .form-grid {
      grid-template-columns: 1fr;
    }
    .main-content {
      padding: 1rem;
    }

    .section {
      padding: 1.5rem;
    }

    .quick-actions-panel,
    .performance-chart {
      padding: 1.5rem;
    }
  }

  /* Additional animations for staggered entry */
  .stat-card:nth-child(1) {
    animation-delay: 0.1s;
  }

  .stat-card:nth-child(2) {
    animation-delay: 0.2s;
  }

  .stat-card:nth-child(3) {
    animation-delay: 0.3s;
  }

  .stat-card:nth-child(4) {
    animation-delay: 0.4s;
  }

  /* Smooth scrolling */
  html {
    scroll-behavior: smooth;
  }

  /* Custom scrollbar */
  ::-webkit-scrollbar {
    width: 10px;
    height: 10px;
  }

  ::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.2);
  }

  ::-webkit-scrollbar-thumb {
    background: linear-gradient(180deg, #dc2626, #ef4444);
    border-radius: 5px;
  }

  ::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(180deg, #ef4444, #f87171);
  }

/* ========================================
   PROFESSIONAL ADMIN HERO STATS CARDS
   ======================================== */

.admin-hero-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2rem;
  margin-bottom: 3rem;
}

.admin-stat-card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 1.75rem;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  backdrop-filter: blur(20px);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.admin-stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 3px;
  background: linear-gradient(90deg, transparent, #dc2626, transparent);
  opacity: 0;
  transition: opacity 0.3s;
}

.admin-stat-card:hover {
  transform: translateY(-6px);
  border-color: rgba(220, 38, 38, 0.4);
  box-shadow: 0 16px 48px rgba(220, 38, 38, 0.25), 0 0 0 1px rgba(220, 38, 38, 0.1);
}

.admin-stat-card:hover::before {
  opacity: 1;
}

.admin-stat-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1.25rem;
}

.admin-stat-icon-wrapper {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
  transition: all 0.3s;
}

.admin-stat-card:hover .admin-stat-icon-wrapper {
  transform: scale(1.1) rotate(-5deg);
  box-shadow: 0 12px 32px rgba(220, 38, 38, 0.4);
}

.admin-stat-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 0.875rem;
  border-radius: 10px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  backdrop-filter: blur(10px);
}

.admin-stat-badge ion-icon {
  font-size: 0.9rem;
}

.badge-red {
  background: rgba(220, 38, 38, 0.15);
  color: #fca5a5;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.badge-orange {
  background: rgba(234, 88, 12, 0.15);
  color: #fdba74;
  border: 1px solid rgba(234, 88, 12, 0.3);
}

.badge-green {
  background: rgba(5, 150, 105, 0.15);
  color: #6ee7b7;
  border: 1px solid rgba(5, 150, 105, 0.3);
}

.badge-purple {
  background: rgba(124, 58, 237, 0.15);
  color: #c4b5fd;
  border: 1px solid rgba(124, 58, 237, 0.3);
}

.admin-stat-content {
  margin-bottom: 1.25rem;
}

.admin-stat-label {
  font-size: 0.875rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-weight: 600;
  margin-bottom: 0.625rem;
}

.admin-stat-value {
  font-size: 2.25rem;
  font-weight: 900;
  color: #ffffff;
  line-height: 1.1;
  text-shadow: 0 2px 12px rgba(0, 0, 0, 0.4);
  margin-bottom: 0.625rem;
}

.admin-stat-meta {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #cbd5e1;
}

.admin-stat-meta ion-icon {
  font-size: 1rem;
  color: #dc2626;
}

.admin-stat-footer {
  padding-top: 1.25rem;
  border-top: 1px solid rgba(148, 163, 184, 0.1);
}

/* Progress Bar */
.progress-bar-wrapper {
  display: flex;
  align-items: center;
  gap: 0.875rem;
  width: 100%;
}

.progress-bar {
  flex: 1;
  height: 6px;
  background: rgba(100, 116, 139, 0.2);
  border-radius: 10px;
  overflow: hidden;
}

.progress-bar-fill {
  height: 100%;
  border-radius: 10px;
  transition: width 0.6s ease;
  box-shadow: 0 0 12px rgba(220, 38, 38, 0.5);
}

.progress-percent {
  font-size: 0.875rem;
  font-weight: 700;
  color: #ef4444;
  min-width: 40px;
  text-align: right;
}

/* Coach Indicators */
.coach-indicators {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.coach-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: linear-gradient(135deg, #ea580c, #c2410c);
  box-shadow: 0 0 8px rgba(234, 88, 12, 0.6);
  animation: fade-in-dot 0.5s ease forwards;
  opacity: 0;
}

@keyframes fade-in-dot {
  to {
    opacity: 1;
  }
}

.coach-more {
  margin-left: 0.375rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #fb923c;
  padding: 0.25rem 0.625rem;
  background: rgba(234, 88, 12, 0.15);
  border-radius: 8px;
}

/* Pending Indicator */
.pending-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #fbbf24;
  font-weight: 600;
}

.pending-indicator ion-icon {
  font-size: 1.125rem;
  color: #f59e0b;
  animation: pulse-icon 2s infinite;
}

@keyframes pulse-icon {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.2); }
}

.all-clear-indicator {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: #6ee7b7;
  font-weight: 600;
}

.all-clear-indicator ion-icon {
  font-size: 1.125rem;
  color: #10b981;
}

/* Revenue Sparkline */
.revenue-sparkline {
  width: 100%;
  height: 40px;
}

.revenue-sparkline svg {
  width: 100%;
  height: 100%;
}

/* Responsive */
@media (max-width: 768px) {
  .admin-hero-stats {
    grid-template-columns: 1fr;
  }
}

/* ========================================
   SYSTEM OVERVIEW PANEL
   ======================================== */

.system-overview-panel {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 3rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.overview-header {
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.overview-title-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.overview-title-wrapper ion-icon {
  font-size: 2rem;
  color: #dc2626;
}

.overview-title-wrapper h2 {
  font-size: 1.5rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
}

.overview-title-wrapper p {
  font-size: 0.875rem;
  color: #94a3b8;
  margin: 0.25rem 0 0;
}

.overview-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1.5rem;
}

.overview-card {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.5rem;
  background: rgba(15, 23, 42, 0.6);
  border: 1px solid rgba(100, 116, 139, 0.2);
  border-radius: 16px;
  transition: all 0.3s;
}

.overview-card:hover {
  transform: translateY(-4px);
  border-color: rgba(220, 38, 38, 0.4);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.overview-card-icon {
  width: 52px;
  height: 52px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: white;
  flex-shrink: 0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.overview-card-content {
  flex: 1;
}

.overview-card-value {
  font-size: 2rem;
  font-weight: 900;
  color: #ffffff;
  line-height: 1;
  margin-bottom: 0.375rem;
}

.overview-card-label {
  font-size: 0.875rem;
  font-weight: 700;
  color: #cbd5e1;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin-bottom: 0.25rem;
}

.overview-card-desc {
  font-size: 0.75rem;
  color: #64748b;
}

/* ========================================
   MODERN QUICK ACTIONS
   ======================================== */

.modern-quick-actions {
  margin-bottom: 3rem;
}

.quick-actions-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 1.5rem;
}

.quick-actions-header ion-icon {
  font-size: 1.75rem;
  color: #dc2626;
}

.quick-actions-header h2 {
  font-size: 1.5rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
}

.modern-actions-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.modern-action-card {
  position: relative;
  display: flex;
  align-items: center;
  gap: 1.25rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(100, 116, 139, 0.2);
  border-radius: 16px;
  text-decoration: none;
  transition: all 0.3s;
  overflow: hidden;
}

.modern-action-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 3px;
  height: 100%;
  background: var(--card-color);
  opacity: 0;
  transition: opacity 0.3s;
}

.modern-action-card:hover {
  transform: translateX(6px);
  border-color: var(--card-color);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.modern-action-card:hover::before {
  opacity: 1;
}

.modern-action-icon {
  width: 48px;
  height: 48px;
  background: rgba(220, 38, 38, 0.1);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  color: var(--card-color);
  flex-shrink: 0;
  transition: all 0.3s;
}

.modern-action-card:hover .modern-action-icon {
  transform: scale(1.1) rotate(-5deg);
  background: rgba(220, 38, 38, 0.2);
}

.modern-action-content {
  flex: 1;
}

.modern-action-content h3 {
  font-size: 1rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.25rem;
}

.modern-action-content p {
  font-size: 0.875rem;
  color: #94a3b8;
  margin: 0;
}

.modern-action-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  width: 24px;
  height: 24px;
  background: #dc2626;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: white;
  box-shadow: 0 4px 12px rgba(220, 38, 38, 0.4);
  animation: pulse-badge 2s infinite;
}

@keyframes pulse-badge {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.modern-action-arrow {
  font-size: 1.25rem;
  color: var(--card-color);
  opacity: 0;
  transform: translateX(-10px);
  transition: all 0.3s;
}

.modern-action-card:hover .modern-action-arrow {
  opacity: 1;
  transform: translateX(0);
}

/* ========================================
   ACTIVITY TIMELINE
   ======================================== */

.activity-timeline-section {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 3rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

.timeline-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(148, 163, 184, 0.1);
}

.timeline-title-wrapper {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.timeline-title-wrapper ion-icon {
  font-size: 1.75rem;
  color: #dc2626;
}

.timeline-title-wrapper h2 {
  font-size: 1.5rem;
  font-weight: 800;
  color: #ffffff;
  margin: 0;
}

.timeline-view-all {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.625rem 1.25rem;
  background: rgba(220, 38, 38, 0.1);
  border: 1px solid rgba(220, 38, 38, 0.3);
  border-radius: 10px;
  color: #fca5a5;
  font-size: 0.875rem;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.3s;
}

.timeline-view-all:hover {
  background: rgba(220, 38, 38, 0.2);
  transform: translateX(4px);
}

.timeline-view-all ion-icon {
  font-size: 1rem;
}

.activity-timeline {
  display: flex;
  flex-direction: column;
  gap: 0;
}

.timeline-item {
  position: relative;
  display: flex;
  padding: 1.25rem 0;
  border-bottom: 1px solid rgba(148, 163, 184, 0.05);
}

.timeline-item:last-child {
  border-bottom: none;
}

.timeline-dot {
  position: absolute;
  left: 21px;
  top: 2rem;
  bottom: 0;
  width: 2px;
  background: linear-gradient(180deg, var(--dot-color), transparent);
  opacity: 0.3;
}

.timeline-item:last-child .timeline-dot {
  display: none;
}

.timeline-content {
  display: flex;
  align-items: center;
  gap: 1.25rem;
  flex: 1;
  position: relative;
}

.timeline-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.25rem;
  color: #dc2626;
  flex-shrink: 0;
  border: 2px solid rgba(220, 38, 38, 0.2);
}

.timeline-info {
  flex: 1;
}

.timeline-info h4 {
  font-size: 1rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0 0 0.375rem;
}

.timeline-info p {
  font-size: 0.875rem;
  color: #94a3b8;
  margin: 0;
}

.timeline-status {
  padding: 0.25rem 0.625rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
}

.status-active {
  background: rgba(5, 150, 105, 0.15);
  color: #6ee7b7;
}

.status-pending {
  background: rgba(245, 158, 11, 0.15);
  color: #fcd34d;
}

.timeline-time {
  font-size: 0.75rem;
  font-weight: 600;
  color: #64748b;
  text-align: right;
  min-width: 80px;
}

.timeline-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem;
  color: #64748b;
}

.timeline-empty ion-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.timeline-empty p {
  font-size: 1rem;
  margin: 0;
}

/* Responsive */
/* ============================================
   USERS PAGE STYLES
   ============================================ */

/* Modern Users Stats Grid */
.users-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.users-stat-card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 1.75rem;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.users-stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--card-gradient);
  opacity: 0.05;
  transition: opacity 0.4s;
}

.users-stat-card:hover::before {
  opacity: 0.12;
}

.users-stat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
  border-color: rgba(220, 38, 38, 0.4);
}

.users-stat-icon-wrapper {
  position: absolute;
  top: 1.75rem;
  right: 1.75rem;
  width: 56px;
  height: 56px;
  background: var(--card-gradient);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
  transition: all 0.4s;
}

.users-stat-card:hover .users-stat-icon-wrapper {
  transform: rotate(12deg) scale(1.1);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.5);
}

.users-stat-content {
  position: relative;
  z-index: 1;
}

.users-stat-value {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #ffffff, #e5e7eb);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.users-stat-label {
  font-size: 0.95rem;
  font-weight: 600;
  color: #d1d5db;
  margin-bottom: 0.25rem;
  letter-spacing: 0.3px;
}

.users-stat-desc {
  font-size: 0.8rem;
  color: #9ca3af;
  font-weight: 400;
}

.users-stat-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-top: 1rem;
  border: 1px solid currentColor;
}

.users-stat-badge ion-icon {
  font-size: 1rem;
}

/* Professional Users Form Card */
.users-form-card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2.5rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
}

.users-form-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(220, 38, 38, 0.2);
}

.users-form-title-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.users-form-icon {
  width: 56px;
  height: 56px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  box-shadow: 0 8px 24px rgba(220, 38, 38, 0.4);
}

.users-form-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
  letter-spacing: -0.5px;
}

.users-form-subtitle {
  font-size: 0.875rem;
  color: #9ca3af;
  margin: 0.25rem 0 0;
}

.users-form-badge {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(220, 38, 38, 0.15);
  color: #dc2626;
  padding: 8px 16px;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.users-form-badge ion-icon {
  font-size: 1.1rem;
}

.users-cancel-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background: rgba(107, 114, 128, 0.2);
  color: #9ca3af;
  padding: 8px 16px;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
  border: 1px solid rgba(107, 114, 128, 0.3);
  text-decoration: none;
  transition: all 0.3s;
}

.users-cancel-btn:hover {
  background: rgba(239, 68, 68, 0.2);
  color: #ef4444;
  border-color: rgba(239, 68, 68, 0.5);
  transform: translateY(-2px);
}

/* Enhanced Form Submit Button */
.users-submit-btn {
  flex: 1;
  position: relative;
  overflow: hidden;
  background: linear-gradient(135deg, #dc2626, #991b1b);
  color: white;
  padding: 1rem 2rem;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 8px 24px rgba(220, 38, 38, 0.3);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.users-submit-btn:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 32px rgba(220, 38, 38, 0.5);
}

.users-submit-btn:active {
  transform: translateY(-2px);
}

.users-reset-btn {
  flex: 0 0 auto;
  background: rgba(107, 114, 128, 0.2);
  color: #9ca3af;
  padding: 1rem 1.5rem;
  border: 1px solid rgba(107, 114, 128, 0.3);
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  gap: 6px;
}

.users-reset-btn:hover {
  background: rgba(107, 114, 128, 0.3);
  color: #d1d5db;
  transform: translateY(-2px);
}

/* Modern Users List Section */
.users-list-section {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
}

.users-list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(220, 38, 38, 0.2);
}

.users-list-title-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.users-list-title-wrapper > ion-icon {
  font-size: 2rem;
  color: #dc2626;
  background: rgba(220, 38, 38, 0.15);
  padding: 12px;
  border-radius: 12px;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.users-list-title-wrapper h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
  letter-spacing: -0.5px;
}

.users-list-title-wrapper p {
  font-size: 0.875rem;
  color: #9ca3af;
  margin: 0.25rem 0 0;
}

.users-total-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(220, 38, 38, 0.15);
  color: #dc2626;
  padding: 10px 18px;
  border-radius: 20px;
  font-size: 0.95rem;
  font-weight: 600;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.users-total-badge ion-icon {
  font-size: 1.25rem;
}

.users-table-wrapper {
  overflow-x: auto;
}

/* Enhanced Users Table */
.users-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 0.75rem;
}

.users-table thead tr td {
  background: rgba(220, 38, 38, 0.1);
  color: #dc2626;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 1rem 1.5rem;
  border: none;
}

.users-table thead tr td:first-child {
  border-radius: 12px 0 0 12px;
}

.users-table thead tr td:last-child {
  border-radius: 0 12px 12px 0;
}

.users-table tbody tr {
  background: linear-gradient(135deg, rgba(17, 24, 39, 0.6), rgba(17, 24, 39, 0.4));
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.users-table tbody tr:hover {
  background: linear-gradient(135deg, rgba(220, 38, 38, 0.15), rgba(220, 38, 38, 0.08));
  transform: translateX(8px) scale(1.01);
  box-shadow: 0 8px 24px rgba(220, 38, 38, 0.2);
}

.users-table tbody tr td {
  padding: 1.25rem 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-left: none;
  border-right: none;
}

.users-table tbody tr td:first-child {
  border-radius: 12px 0 0 12px;
  border-left: 1px solid rgba(255, 255, 255, 0.05);
}

.users-table tbody tr td:last-child {
  border-radius: 0 12px 12px 0;
  border-right: 1px solid rgba(255, 255, 255, 0.05);
}

/* ============================================
   COURSES PAGE STYLES
   ============================================ */

/* Modern Courses Stats Grid */
.courses-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.courses-stat-card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 1.75rem;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.courses-stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--card-gradient);
  opacity: 0.05;
  transition: opacity 0.4s;
}

.courses-stat-card:hover::before {
  opacity: 0.12;
}

.courses-stat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
  border-color: rgba(220, 38, 38, 0.4);
}

.courses-stat-icon-wrapper {
  position: absolute;
  top: 1.75rem;
  right: 1.75rem;
  width: 56px;
  height: 56px;
  background: var(--card-gradient);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
  transition: all 0.4s;
}

.courses-stat-card:hover .courses-stat-icon-wrapper {
  transform: rotate(12deg) scale(1.1);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.5);
}

.courses-stat-content {
  position: relative;
  z-index: 1;
}

.courses-stat-value {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #ffffff, #e5e7eb);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.courses-stat-label {
  font-size: 0.95rem;
  font-weight: 600;
  color: #d1d5db;
  margin-bottom: 0.25rem;
  letter-spacing: 0.3px;
}

.courses-stat-desc {
  font-size: 0.8rem;
  color: #9ca3af;
  font-weight: 400;
}

.courses-stat-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-top: 1rem;
  border: 1px solid currentColor;
}

.courses-stat-badge ion-icon {
  font-size: 1rem;
}

/* Modern Courses Sessions Section */
.courses-sessions-section {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
}

.courses-sessions-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(220, 38, 38, 0.2);
}

.courses-sessions-title-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.courses-sessions-title-wrapper > ion-icon {
  font-size: 2rem;
  color: #dc2626;
  background: rgba(220, 38, 38, 0.15);
  padding: 12px;
  border-radius: 12px;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.courses-sessions-title-wrapper h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
  letter-spacing: -0.5px;
}

.courses-sessions-title-wrapper p {
  font-size: 0.875rem;
  color: #9ca3af;
  margin: 0.25rem 0 0;
}

.courses-total-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(220, 38, 38, 0.15);
  color: #dc2626;
  padding: 10px 18px;
  border-radius: 20px;
  font-size: 0.95rem;
  font-weight: 600;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.courses-total-badge ion-icon {
  font-size: 1.25rem;
}

.courses-table-wrapper {
  overflow-x: auto;
}

/* Enhanced Courses Table */
.courses-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 0.75rem;
}

.courses-table thead tr td {
  background: rgba(220, 38, 38, 0.1);
  color: #dc2626;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 1rem 1.5rem;
  border: none;
}

.courses-table thead tr td:first-child {
  border-radius: 12px 0 0 12px;
}

.courses-table thead tr td:last-child {
  border-radius: 0 12px 12px 0;
}

.courses-table tbody tr {
  background: linear-gradient(135deg, rgba(17, 24, 39, 0.6), rgba(17, 24, 39, 0.4));
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.courses-table tbody tr:hover {
  background: linear-gradient(135deg, rgba(220, 38, 38, 0.15), rgba(220, 38, 38, 0.08));
  transform: translateX(8px) scale(1.01);
  box-shadow: 0 8px 24px rgba(220, 38, 38, 0.2);
}

.courses-table tbody tr td {
  padding: 1.25rem 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-left: none;
  border-right: none;
}

.courses-table tbody tr td:first-child {
  border-radius: 12px 0 0 12px;
  border-left: 1px solid rgba(255, 255, 255, 0.05);
}

.courses-table tbody tr td:last-child {
  border-radius: 0 12px 12px 0;
  border-right: 1px solid rgba(255, 255, 255, 0.05);
}

/* ============================================
   SUBSCRIPTIONS PAGE STYLES
   ============================================ */

/* Modern Subscriptions Stats Grid */
.subs-stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-bottom: 2.5rem;
}

.subs-stat-card {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 1.75rem;
  position: relative;
  overflow: hidden;
  transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.subs-stat-card::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: var(--card-gradient);
  opacity: 0.05;
  transition: opacity 0.4s;
}

.subs-stat-card:hover::before {
  opacity: 0.12;
}

.subs-stat-card:hover {
  transform: translateY(-8px);
  box-shadow: 0 16px 48px rgba(0, 0, 0, 0.5);
  border-color: rgba(220, 38, 38, 0.4);
}

.subs-stat-icon-wrapper {
  position: absolute;
  top: 1.75rem;
  right: 1.75rem;
  width: 56px;
  height: 56px;
  background: var(--card-gradient);
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.75rem;
  color: white;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
  transition: all 0.4s;
}

.subs-stat-card:hover .subs-stat-icon-wrapper {
  transform: rotate(12deg) scale(1.1);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.5);
}

.subs-stat-content {
  position: relative;
  z-index: 1;
}

.subs-stat-value {
  font-size: 2.5rem;
  font-weight: 800;
  background: linear-gradient(135deg, #ffffff, #e5e7eb);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  line-height: 1;
  margin-bottom: 0.5rem;
}

.subs-stat-label {
  font-size: 0.95rem;
  font-weight: 600;
  color: #d1d5db;
  margin-bottom: 0.25rem;
  letter-spacing: 0.3px;
}

.subs-stat-desc {
  font-size: 0.8rem;
  color: #9ca3af;
  font-weight: 400;
}

.subs-stat-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 600;
  margin-top: 1rem;
  border: 1px solid currentColor;
}

.subs-stat-badge ion-icon {
  font-size: 1rem;
}

/* Modern Pending Requests Section */
.subs-pending-section,
.subs-history-section {
  background: linear-gradient(135deg, rgba(30, 41, 59, 0.95), rgba(15, 23, 42, 0.98));
  border: 1px solid rgba(220, 38, 38, 0.2);
  border-radius: 20px;
  padding: 2rem;
  margin-bottom: 2.5rem;
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
  backdrop-filter: blur(10px);
}

.subs-pending-header,
.subs-history-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
  padding-bottom: 1.5rem;
  border-bottom: 1px solid rgba(220, 38, 38, 0.2);
}

.subs-pending-title-wrapper,
.subs-history-title-wrapper {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.subs-pending-title-wrapper > ion-icon,
.subs-history-title-wrapper > ion-icon {
  font-size: 2rem;
  color: #dc2626;
  background: rgba(220, 38, 38, 0.15);
  padding: 12px;
  border-radius: 12px;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.subs-pending-title-wrapper h2,
.subs-history-title-wrapper h2 {
  font-size: 1.5rem;
  font-weight: 700;
  color: #ffffff;
  margin: 0;
  letter-spacing: -0.5px;
}

.subs-pending-title-wrapper p,
.subs-history-title-wrapper p {
  font-size: 0.875rem;
  color: #9ca3af;
  margin: 0.25rem 0 0;
}

.subs-pending-badge,
.subs-history-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(220, 38, 38, 0.15);
  color: #dc2626;
  padding: 10px 18px;
  border-radius: 20px;
  font-size: 0.95rem;
  font-weight: 600;
  border: 1px solid rgba(220, 38, 38, 0.3);
}

.subs-pending-badge ion-icon,
.subs-history-badge ion-icon {
  font-size: 1.25rem;
}

.subs-table-wrapper {
  overflow-x: auto;
}

/* Enhanced Subscriptions Table */
.subs-table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0 0.75rem;
}

.subs-table thead tr td {
  background: rgba(220, 38, 38, 0.1);
  color: #dc2626;
  font-weight: 600;
  font-size: 0.8rem;
  text-transform: uppercase;
  letter-spacing: 1px;
  padding: 1rem 1.5rem;
  border: none;
}

.subs-table thead tr td:first-child {
  border-radius: 12px 0 0 12px;
}

.subs-table thead tr td:last-child {
  border-radius: 0 12px 12px 0;
}

.subs-table tbody tr {
  background: linear-gradient(135deg, rgba(17, 24, 39, 0.6), rgba(17, 24, 39, 0.4));
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
}

.subs-table tbody tr:hover {
  background: linear-gradient(135deg, rgba(220, 38, 38, 0.15), rgba(220, 38, 38, 0.08));
  transform: translateX(8px) scale(1.01);
  box-shadow: 0 8px 24px rgba(220, 38, 38, 0.2);
}

.subs-table tbody tr td {
  padding: 1.25rem 1.5rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
  border-left: none;
  border-right: none;
}

.subs-table tbody tr td:first-child {
  border-radius: 12px 0 0 12px;
  border-left: 1px solid rgba(255, 255, 255, 0.05);
}

.subs-table tbody tr td:last-child {
  border-radius: 0 12px 12px 0;
  border-right: 1px solid rgba(255, 255, 255, 0.05);
}

@media (max-width: 768px) {
  .overview-cards-grid {
    grid-template-columns: 1fr;
  }

  .modern-actions-grid {
    grid-template-columns: 1fr;
  }

  .timeline-time {
    display: none;
  }

  .users-stats-grid {
    grid-template-columns: 1fr;
  }

  .users-form-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .users-list-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .users-table tbody tr:hover {
    transform: translateX(4px) scale(1);
  }

  .action-buttons {
    flex-direction: column;
    width: 100%;
  }

  .action-buttons .btn {
    width: 100%;
    justify-content: center;
  }

  .courses-stats-grid {
    grid-template-columns: 1fr;
  }

  .courses-sessions-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .courses-table tbody tr:hover {
    transform: translateX(4px) scale(1);
  }

  .subs-stats-grid {
    grid-template-columns: 1fr;
  }

  .subs-pending-header,
  .subs-history-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .subs-table tbody tr:hover {
    transform: translateX(4px) scale(1);
  }
}
</style>
