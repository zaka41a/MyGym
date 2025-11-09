<?php
declare(strict_types=1);

/**
 * Subscription functions (BACKEND ONLY)
 * Tables:
 *  - plans(id, code, name, price_cents, features)
 *  - subscriptions(id, user_id, plan_id, status, start_date, end_date, approved_by, created_at)
 * Statuses: PENDING | ACTIVE | CANCELLED | EXPIRED
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/** Returns current ACTIVE subscription, otherwise null */
function get_active_subscription(PDO $pdo, int $userId): ?array {
  $stmt = $pdo->prepare("
    SELECT s.*, p.name AS plan_name, p.code AS plan_code, p.price_cents
      FROM subscriptions s
      JOIN plans p ON p.id = s.plan_id
     WHERE s.user_id = :uid
       AND s.status  = 'ACTIVE'
       AND (s.start_date IS NULL OR s.start_date <= CURRENT_DATE())
       AND (s.end_date   IS NULL OR s.end_date   >= CURRENT_DATE())
  ORDER BY s.id DESC
     LIMIT 1
  ");
  $stmt->execute([':uid'=>$userId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

/** True if member has active subscription */
function is_subscribed(PDO $pdo, int $userId): bool {
  return get_active_subscription($pdo, $userId) !== null;
}

/** Returns user's latest PENDING request (if exists) */
function get_pending_request(PDO $pdo, int $userId): ?array {
  $stmt = $pdo->prepare("
    SELECT s.*, p.name AS plan_name, p.code AS plan_code
      FROM subscriptions s
      JOIN plans p ON p.id = s.plan_id
     WHERE s.user_id = :uid
       AND s.status  = 'PENDING'
  ORDER BY s.created_at DESC
     LIMIT 1
  ");
  $stmt->execute([':uid'=>$userId]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

/**
 * Returns current ACTIVE subscription (today) with plan details.
 */
function get_current_active_with_plan(PDO $pdo, int $userId): ?array {
  $sql = "
    SELECT s.*, p.code AS plan_code, p.name AS plan_name
      FROM subscriptions s
      JOIN plans p ON p.id = s.plan_id
     WHERE s.user_id = :u
       AND s.status  = 'ACTIVE'
       AND (s.start_date IS NULL OR s.start_date <= CURRENT_DATE())
       AND (s.end_date   IS NULL OR s.end_date   >= CURRENT_DATE())
  ORDER BY s.id DESC
     LIMIT 1
  ";
  $st = $pdo->prepare($sql);
  $st->execute([':u'=>$userId]);
  $row = $st->fetch(PDO::FETCH_ASSOC);
  return $row ?: null;
}

/**
 * TRUE if user has access to classes/coach (PLUS/PRO plans), FALSE otherwise (BASIC).
 */
function has_class_access(PDO $pdo, int $userId): bool {
  $sub = get_current_active_with_plan($pdo, $userId);
  if (!$sub) return false;
  $code = strtoupper((string)($sub['plan_code'] ?? ''));
  return in_array($code, ['PLUS','PRO'], true);
}

/**
 * Automatically marks expired subscriptions (end_date < today) as EXPIRED
 * Returns number of updated subscriptions
 */
function expire_old_subscriptions(PDO $pdo): int {
  $stmt = $pdo->prepare("
    UPDATE subscriptions
       SET status = 'EXPIRED'
     WHERE status = 'ACTIVE'
       AND end_date IS NOT NULL
       AND end_date < CURRENT_DATE()
  ");
  $stmt->execute();
  return $stmt->rowCount();
}
