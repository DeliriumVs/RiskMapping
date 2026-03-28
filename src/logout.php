<?php
// src/logout.php
require 'db.php';

// Si c'est un expert (Admin/Animateur/Lecteur) qui se déconnecte, on trace !
if (isset($_SESSION['admin_id'])) {
    $username = $_SESSION['admin_username'] ?? 'Inconnu';
    log_audit($pdo, $_SESSION['admin_id'], 'LOGOUT', "Déconnexion volontaire de l'utilisateur : $username");
}

// Détruit toutes les variables de session
session_unset();

// Détruit la session elle-même
session_destroy();

// Redirection vers l'accueil public
header("Location: index.php");
exit;
?>
