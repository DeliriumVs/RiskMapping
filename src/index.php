<?php
// src/index.php
require 'db.php';

// Redirection automatique si déjà connecté
if (isset($_SESSION['role']) && $_SESSION['role'] === 'MJ') {
    header("Location: " . (empty($_SESSION['analyse_id']) ? "select_analyse.php" : "registre_risques.php"));
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RiskMapping — Analyse de Risques EBIOS RM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .landing-card { background: #161b22; border: 1px solid #30363d; border-radius: 12px; padding: 50px 40px; text-align: center; max-width: 480px; width: 100%; }
        .landing-icon { font-size: 4rem; margin-bottom: 20px; }
        .landing-desc { color: #8b949e; margin-bottom: 32px; line-height: 1.6; font-size: 1rem; }
        .landing-footer { margin-top: 32px; color: #484f58; font-size: 0.8rem; }
    </style>
</head>
<body>
    <div class="landing-card">
        <div class="landing-icon">🧭</div>
        <h1 style="color: #3b82f6; margin: 0 0 8px 0; font-size: 2rem;">RiskMapping</h1>
        <p style="color: #8b949e; margin: 0 0 30px 0; font-size: 0.95rem;">Plateforme d'Analyse de Risques EBIOS RM</p>

        <p class="landing-desc">Espace réservé aux Risk Managers et analystes sécurité.<br>Accès au registre des risques, référentiels EBIOS RM et pilotage des analyses.</p>

        <a href="admin_login.php" class="btn btn-mj" style="display: block; font-size: 1.1rem; padding: 14px; background: #3b82f6; border: none; color: white; border-radius: 6px; text-decoration: none;">🔐 Connexion</a>

        <div class="landing-footer">
            RiskMapping Suite • Inspiré de la méthode <strong style="color: #8b949e;">EBIOS RM (ANSSI)</strong>
        </div>
    </div>
</body>
</html>
