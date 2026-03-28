<?php
// src/index.php
require 'db.php';

// --- LE MÉCANISME DE ROUTAGE AUTOMATIQUE ---
// Si l'utilisateur a déjà une session active, on le renvoie directement à sa place
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'MJ') {
        if (isset($_SESSION['session_id'])) {
            header("Location: mj_dashboard.php");
        } else {
            header("Location: registre_risques.php"); // Le hub central du Risk Manager
        }
        exit;
    } elseif ($_SESSION['role'] === 'participant' && isset($_SESSION['session_id'])) {
        $stmt = $pdo->prepare("SELECT statut FROM sessions WHERE id = ?");
        $stmt->execute([$_SESSION['session_id']]);
        $sess_statut = $stmt->fetchColumn();
        
        if (in_array($sess_statut, ['discussion', 'termine'])) {
            header("Location: participant_view.php"); 
        } else {
            header("Location: saisie_cauchemar.php"); 
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RiskMapping - Analyse de Risques EBIOS RM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .split-container { display: flex; gap: 30px; margin-top: 40px; align-items: stretch; flex-wrap: wrap; }
        .card { flex: 1; min-width: 300px; background: #161b22; border: 1px solid #30363d; border-radius: 8px; padding: 40px 30px; text-align: center; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s, border-color 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card-icon { font-size: 4rem; margin-bottom: 20px; }
        .card-desc { color: #8b949e; margin-bottom: 30px; line-height: 1.5; }
        
        .card-part:hover { border-color: var(--accent-green); }
        .card-mj:hover { border-color: #3b82f6; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px;">
        <h1 style="text-align: center; color: #fff; font-size: 2.5rem; margin-bottom: 10px;">🧭 RiskMapping</h1>
        <p class="subtitle" style="text-align: center; font-size: 1.2rem;">Plateforme d'Analyse de Risques & Ateliers Collaboratifs</p>
        
        <div class="split-container">
            
            <div class="card card-part">
                <div>
                    <div class="card-icon">👥</div>
                    <h2 style="color: var(--accent-green); margin-top: 0;">Collaborateurs</h2>
                    <p class="card-desc">Vous avez été invité à participer à un atelier d'identification des risques (Module interactif).</p>
                </div>
                <a href="join.php" class="btn btn-part" style="width: 100%; box-sizing: border-box; font-size: 1.2rem; padding: 15px;">🎟️ Rejoindre avec un Code PIN</a>
            </div>
            
            <div class="card card-mj">
                <div>
                    <div class="card-icon">🛡️</div>
                    <h2 style="color: #3b82f6; margin-top: 0;">Équipe Sécurité</h2>
                    <p class="card-desc">Espace réservé aux Risk Managers. Accès au registre global, référentiels EBIOS RM et pilotage des ateliers.</p>
                </div>
                <a href="admin_login.php" class="btn btn-mj" style="width: 100%; box-sizing: border-box; font-size: 1.2rem; padding: 15px; background: #3b82f6; border: none; color: white;">🔐 Connexion </a>
            </div>
            
        </div>
        
        <div style="text-align: center; margin-top: 40px; color: #484f58; font-size: 0.85rem;">
            <p style="color: #8b949e; font-size: 0.9rem; margin-top: 20px;">
                RiskMapping Suite • Inspiré de la méthode <strong>EBIOS RM (ANSSI)</strong>
            </p>
        </div>
    </div>
</body>
</html>
