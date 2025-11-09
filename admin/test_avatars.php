<?php
declare(strict_types=1);

require_once __DIR__ . '/../backend/auth.php';
requireRole('ADMIN');
require_once __DIR__ . '/../backend/db.php';

function avatar_url_for_user_test(array $u): ?string {
  $rootFS      = dirname(__DIR__);
  $uploadFS    = $rootFS . '/uploads/avatars';
  $uploadWeb   = '/MyGym/uploads/avatars';

  echo "<pre>Testing user ID: {$u['id']}, Role: {$u['role']}\n";
  echo "Root FS: $rootFS\n";
  echo "Upload FS: $uploadFS\n";
  echo "Upload Web: $uploadWeb\n";

  // Check avatar column first
  if (!empty($u['avatar'])) {
    $url = $uploadWeb . '/' . basename((string)$u['avatar']) . '?t=' . time();
    echo "Found in DB avatar column: $url\n</pre>";
    return $url;
  }

  // Check for user_ID.ext files
  $id = (int)($u['id'] ?? 0);
  if ($id > 0) {
    foreach (['jpg','png','webp','jpeg'] as $ext) {
      $p = $uploadFS . "/user_{$id}.{$ext}";
      echo "Checking: $p ... ";
      if (is_file($p)) {
        $url = $uploadWeb . "/user_{$id}.{$ext}?t=" . time();
        echo "FOUND! URL: $url\n</pre>";
        return $url;
      }
      echo "NOT FOUND\n";
    }
  }

  echo "No avatar found, returning placeholder\n</pre>";
  return 'https://via.placeholder.com/40x40?text=U';
}

$stmt = $pdo->query("SELECT id, fullname, role, avatar FROM users ORDER BY id LIMIT 5");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Test Avatars</title>
  <style>
    body { font-family: monospace; padding: 20px; background: #222; color: #fff; }
    img { border: 2px solid lime; margin: 10px; }
    .test { background: #333; padding: 10px; margin: 10px 0; }
  </style>
</head>
<body>
  <h1>Avatar Test Page</h1>

  <?php foreach ($users as $u): ?>
    <div class="test">
      <h3><?= htmlspecialchars($u['fullname']) ?> (ID: <?= $u['id'] ?>)</h3>
      <?php $url = avatar_url_for_user_test($u); ?>
      <p><strong>URL returned:</strong> <?= htmlspecialchars($url) ?></p>
      <p><strong>Display test:</strong></p>
      <img src="<?= htmlspecialchars($url) ?>" alt="avatar" width="40" height="40">
    </div>
  <?php endforeach; ?>
</body>
</html>
