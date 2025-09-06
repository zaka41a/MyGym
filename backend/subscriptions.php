<?php
declare(strict_types=1);

/**
 * Fonctions abonnement (BACKEND UNIQUEMENT)
 * Tables:
 *  - plans(id, code, name, price_cents, features)
 *  - subscriptions(id, user_id, plan_id, status, start_date, end_date, approved_by, created_at)
 * Statuts: PENDING | ACTIVE | CANCELLED | EXPIRED
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

/** retourne la souscription ACTIVE en cours, sinon null */
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

/** true si membre abonné actif */
function is_subscribed(PDO $pdo, int $userId): bool {
  return get_active_subscription($pdo, $userId) !== null;
}

/** retourne la dernière demande PENDING du user (si existe) */
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
 * Retourne la souscription ACTIVE (aujourd’hui) avec plan.
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
 * TRUE si l’utilisateur a accès aux cours/coach (plans PLUS/PRO), FALSE sinon (BASIC).
 */
function has_class_access(PDO $pdo, int $userId): bool {
  $sub = get_current_active_with_plan($pdo, $userId);
  if (!$sub) return false;
  $code = strtoupper((string)($sub['plan_code'] ?? ''));
  return in_array($code, ['PLUS','PRO'], true);
}
