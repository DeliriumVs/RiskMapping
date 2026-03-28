<?php
// src/join.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code_session']);
    $pseudo = trim($_POST['pseudo']);
    $role = $_POST['role'];
    
    $stmt = $pdo->prepare("SELECT id FROM sessions WHERE code_session = ? AND statut IN ('configuration', 'saisie')");
    $stmt->execute([$code]);
    $session = $stmt->fetch();
    
    if ($session) {
        $stmtIn = $pdo->prepare("INSERT INTO participants (session_id, pseudo, role) VALUES (?, ?, ?)");
        $stmtIn->execute([$session['id'], $pseudo, $role]);
        
        $_SESSION['participant_id'] = $pdo->lastInsertId();
        $_SESSION['session_id'] = $session['id'];
        $_SESSION['role'] = 'participant';
        
        header("Location: index.php");
        exit;
    } else {
        $erreur = "Code PIN invalide, ou l'atelier a déjà commencé.";
    }
}

$equipes = $pdo->query("SELECT nom FROM equipes ORDER BY nom")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rejoindre - RiskMapping</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container" style="max-width: 500px;">
        <h1 style="color: var(--accent-green);">🎟️ Rejoindre l'atelier</h1>
        <p class="subtitle">Entrez le code fourni par l'animateur.</p>
        
        <?php if ($erreur): ?>
            <div style="background-color: rgba(255, 68, 68, 0.2); border: 1px solid var(--accent-red); padding: 10px; border-radius: 4px; color: var(--accent-red); margin-bottom: 15px;">
                ⚠️ <?= $erreur ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="join.php">
            <div style="margin-bottom: 15px;">
                <label for="code_session">🔑 Code PIN (5 chiffres) :</label>
                <input type="text" id="code_session" name="code_session" maxlength="5" inputmode="numeric" pattern="[0-9]{5}" placeholder="00000" required style="width: 100%; box-sizing: border-box; font-size: 1.5rem; text-align: center; letter-spacing: 5px;">
            </div>
            
            <div style="margin-bottom: 15px;">
                <label for="pseudo">👤 Votre prénom / pseudo :</label>
                <input type="text" id="pseudo" name="pseudo" placeholder="Ex: Thomas" required style="width: 100%; box-sizing: border-box;">
            </div>
            
            <div style="margin-bottom: 25px;">
                <label for="role">🏢 Votre Service / Direction :</label>
                <select id="role" name="role" required style="width: 100%; padding: 10px; background: #0d1117; color: #fff; border: 1px solid #30363d; border-radius: 4px;">
                    <?php if (empty($equipes)): ?>
                        <option value="Non renseigné">Aucun service paramétré</option>
                    <?php else: ?>
                        <?php foreach ($equipes as $eq_nom): ?>
                            <option value="<?= htmlspecialchars($eq_nom) ?>"><?= htmlspecialchars($eq_nom) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-part" style="width: 100%; font-size: 1.1rem; padding: 12px;">Entrer dans la salle d'attente</button>
            <div style="text-align: center; margin-top: 15px;">
                <a href="index.php" style="color: #8b949e; text-decoration: none; font-size: 0.9rem;">Annuler</a>
            </div>
        </form>
    </div>
</body>
</html>
