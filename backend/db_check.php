<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/db.php';
$r = $pdo->query("SELECT DATABASE() db, @@hostname host, @@port port, VERSION() ver")->fetch();
echo "DB={$r['db']} — Host={$r['host']} — Port={$r['port']} — Ver={$r['ver']}";
