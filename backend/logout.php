<?php
declare(strict_types=1);
ini_set('display_errors','1');
error_reporting(E_ALL);

// Démarre la session si pas déjà active
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Vider toutes les variables de session
$_SESSION = [];

// Supprimer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Détruire la session
session_destroy();

// Redirection vers la page de login
header("Location: /MyGym/frontend/login/login.html?logout=success");
exit;
