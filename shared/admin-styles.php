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
    margin-bottom: 3rem;
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
  }

  /* Activity List */
  .activity-list {
    list-style: none;
  }

  .activity-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem;
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    margin-bottom: 0.75rem;
    transition: all 0.3s;
  }

  .activity-item:hover {
    background: rgba(0, 0, 0, 0.5);
    border-color: rgba(220, 38, 38, 0.3);
    transform: translateX(4px);
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
  }

  @media (max-width: 640px) {
    .stats-grid {
      grid-template-columns: 1fr;
    }
    .form-grid {
      grid-template-columns: 1fr;
    }
  }
</style>
