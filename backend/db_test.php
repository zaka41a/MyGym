<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';

try {
  $row = $pdo->query('SELECT NOW() AS t')->fetch();
  echo "✅ Database connected! Current time: " . $row['t'];
} catch (Throwable $e) {
  echo "❌ Database connection failed: " . $e->getMessage();
}
