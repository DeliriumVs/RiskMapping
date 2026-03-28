<?php
// src/admin_login.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

// AUTO-RÉPARATION AVEC ARGON2id
$stmtCount = $pdo->query("SELECT COUNT(*) FROM admin_users");
if ($stmtCount->fetchColumn() == 0) {
    $hash = password_hash('EBIOSRM', PASSWORD_ARGON2ID);
    $pdo->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES ('admin', ?, 'admin')")->execute([$hash]);
    log_audit($pdo, null, 'SYSTEM_INIT', "Création automatique du compte super-admin par défaut.");
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // On récupère is_locked en plus du reste
    $stmt = $pdo->prepare("SELECT id, password_hash, role, is_locked FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password_hash'])) {
        
        // VÉRIFICATION 1 : COMPTE VERROUILLÉ
        if ($user['is_locked'] == 1) {
            log_audit($pdo, $user['id'], 'LOGIN_BLOCKED', "Tentative de connexion refusée (Compte verrouillé).");
            $erreur = "🚫 Votre compte a été verrouillé par un Administrateur. Accès refusé.";
        } 
        // VÉRIFICATION 2 : COMPTE EN ATTENTE
        elseif ($user['role'] === 'en_attente') {
            log_audit($pdo, $user['id'], 'LOGIN_BLOCKED', "Tentative de connexion refusée (Compte en attente d'approbation).");
            $erreur = "⏳ Votre compte est toujours en attente de validation par un Administrateur.";
        } 
        // VÉRIFICATION 3 : C'EST TOUT BON
        else {
            $_SESSION['role'] = 'MJ'; 
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_role'] = $user['role'];
            $_SESSION['admin_username'] = $username;
            
            if (password_needs_rehash($user['password_hash'], PASSWORD_ARGON2ID)) {
                $newHash = password_hash($password, PASSWORD_ARGON2ID);
                $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")->execute([$newHash, $user['id']]);
            }
            
            log_audit($pdo, $user['id'], 'LOGIN_SUCCESS', "Connexion réussie.");
            header("Location: registre_risques.php");
            exit;
        }
        
    } else {
        log_audit($pdo, null, 'LOGIN_FAILED', "Tentative échouée pour l'utilisateur : " . $username);
        $erreur = "Identifiants incorrects.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RiskMapping - Connexion Expert</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 450px; margin-top: 10vh;">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 4rem; margin-bottom: 10px;">🛡️</div>
            <h1 style="color: #3b82f6; margin: 0;">Équipe Sécurité</h1>
            <p class="subtitle">Espace Backoffice (RBAC Actif)</p>
        </div>
        
        <?php if ($erreur): ?>
            <div style="background-color: rgba(255, 68, 68, 0.2); border: 1px solid var(--accent-red); padding: 15px; border-radius: 4px; color: var(--accent-red); margin-bottom: 15px; text-align: center; font-weight:bold;">
                <?= $erreur ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="admin_login.php" style="background: #161b22; padding: 30px; border-radius: 8px; border: 1px solid #30363d;">
            
            <div style="margin-bottom: 20px;">
                <label for="username" style="color: #c9d1d9; font-weight: bold; display: block; margin-bottom: 8px;">Adresse Email / ID :</label>
                <input type="text" id="username" name="username" placeholder="admin" required style="width: 100%; box-sizing: border-box; padding: 12px; background: #0d1117; color: #fff; border: 1px solid #30363d; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 30px;">
                <label for="password" style="color: #c9d1d9; font-weight: bold; display: block; margin-bottom: 8px;">Mot de passe :</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required style="width: 100%; box-sizing: border-box; padding: 12px; background: #0d1117; color: #fff; border: 1px solid #30363d; border-radius: 4px;">
            </div>
            
            <button type="submit" class="btn btn-mj" style="width: 100%; font-size: 1.1rem; padding: 12px; background: #3b82f6; border-color: #3b82f6;">Authentification sécurisée</button>
        </form>
        
        <div style="display:flex; justify-content:space-between; margin-top: 20px; padding: 0 10px;">
            <a href="index.php" style="color: #8b949e; text-decoration: none; font-size: 0.9rem;">← Accueil public</a>
            <a href="admin_register.php" style="color: #3b82f6; text-decoration: none; font-size: 0.9rem; font-weight:bold;">Demander un accès ➔</a>
        </div>
        
    </div>
</body>
</html>
