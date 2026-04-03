<?php
// src/saisie_cauchemar.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'participant' || !isset($_SESSION['participant_id'])) {
    header("Location: index.php");
    exit;
}

$session_id = $_SESSION['session_id'];
$participant_id = $_SESSION['participant_id'];

$stmtP = $pdo->prepare("SELECT pseudo FROM participants WHERE id = ?");
$stmtP->execute([$participant_id]);
$participant = $stmtP->fetch();
$pseudo = $participant ? $participant['pseudo'] : 'Anonyme';

$stmtSess = $pdo->prepare("SELECT statut FROM sessions WHERE id = ?");
$stmtSess->execute([$session_id]);
$sess_statut = $stmtSess->fetchColumn();

if ($sess_statut !== 'saisie' && $sess_statut !== 'configuration') {
    header("Location: participant_view.php");
    exit;
}

$a_soumis = false;
$stmtCheck = $pdo->prepare("SELECT id FROM scenarios_bruts WHERE auteur_id = ?");
$stmtCheck->execute([$participant_id]);
if ($stmtCheck->fetch()) {
    $a_soumis = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$a_soumis) {
    
    $titre = trim($_POST['titre']);
    $description = trim($_POST['description']);
    
    // 1. On insère le scénario de base (qui ne contient plus d'ID liés)
    $stmtIn = $pdo->prepare("INSERT INTO scenarios_bruts (session_id, auteur_id, titre, description) VALUES (?, ?, ?, ?)");
    $stmtIn->execute([$session_id, $participant_id, $titre, $description]);
    
    $nouveau_scenario_id = $pdo->lastInsertId();
    
    // 2. Liaisons multiples : Valeurs Métier
    if (!empty($_POST['valeurs_metier']) && is_array($_POST['valeurs_metier'])) {
        $stmtVm = $pdo->prepare("INSERT INTO scenario_valeurs_metier (scenario_id, valeur_metier_id) VALUES (?, ?)");
        foreach ($_POST['valeurs_metier'] as $vm_id) {
            $stmtVm->execute([$nouveau_scenario_id, (int)$vm_id]);
        }
    }

    // 3. Liaisons multiples : Menaces
    if (!empty($_POST['menaces']) && is_array($_POST['menaces'])) {
        $stmtM = $pdo->prepare("INSERT INTO scenario_menaces (scenario_id, menace_id) VALUES (?, ?)");
        foreach ($_POST['menaces'] as $m_id) {
            $stmtM->execute([$nouveau_scenario_id, (int)$m_id]);
        }
    }
    
    header("Location: saisie_cauchemar.php");
    exit;
}

$valeurs_metier = $pdo->query("SELECT * FROM valeurs_metier ORDER BY nom ASC")->fetchAll();
$menaces = $pdo->query("SELECT * FROM menaces ORDER BY type_source ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Saisie - RiskMapping</title>
    <link rel="stylesheet" href="style.css">
    
    <script>
        // Polling de l'état de session — redirige dès que le MJ ferme les soumissions
        (function() {
            let _pollInterval = setInterval(async () => {
                try {
                    const res  = await fetch('api_session_status.php');
                    const data = await res.json();
                    if (data.status === 'success' && data.session_statut !== 'saisie' && data.session_statut !== 'configuration') {
                        clearInterval(_pollInterval);
                        window.location.href = 'participant_view.php';
                    }
                } catch(e) {}
            }, 3000);
        })();
    </script>
    
    <style>
        .wait-box { text-align: center; padding: 40px; border-radius: 8px; border: 1px solid #3b82f6; background: rgba(59, 130, 246, 0.05); margin-top: 50px; }
        .spinner { border: 4px solid rgba(59, 130, 246, 0.1); border-top: 4px solid #3b82f6; border-radius: 50%; width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 25px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        .form-section { background: #0d1117; padding: 20px; border-radius: 6px; border: 1px solid #30363d; margin-bottom: 20px; }
        .form-label { color: #c9d1d9; font-weight: bold; display: block; margin-bottom: 10px; font-size: 1.1rem; }
        .form-select, .form-input, .form-textarea { width: 100%; box-sizing: border-box; padding: 12px; font-size: 1rem; background: #161b22; color: #fff; border: 1px solid #30363d; border-radius: 4px; font-family: sans-serif; transition: 0.3s; }
        .form-select:focus, .form-input:focus, .form-textarea:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2); }
        
        details { background: #161b22; border: 1px dashed #484f58; padding: 15px; border-radius: 6px; margin-bottom: 20px; transition: 0.3s; }
        details[open] { border-style: solid; border-color: #30363d; }
        summary { color: #8b949e; cursor: pointer; font-weight: bold; list-style: none; display: flex; align-items: center; }
        summary::-webkit-details-marker { display: none; }
        summary::before { content: "►"; font-size: 0.8rem; margin-right: 10px; transition: 0.3s; }
        details[open] summary::before { transform: rotate(90deg); }
        
        .checkbox-box { background: #0d1117; border: 1px solid #30363d; border-radius: 4px; padding: 10px; max-height: 140px; overflow-y: auto; }
        .checkbox-item { display: flex; align-items: center; margin-bottom: 8px; color: #c9d1d9; cursor: pointer; font-size: 0.95rem; padding: 4px; border-radius: 4px; transition: background 0.2s;}
        .checkbox-item:hover { background: #161b22; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 700px;">
        
        <?php if ($a_soumis): ?>
            <div class="wait-box">
                <h2 style="color: #3b82f6; margin-top: 0;">C'est noté, <?= htmlspecialchars($pseudo) ?> !</h2>
                <p style="color: #c9d1d9; font-size: 1.1rem;">Votre scénario a bien été ajouté au tableau de bord.</p>
                <div class="spinner"></div>
                <p style="color: #8b949e; font-style: italic; margin-top: 30px;">L'animateur va bientôt fermer les propositions pour lancer les débats. Préparez vos arguments !</p>
            </div>
            
        <?php else: ?>
            <div style="text-align: center; margin-bottom: 30px;">
                <h1 style="color: #fff; margin-bottom: 5px;">✍️ Phase d'idéation</h1>
                <p class="subtitle">Racontez-nous l'événement qui paralyserait votre activité.</p>
            </div>
            
            <form method="POST" action="saisie_cauchemar.php" style="background: #161b22; padding: 25px; border-radius: 8px; border: 1px solid #30363d;">
                
                <div class="form-section" style="border-left: 4px solid var(--accent-green);">
                    <label class="form-label" for="titre">🎯 1. Quel est votre pire cauchemar pro en une phrase ?</label>
                    <input type="text" id="titre" name="titre" class="form-input" placeholder="Ex: Un stagiaire mécontent efface des dossiers partagés, fuite de données RH dans la presse..." required>
                </div>
                
                <div class="form-section" style="border-left: 4px solid orange;">
                    <label class="form-label" for="description">💥 2. Quelles en seraient les conséquences directes ?</label>
                    <textarea id="description" name="description" class="form-textarea" rows="4" placeholder="Ex: Arrêt de la ligne de production pendant 3 jours, panique chez les clients, amende RGPD, atteinte à l'image..." required style="resize: vertical;"></textarea>
                </div>
                
                <details>
                    <summary>🧠 Mode Avancé : Lier aux référentiels de sécurité (Optionnel)</summary>
                    <div style="margin-top: 15px; display: grid; gap: 20px;">
                        
                        <div>
                            <label style="display:block; font-size: 0.85rem; color:#8b949e; margin-bottom:8px;">Quels actifs sont impactés ? (Valeurs Métier) - <span style="font-style:italic;">Choix multiples possibles</span></label>
                            <div class="checkbox-box">
                                <?php foreach ($valeurs_metier as $vm): ?>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="valeurs_metier[]" value="<?= $vm['id'] ?>" style="margin-right: 10px;">
                                        <?= htmlspecialchars($vm['nom']) ?> 
                                        <span style="color: #8b949e; margin-left: 5px; font-size: 0.8rem;">(<?= htmlspecialchars($vm['critere_impacte']) ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div>
                            <label style="display:block; font-size: 0.85rem; color:#8b949e; margin-bottom:8px;">Qui pourrait être à l'origine de cela ? (Menaces) - <span style="font-style:italic;">Choix multiples possibles</span></label>
                            <div class="checkbox-box">
                                <?php foreach ($menaces as $m): ?>
                                    <label class="checkbox-item">
                                        <input type="checkbox" name="menaces[]" value="<?= $m['id'] ?>" style="margin-right: 10px;">
                                        <?= htmlspecialchars($m['type_source']) ?> 
                                        <span style="color: #8b949e; margin-left: 5px; font-size: 0.8rem;">(<?= htmlspecialchars($m['motivation']) ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                    </div>
                </details>
                
                <button type="submit" class="btn btn-part" style="width: 100%; font-size: 1.2rem; padding: 15px; background: #3b82f6; border-color: #3b82f6; color: #fff; margin-top: 10px;">
                    Proposer ce scénario
                </button>
            </form>
        <?php endif; ?>
        
    </div>
</body>
</html>
