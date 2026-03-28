<?php
// src/mj_setup.php
require 'db.php';

// Sécurité : On s'assure que la personne a déjà passé le login admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    header("Location: index.php");
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_session = trim($_POST['nom_session']);
    $max_reacteurs = (int)$_POST['max_reacteurs'];
    
    if (empty($nom_session)) {
        $erreur = "Le nom de l'atelier est obligatoire.";
    } else {
        // Génération d'un code PIN à 5 chiffres unique
        do {
            $code = sprintf("%05d", mt_rand(0, 99999));
            $stmt = $pdo->prepare("SELECT id FROM sessions WHERE code_session = ?");
            $stmt->execute([$code]);
        } while ($stmt->fetch());

        // Création de la session
        $stmt = $pdo->prepare("INSERT INTO sessions (nom_session, code_session, max_reacteurs_par_scenario, statut) VALUES (?, ?, ?, 'configuration')");
        $stmt->execute([$nom_session, $code, $max_reacteurs]);
        
        // On mémorise la session active pour l'animateur
        $_SESSION['session_id'] = $pdo->lastInsertId();
        
        // Direction la salle d'attente de l'atelier
        header("Location: mj_dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RiskMapping - Nouvel Atelier</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 600px; margin-top: 50px;">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 3rem; margin-bottom: 10px;">🎯</div>
            <h1 style="color: var(--accent-red); margin: 0;">Créer un nouvel atelier</h1>
            <p class="subtitle">Configuration de la session d'idéation interactive</p>
        </div>
        
        <?php if ($erreur): ?>
            <div style="background-color: rgba(255, 68, 68, 0.2); border: 1px solid var(--accent-red); padding: 10px; border-radius: 4px; color: var(--accent-red); margin-bottom: 15px; text-align: center;">
                ⚠️ <?= $erreur ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="mj_setup.php" style="background: #161b22; padding: 30px; border-radius: 8px; border: 1px solid #30363d;">
            
            <div style="margin-bottom: 20px;">
                <label for="nom_session" style="color: #c9d1d9; font-weight: bold; display: block; margin-bottom: 8px;">Nom de l'atelier :</label>
                <input type="text" id="nom_session" name="nom_session" placeholder="Ex: Atelier des Risques - DSI Q3" required style="width: 100%; box-sizing: border-box; padding: 12px; background: #0d1117; color: #fff; border: 1px solid #30363d; border-radius: 4px;">
            </div>
            
            <div style="margin-bottom: 30px;">
                <label for="max_reacteurs" style="color: #c9d1d9; font-weight: bold; display: block; margin-bottom: 8px;">Nombre de débatteurs par scénario :</label>
                <select id="max_reacteurs" name="max_reacteurs" style="width: 100%; box-sizing: border-box; padding: 12px; background: #0d1117; color: #fff; border: 1px solid #30363d; border-radius: 4px;">
                    <option value="1">1 participant (Monologue)</option>
                    <option value="2">2 participants (Dialogue)</option>
                    <option value="3" selected>3 participants (Débat croisé - Recommandé)</option>
                    <option value="4">4 participants (Table ronde)</option>
                    <option value="5">5 participants (Foule)</option>
                </select>
                <div style="font-size: 0.8rem; color: #8b949e; margin-top: 8px;">Ces personnes seront tirées au sort pour argumenter le risque avant le vote collectif.</div>
            </div>
            
            <button type="submit" class="btn btn-mj" style="width: 100%; font-size: 1.1rem; padding: 15px; background: var(--accent-red); border-color: var(--accent-red);">Générer le code PIN et ouvrir la salle</button>
        </form>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="registre_risques.php" style="color: #8b949e; text-decoration: none; font-size: 0.9rem;">← Annuler et retourner au Registre</a>
        </div>
    </div>
</body>
</html>
