<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
$row = $pdo->query("SELECT @@hostname host, @@port port, VERSION() ver")->fetch();
echo "Host: {$row['host']} — Port: {$row['port']} — MySQL: {$row['ver']}";
