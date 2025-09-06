<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php'; // démarre la session
header('Content-Type: text/plain; charset=utf-8');
var_dump($_SESSION['user'] ?? null);
