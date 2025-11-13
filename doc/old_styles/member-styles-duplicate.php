<style>

/* ========================================
   CLASSES OVERVIEW HERO (courses.php)
   ======================================== */

.classes-overview-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 2rem;
  padding: 2.5rem 0;
  margin-bottom: 3rem;
  position: relative;
}

.classes-overview-hero::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--member-border), transparent);
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
  color: #ffffff;
  line-height: 1;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.classes-hero-label {
  font-size: 0.875rem;
  color: #cbd5e1;
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
  padding: 3rem 0;
  margin-bottom: 3rem;
  position: relative;
}

.subscription-status-hero::after {
  content: '';
  position: absolute;
  bottom: 0;
  left: 0;
  right: 0;
  height: 1px;
  background: linear-gradient(90deg, transparent, var(--member-border), transparent);
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
  background: linear-gradient(135deg, rgba(239, 68, 68, 0.2), rgba(220, 38, 38, 0.3));
  border: 3px solid var(--member-primary);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 3rem;
  color: var(--member-primary);
  flex-shrink: 0;
  box-shadow: 0 12px 32px rgba(239, 68, 68, 0.4);
}

.subscription-hero-content {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.subscription-hero-plan {
  font-size: 3rem;
  font-weight: 900;
  color: #ffffff;
  line-height: 1;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
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
  color: #ffffff;
  line-height: 1;
  text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
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
