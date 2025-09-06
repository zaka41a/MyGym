<?php
// Démarre la session si elle n'est pas déjà active
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Sécurise les valeurs de session (évite toute injection HTML dans l'affichage)
$username = htmlspecialchars($_SESSION['username'] ?? '');
$role     = htmlspecialchars($_SESSION['role'] ?? '');
?>
<!doctype html>
<html lang="en"> <!-- Interface en anglais -->
<head>
  <meta charset="utf-8">
  <title>MyGym</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Ressources : favicon + Bootstrap + CSS -->
  <link rel="icon" type="image/png" href="/MyGym/frontend/login/images/icons/favicon.ico"/>
  <link rel="stylesheet" href="/MyGym/frontend/login/vendor/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="/MyGym/frontend/login/css/main.css">

  <style>
    /* Barre de navigation supérieure sombre avec texte blanc */
    .topbar {background:#111; color:#fff; padding:.6rem 1rem;}
    .topbar a {color:#fff; text-decoration:none;}
  </style>
</head>
<body>
  <!-- Topbar avec nom de l’application, rôle de l’utilisateur et bouton logout -->
  <div class="topbar d-flex justify-content-between align-items-center">
    <div>
      <strong>MyGym</strong>
      — <small>
        <?php 
          // Affiche le rôle, ou "Visitor" par défaut
          echo $role !== '' ? $role : 'Visitor'; 
        ?>
      </small>
    </div>

    <div>
      <span class="me-3">
        👤 
        <?php 
          // Affiche le nom d’utilisateur, ou "Guest" si vide
          echo $username !== '' ? $username : 'Guest'; 
        ?>
      </span>
      <!-- Lien pour se déconnecter -->
      <a href="/MyGym/backend/logout.php" title="Sign out">Sign out</a>
    </div>
  </div>

  <!-- Conteneur principal Bootstrap -->
  <div class="container py-4">
    <!-- Ici tu ajoutes ton contenu de page -->
    <h1 class="mb-4">Welcome to MyGym</h1>
    <p>This is your gym management system dashboard.</p>
  </div>

  <!-- Chargement des scripts Bootstrap -->
  <script src="/MyGym/frontend/login/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
