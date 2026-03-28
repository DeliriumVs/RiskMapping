<?php
// src/db.php

$host = 'db';
$dbname = 'riskmapping_db';
$user = 'rm_admin';
$pass = 'rm_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fonction centrale pour la journalisation des événements (Audit Trail)
 * @param PDO $pdo L'objet de connexion base de données
 * @param int|null $user_id L'ID de l'admin (null si action système ou non connecté)
 * @param string $action Le code de l'action (ex: LOGIN_SUCCESS, DELETE_RISK)
 * @param string $details Détails lisibles de l'action
 */
function log_audit($pdo, $user_id, $action, $details = '') {
    // Récupération de l'adresse IP (gère aussi les proxys basiques)
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (PDOException $e) {
        // En prod, on pourrait écrire dans un fichier log si la BDD tombe.
        // Ici on ignore silencieusement pour ne pas bloquer l'application.
    }
}
?>
