<?php
$DB_HOST = '127.0.0.1';
$DB_PORT = 3306;
$DB_NAME = 'mygym';
$DB_USER = 'mygym';
$DB_PASS = 'MyGym123!';

$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

$dsn = "mysql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_NAME;charset=utf8mb4";
$pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
