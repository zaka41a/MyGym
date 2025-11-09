<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('ADMIN'); // FR : page réservée à l'ADMIN
require_once __DIR__ . '/../backend/db.php';

/* ===== Debug local (mets false en prod) =====
   FR : utilitaire de debug local (désactivé en prod) */
$DEBUG = false;
if ($DEBUG) { error_reporting(E_ALL); ini_set('display_errors', '1'); }

/* ===== Helpers =====
   FR : fonctions d'aide (détection colonne, + robustesse aux schémas partiels) */
function col_exists(PDO $pdo, string $table, string $col): bool {
  $q = $pdo->prepare("
    SELECT 1 FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t AND COLUMN_NAME = :c
    LIMIT 1
  ");
  $q->execute([':t'=>$table, ':c'=>$col]);
  return (bool)$q->fetchColumn();
}

/* ===== Période (défaut : mois courant) =====
   FR : fenêtre temporelle filtrant les cours à afficher (GET facultatif) */
$start = new DateTime('first day of this month 00:00:00');
$end   = new DateTime('last day of this month 23:59:59');
if (!empty($_GET['start'])) { try { $start = new DateTime($_GET['start'].' 00:00:00'); } catch (Throwable $e) {} }
if (!empty($_GET['end']))   { try { $end   = new DateTime($_GET['end']  .' 23:59:59'); } catch (Throwable $e) {} }

/* ===== Requête tolérante =====
   FR : construit dynamiquement le SELECT/JOINS selon colonnes disponibles
        -> évite un crash si certaines colonnes/tables manquent */
$sqlError = null;
$sessions = [];

try {
  $hasStart   = col_exists($pdo, 'sessions', 'start_at');
  $hasEnd     = col_exists($pdo, 'sessions', 'end_at');
  $hasCap     = col_exists($pdo, 'sessions', 'capacity');
  $hasActId   = col_exists($pdo, 'sessions', 'activity_id');
  $hasCoachId = col_exists($pdo, 'sessions', 'coach_id');

  if (!$hasStart) {
    // FR : garde-fou si la table sessions n'a pas start_at
    throw new RuntimeException("La colonne sessions.start_at est introuvable. Vérifie la table 'sessions'.");
  }

  $select = ["s.id", "s.start_at"];
  if ($hasEnd) $select[] = "s.end_at";
  if ($hasCap) $select[] = "s.capacity";

  // FR : jointure activités (si activity_id existe), sinon jointure neutre
  $select[] = "a.code AS activity_code";
  $select[] = "a.name AS activity_name";
  $joinAct = $hasActId ? "LEFT JOIN activities a ON a.id = s.activity_id" : "LEFT JOIN activities a ON 1=0";

  // FR : jointure coach (si coach_id existe), sinon jointure neutre
  $select[] = "u.fullname AS coach_name";
  $joinCoach = $hasCoachId ? "LEFT JOIN users u ON u.id = s.coach_id" : "LEFT JOIN users u ON 1=0";

  // FR : comptage des réservations (filtre par statut si possible)
  $hasRes       = col_exists($pdo, 'reservations', 'session_id');
  $hasResStatus = $hasRes && col_exists($pdo, 'reservations', 'status');
  $bookedSql = "0 AS booked";
  if ($hasRes) {
    $bookedSql = $hasResStatus
      ? "(SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id AND r.status IN ('BOOKED','ATTENDED')) AS booked"
      : "(SELECT COUNT(*) FROM reservations r WHERE r.session_id = s.id) AS booked";
  }
  $select[] = $bookedSql;

  // FR : requête finale, bornée par la période choisie
  $sql = "
    SELECT ".implode(",\n           ", $select)."
    FROM sessions s
    $joinAct
    $joinCoach
    WHERE s.start_at BETWEEN :d1 AND :d2
    ORDER BY s.start_at ASC
  ";

  $st = $pdo->prepare($sql);
  $st->execute([
    ':d1' => $start->format('Y-m-d H:i:s'),
    ':d2' => $end->format('Y-m-d H:i:s'),
  ]);
  $sessions = $st->fetchAll(PDO::FETCH_ASSOC);

} catch (Throwable $e) {
  // FR : capture du message d'erreur pour affichage non bloquant
  $sqlError = $e->getMessage();
  if ($DEBUG) error_log("ADMIN/courses.php SQL ERROR: ".$sqlError);
}
?>
<!DOCTYPE html>
<html lang="fr"><!-- FR : attribut non visible, gardé tel quel -->
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>MyGym — Classes (Admin)</title> <!-- EN : titre page -->
  <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
  <style>
    @import url("https://fonts.googleapis.com/css2?family=Ubuntu:wght@300;400;500;700&display=swap");
    :root{
      --primary:#e50914;--primary-600:#cc0812;--white:#fff;--gray:#f5f5f5;
      --border:#e9e9e9;--black1:#222;--black2:#999;--shadow:0 7px 25px rgba(0,0,0,.08)
    }
    *{box-sizing:border-box;margin:0;padding:0;font-family:"Ubuntu",system-ui,Segoe UI,Roboto,sans-serif}
    body{background:var(--gray);color:var(--black1);min-height:100vh;overflow-x:hidden}
    .container{position:relative;width:100%}
    .navigation{position:fixed;width:300px;height:100%;background:var(--primary);overflow:hidden;box-shadow:var(--shadow)}
    .navigation ul{position:absolute;inset:0}
    .navigation ul li{list-style:none}
    .navigation ul li a{display:flex;width:100%;text-decoration:none;color:#fff;align-items:center;padding-left:10px;height:60px}
    .navigation ul li a .icon{min-width:50px;text-align:center}
    /* ✅ Même CSS que dans index.php */
    .navigation ul li a .icon ion-icon{font-size:1.5rem;color:#fff}
    .navigation ul li a .title{white-space:nowrap}
    .navigation ul li:hover,.navigation ul li.active{background:var(--primary-600)}
    .main{position:absolute;left:300px;width:calc(100% - 300px);min-height:100vh;background:#fff}
    .topbar{height:60px;display:flex;align-items:center;justify-content:space-between;padding:0 16px;border-bottom:1px solid var(--border)}
    .wrap{max-width:1200px;margin:0 auto;padding:20px}
    .panel{background:#fff;border:1px solid var(--border);border-radius:12px;padding:18px;box-shadow:var(--shadow)}
    .alert{padding:10px;border-radius:8px;margin-bottom:10px}
    .err{background:#fdecea;border:1px solid #f5c6cb}
    table{width:100%;border-collapse:collapse;margin-top:10px}
    thead td{font-weight:700}
    tr{border-bottom:1px solid #eee}
    td{padding:10px;vertical-align:middle}
    .muted{color:#777}
    @media (max-width:991px){.main{left:0;width:100%}}
  </style>
</head>
<body>
<div class="container">
  <!-- FR : Barre latérale — uniquement traduction des libellés visibles -->
  <div class="navigation">
    <ul>
      <li><a href="index.php"><span class="icon"><ion-icon name="home-outline"></ion-icon></span><span class="title">Dashboard</span></a></li>
      <li><a href="users.php"><span class="icon"><ion-icon name="people-outline"></ion-icon></span><span class="title">Users</span></a></li> <!-- EN : Utilisateurs -->
      <li class="active"><a href="courses.php"><span class="icon"><ion-icon name="calendar-outline"></ion-icon></span><span class="title">Classes</span></a></li> <!-- EN : Cours -->
      <li><a href="subscriptions.php"><span class="icon"><ion-icon name="card-outline"></ion-icon></span><span class="title">Subscriptions & Payments</span></a></li> <!-- EN : Abonnements & Paiements -->
      <li><a href="/MyGym/backend/logout.php"><span class="icon"><ion-icon name="log-out-outline"></ion-icon></span><span class="title">Logout</span></a></li> <!-- EN : Déconnexion -->
    </ul>
  </div>

  <!-- FR : En-tête de la zone principale -->
  <div class="main">
    <div class="topbar">
      <div style="width:60px;text-align:center"><ion-icon name="menu-outline" style="font-size:2rem"></ion-icon></div>
      <div style="font-weight:700;color:var(--primary)">Classes — Administration</div> <!-- EN : libellé topbar -->
    </div>

    <div class="wrap">
      <div class="panel">
        <h2 style="margin:0 0 8px">
          All sessions (<?= htmlspecialchars($start->format('d/m/Y')) ?> → <?= htmlspecialchars($end->format('d/m/Y')) ?>) <!-- EN : titre bloc -->
        </h2>

        <?php if ($sqlError): ?>
          <div class="alert err">SQL error: <?= htmlspecialchars($sqlError) ?></div> <!-- EN : message erreur -->
        <?php endif; ?>

        <!-- FR : Tableau récapitulatif des créneaux -->
        <table>
          <thead>
            <tr>
              <td style="width:140px">Date</td>
              <td style="width:120px">Time</td>         <!-- EN : Heure -->
              <td>Activity</td>                         <!-- EN : Activité -->
              <td style="width:160px">Coach</td>
              <td style="width:90px">Booked</td>        <!-- EN : Réservés -->
              <td style="width:90px">Capacity</td>      <!-- EN : Capacité -->
            </tr>
          </thead>
          <tbody>
          <?php if (!$sessions): ?>
            <tr><td colspan="6" class="muted" style="text-align:center">No sessions in this period.</td></tr> <!-- EN : état vide -->
          <?php else: foreach ($sessions as $s):
              // FR : formatages simples d'affichage
              $date  = !empty($s['start_at']) ? (new DateTime($s['start_at']))->format('d/m/Y') : '—';
              $heure = '—';
              if (!empty($s['start_at'])) {
                $sd = new DateTime($s['start_at']); $hi = $sd->format('H:i');
                $hf = !empty($s['end_at']) ? (new DateTime($s['end_at']))->format('H:i') : '—';
                $heure = $hi.' → '.$hf;
              }
              $cap   = (int)($s['capacity']      ?? 0);
              $book  = (int)($s['booked']        ?? 0);
              $act   = (string)($s['activity_name'] ?? '');
              $actC  = (string)($s['activity_code'] ?? '');
              $coach = (string)($s['coach_name']    ?? '—');
          ?>
            <tr>
              <td><?= htmlspecialchars($date) ?></td>
              <td><?= htmlspecialchars($heure) ?></td>
              <td><?= htmlspecialchars($act) ?> <span class="muted">(<?= htmlspecialchars($actC) ?>)</span></td>
              <td><?= htmlspecialchars($coach ?: '—') ?></td>
              <td><?= $book ?></td>
              <td><?= $cap ?></td>
            </tr>
          <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>
</body>
</html>
