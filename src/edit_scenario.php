<?php
// src/edit_scenario.php
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("Accès refusé.");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: mj_dashboard.php");
    exit;
}

$id_scenario = (int)$_GET['id'];
$from = $_GET['from'] ?? 'session'; 
$redirect_url = ($from === 'master') ? 'registre_risques.php' : 'consolidation.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre = $_POST['titre'];
    $description = $_POST['description'];
    $impact = (int)$_POST['impact'];
    $vraisemblance = (int)$_POST['vraisemblance'];
    $traitement = $_POST['traitement'];
    $justification = $_POST['justification'];
    
    $priorite_mult = $impact * $vraisemblance; 
    $niveau_ebios_max = max($impact, $vraisemblance); 
    
    // On met à jour toutes les données ET on horodate le traitement
    $statut_qualification = in_array($_POST['statut_qualification'] ?? '', ['a_qualifier', 'qualifie'])
        ? $_POST['statut_qualification']
        : 'a_qualifier';
    $scenario_technique = trim($_POST['scenario_technique'] ?? '');

    $stmt = $pdo->prepare("UPDATE scenarios_bruts SET titre = ?, description = ?, impact_estime = ?, vraisemblance_estimee = ?, priorite = ?, niveau_ebios = ?, strategie_traitement = ?, justification_traitement = ?, statut_qualification = ?, scenario_technique = ?, traitement_updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$titre, $description, $impact, $vraisemblance, $priorite_mult, $niveau_ebios_max, $traitement, $justification, $statut_qualification, $scenario_technique, $id_scenario]);
    
    header("Location: " . $redirect_url);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM scenarios_bruts WHERE id = ?");
$stmt->execute([$id_scenario]);
$scenario = $stmt->fetch();

if (!$scenario) { die("Scénario introuvable."); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Éditer - Lundi Noir</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .edit-container { background: #161b22; padding: 20px; border-radius: 8px; border: 1px solid #30363d; margin-top: 20px; text-align: left; }
        textarea { width: 100%; height: 80px; background: #0d1117; color: #fff; border: 1px solid #30363d; padding: 10px; border-radius: 4px; resize: vertical; box-sizing: border-box; font-family: sans-serif;}
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        select { width: 100%; padding: 10px; background: #0d1117; color: #fff; border: 1px solid #30363d; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 800px;">
        <h1 style="color: var(--accent-green);">✎ Édition et Traitement</h1>
        
        <div class="edit-container">
            <form method="POST" action="edit_scenario.php?id=<?= $id_scenario ?>&from=<?= $from ?>">
                <div style="margin-bottom: 15px;">
                    <label>Titre de la Menace :</label>
                    <input type="text" name="titre" value="<?= htmlspecialchars($scenario['titre']) ?>" required style="width: 100%; box-sizing: border-box;">
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label>Description des symptômes :</label>
                    <textarea name="description" required><?= htmlspecialchars($scenario['description']) ?></textarea>
                </div>
                
                <div class="grid-2">
                    <div>
                        <label>💥 Conséquences :</label>
                        <select name="impact" required>
                            <option value="1" <?= $scenario['impact_estime'] == 1 ? 'selected' : '' ?>>1 - Mineure</option>
                            <option value="2" <?= $scenario['impact_estime'] == 2 ? 'selected' : '' ?>>2 - Significative</option>
                            <option value="3" <?= $scenario['impact_estime'] == 3 ? 'selected' : '' ?>>3 - Grave</option>
                            <option value="4" <?= $scenario['impact_estime'] == 4 ? 'selected' : '' ?>>4 - Critique</option>
                        </select>
                    </div>
                    <div>
                        <label>🎲 Vraisemblance :</label>
                        <select name="vraisemblance" required>
                            <option value="1" <?= $scenario['vraisemblance_estimee'] == 1 ? 'selected' : '' ?>>1 - Très faible</option>
                            <option value="2" <?= $scenario['vraisemblance_estimee'] == 2 ? 'selected' : '' ?>>2 - Faible</option>
                            <option value="3" <?= $scenario['vraisemblance_estimee'] == 3 ? 'selected' : '' ?>>3 - Élevée</option>
                            <option value="4" <?= $scenario['vraisemblance_estimee'] == 4 ? 'selected' : '' ?>>4 - Très élevée</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed #30363d;">
                    <label style="color: #3b82f6;">🛡️ Stratégie de Traitement (EBIOS RM) :</label>
                    <select name="traitement" style="border-color: #3b82f6; margin-top: 5px; margin-bottom: 15px;">
                        <option value="À définir" <?= $scenario['strategie_traitement'] === 'À définir' ? 'selected' : '' ?>>⏳ À définir</option>
                        <option value="Réduire" <?= $scenario['strategie_traitement'] === 'Réduire' ? 'selected' : '' ?>>📉 Réduire (Appliquer des mesures de sécurité)</option>
                        <option value="Transférer" <?= $scenario['strategie_traitement'] === 'Transférer' ? 'selected' : '' ?>>🤝 Transférer (Assurance, Sous-traitance contractuelle)</option>
                        <option value="Éviter" <?= $scenario['strategie_traitement'] === 'Éviter' ? 'selected' : '' ?>>🚫 Éviter (Arrêt de l'activité/service)</option>
                        <option value="Accepter" <?= $scenario['strategie_traitement'] === 'Accepter' ? 'selected' : '' ?>>✅ Accepter (Risque résiduel validé)</option>
                    </select>

                    <label style="color: #8b949e; font-size: 0.9rem;">Justification / Mesures à prendre (Plan d'Action) :</label>
                    <textarea name="justification" placeholder="Ex: Transféré à AWS (Contrat SLA 99.9%). Mesure complémentaire : Mettre en place un backup froid mensuel." style="margin-top: 5px; border-color: #3b82f6;"><?= htmlspecialchars($scenario['justification_traitement'] ?? '') ?></textarea>
                </div>
                
                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px dashed #30363d;">
                    <label style="color: #a78bfa; font-weight: bold;">🔬 Qualification Technique (post-atelier)</label>
                    <p style="color: #8b949e; font-size: 0.8rem; margin: 5px 0 12px;">À renseigner à froid par l'équipe sécurité, après l'atelier participatif.</p>

                    <select name="statut_qualification" style="border-color: #a78bfa; margin-bottom: 15px; width: 100%; padding: 10px; background: #0d1117; color: #fff; border-radius: 4px;">
                        <option value="a_qualifier" <?= ($scenario['statut_qualification'] ?? 'a_qualifier') === 'a_qualifier' ? 'selected' : '' ?>>⚠️ À qualifier — En attente de relecture technique</option>
                        <option value="qualifie"    <?= ($scenario['statut_qualification'] ?? '') === 'qualifie'    ? 'selected' : '' ?>>✅ Qualifié — Reformulation technique validée</option>
                    </select>

                    <label style="color: #8b949e; font-size: 0.9rem;">Reformulation technique du scénario :</label>
                    <textarea name="scenario_technique" placeholder="Ex : Exploitation d'une vulnérabilité RCE sur le VPN Ivanti (CVE-XXXX) permettant l'exécution de code arbitraire, suivie d'un mouvement latéral vers le contrôleur de domaine…" style="margin-top: 5px; border-color: #a78bfa; height: 100px;"><?= htmlspecialchars($scenario['scenario_technique'] ?? '') ?></textarea>
                </div>

                <div style="display: flex; gap: 15px; margin-top: 25px;">
                    <button type="submit" class="btn btn-mj" style="flex: 1;">💾 Sauvegarder</button>
                    <a href="<?= $redirect_url ?>" class="btn" style="background: #30363d; color: #fff; flex: 1; text-align: center;">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
