<?php
// src/admin_register.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

$message = '';
$succes = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $motif = trim($_POST['motif']);
    
    // Validation stricte OWASP du mot de passe
    $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/';
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "⚠️ Veuillez saisir une adresse email valide.";
    } elseif (!preg_match($regex, $password)) {
        $message = "⚠️ Le mot de passe ne respecte pas la politique de sécurité (Min 12, Maj, Min, Chiffre, Spécial).";
    } elseif (empty($motif)) {
        $message = "⚠️ Veuillez indiquer le motif de votre demande d'accès.";
    } else {
        try {
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role, motif_demande) VALUES (?, ?, 'en_attente', ?)");
            $stmt->execute([$email, $hash, $motif]);
            
            $new_user_id = $pdo->lastInsertId();
            log_audit($pdo, $new_user_id, 'ACCOUNT_REQUESTED', "Demande d'accès pour : $email");
            
            $succes = true;
        } catch (PDOException $e) {
            $message = "⚠️ Cette adresse email est déjà enregistrée.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RiskMapping - Demande d'accès</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 500px; margin-top: 8vh;">
        
        <div style="text-align: center; margin-bottom: 25px;">
            <div style="font-size: 3rem; margin-bottom: 10px;">🔐</div>
            <h1 style="color: #3b82f6; margin: 0;">Demande d'accès Expert</h1>
            <p class="subtitle">Votre compte sera soumis à validation.</p>
        </div>
        
        <?php if ($succes): ?>
            <div style="background-color: rgba(0, 255, 204, 0.1); border: 1px solid var(--accent-green); padding: 20px; border-radius: 8px; color: var(--accent-green); text-align: center;">
                <h3 style="margin-top:0;">✅ Demande envoyée !</h3>
                <p style="color:#c9d1d9;">Votre compte est créé, mais il est <strong>en attente d'approbation</strong>. Un administrateur doit valider votre niveau d'habilitation avant que vous ne puissiez vous connecter.</p>
                <a href="admin_login.php" class="btn btn-mj" style="display:inline-block; margin-top:15px; background: #3b82f6; border:none; text-decoration:none;">Retour à la connexion</a>
            </div>
        <?php else: ?>
        
            <?php if ($message): ?>
                <div style="background-color: rgba(255, 68, 68, 0.2); border: 1px solid var(--accent-red); padding: 10px; border-radius: 4px; color: var(--accent-red); margin-bottom: 15px; text-align: center;">
                    <?= $message ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="admin_register.php" style="background: #161b22; padding: 30px; border-radius: 8px; border: 1px solid #30363d;">
                
                <div style="margin-bottom: 20px;">
                    <label for="email" style="color: #c9d1d9; font-weight: bold; display: block; margin-bottom: 8px;">Adresse Email pro :</label>
                    <input type="email" id="email" name="email" placeholder="prenom.nom@entreprise.com" required style="width: 100%; box-sizing: border-box; padding: 12px; background: #0d1117; color: #fff; border: 1px solid #30363d; border-radius: 4px;">
                </div>
                
                <div style="margin-bottom: 20px;">
                    <label for="password" style="color: #c9d1d9; font-weight: bold; display: block; margin-bottom: 8px;">Mot de passe de sécurité :</label>
                    <input type="password" id="password" name="password" placeholder="Min 12 car., Maj, Min, Chiffre, Spécial" required pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}" style="width: 100%; box-sizing: border-box; padding: 12px; background: #0d1117; color: #fff; border: 1px solid #30363d; border-radius: 4px;">
                </div>

                <div style="margin-bottom: 30px;">
                    <label for="motif" style="color: #c9d1d9; font-weight: bold; display: block; margin-bottom: 8px;">Motif de la demande :</label>
                    <textarea id="motif" name="motif" rows="3" placeholder="Ex: Besoin d'accès Animateur pour les ateliers du Q3, sur demande de M. Martin." required style="width: 100%; box-sizing: border-box; padding: 12px; background: #0d1117; color: #fff; border: 1px solid #30363d; border-radius: 4px; font-family: sans-serif; resize:vertical;"></textarea>
                </div>
                
                <button type="submit" class="btn btn-mj" style="width: 100%; font-size: 1.1rem; padding: 12px; background: #3b82f6; border-color: #3b82f6;">Soumettre ma demande</button>
            </form>
            
            <div style="text-align: center; margin-top: 20px;">
                <a href="admin_login.php" style="color: #8b949e; text-decoration: none; font-size: 0.9rem;">← J'ai déjà un compte, me connecter</a>
            </div>
            
        <?php endif; ?>
        
    </div>
</body>
</html>
