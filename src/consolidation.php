<?php
// src/consolidation.php
require 'db.php';

// Sécurité MJ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("Accès refusé.");
}

$session_id = $_SESSION['session_id'];

// On s'assure que la session est bien marquée comme terminée
$pdo->prepare("UPDATE sessions SET statut = 'termine' WHERE id = ? AND statut != 'termine'")->execute([$session_id]);

// On récupère tous les scénarios traités
$stmt = $pdo->prepare("
    SELECT s.id, s.titre, s.description, s.impact_estime, s.vraisemblance_estimee, s.niveau_ebios, s.priorite, p.pseudo
    FROM scenarios_bruts s
    JOIN participants p ON s.auteur_id = p.id
    WHERE s.session_id = ? AND s.statut = 'traite'
    ORDER BY s.priorite DESC
");
$stmt->execute([$session_id]);
$scenarios = $stmt->fetchAll();

// Récupération des notes
foreach ($scenarios as &$scen) {
    $stmtNotes = $pdo->prepare("
        SELECT c.notes_mj, p.pseudo, p.role 
        FROM contributions c 
        JOIN participants p ON c.participant_id = p.id 
        WHERE c.scenario_id = ? AND c.notes_mj IS NOT NULL AND c.notes_mj != ''
    ");
    $stmtNotes->execute([$scen['id']]);
    $scen['contributions'] = $stmtNotes->fetchAll();
}
unset($scen);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Consolidation - Lundi Noir</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    <style>
        /* On permet au conteneur de prendre 95% de l'écran pour éviter le scroll */
        .container { max-width: 95% !important; }
        
        .table-container { width: 100%; overflow-x: auto; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; background: #161b22; color: #fff; text-align: left; font-size: 0.9rem; } /* Police globale un peu plus fine */
        
        /* Réduction du padding (10px au lieu de 15px) pour gagner de la place */
        th, td { padding: 10px; border: 1px solid #30363d; }
        th { background: #21262d; color: var(--accent-green); position: sticky; top: 0; }
        tr:hover { background: #2c3238; }
        .drag-handle { cursor: grab; color: #8b949e; font-size: 1.2rem; text-align: center; width: 30px; }
        .drag-handle:active { cursor: grabbing; }
        
        .badge-risk { padding: 4px 8px; border-radius: 4px; font-weight: bold; text-align: center; display: inline-block; min-width: 25px; }
        /* Les 4 niveaux de risques officiels EBIOS RM */
        .risk-critical { background: rgba(255, 0, 85, 0.2); color: #ff0055; border: 1px solid #ff0055; text-transform: uppercase; }
        .risk-high { background: rgba(255, 68, 68, 0.2); color: #ff4444; border: 1px solid #ff4444; }
        .risk-medium { background: rgba(255, 165, 0, 0.2); color: orange; border: 1px solid orange; }
        .risk-low { background: rgba(0, 255, 204, 0.2); color: #00ffcc; border: 1px solid #00ffcc; }
        
        /* --- STYLE POUR L'EXPORT PDF --- */
        @media print {
            .no-print { display: none !important; }
            body, .container { background: #fff !important; color: #000 !important; box-shadow: none !important; border: none !important; max-width: 100% !important; padding: 0 !important; }
            h1, .subtitle { color: #000 !important; }
            table { border: 2px solid #000 !important; }
            th { background: #f0f0f0 !important; color: #000 !important; border: 1px solid #000 !important; font-weight: bold !important; }
            td { border: 1px solid #000 !important; color: #000 !important; }
            .badge-risk { background: transparent !important; color: #000 !important; border: 1px solid #000 !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 style="color: var(--accent-green);">📊 Consolidation EBIOS RM</h1>
        <p class="subtitle">Hiérarchisation des Événements Redoutés</p>

        <div class="no-print" style="margin-bottom: 20px; display: flex; gap: 15px; justify-content: flex-end;">
            <a href="registre_risques.php" class="btn" style="background: #3b82f6; color: white;">🌐 Registre Global</a>
            
            <button onclick="window.print()" class="btn" style="background: #da291c; color: #fff; border: 1px solid #da291c;">📄 Export PDF</button>
            <a href="export_csv.php" class="btn" style="background: #107c41; color: #fff; border: 1px solid #107c41;">🧮 Export CSV</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th class="no-print">↕</th>
                        <th style="width: 20%;">Scénario de Menace</th>
                        <th style="width: 25%;">Impacts / Craintes métiers (Notes MJ)</th>
                        <th>Gravité<br>(Conséquence)</th>
                        <th>Vraisemblance<br>(Probabilité)</th>
                        <th>Niveau EBIOS<br>(MAX)</th>
                        <th>Criticité<br>(C x V)</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>
                <tbody id="sortable-table">
                    <?php if (empty($scenarios)): ?>
                        <tr><td colspan="8" style="text-align: center;">Aucun scénario traité.</td></tr>
                    <?php else: ?>
                        <?php 
                            // Dictionnaires EBIOS RM
                            $lbl_c = [1 => 'Mineure', 2 => 'Significative', 3 => 'Grave', 4 => 'Critique'];
                            $lbl_v = [1 => 'Très faible', 2 => 'Faible', 3 => 'Élevée', 4 => 'Très élevée'];
                            
                            foreach ($scenarios as $s): 
                                // Calcul du badge EBIOS officiel (MAX)
                                $niveau_ebios = (int)$s['niveau_ebios'];
                                if ($niveau_ebios >= 4) {
                                    $classe_risque_ebios = 'risk-critical';
                                    $label_risque_ebios = 'Critique';
                                } elseif ($niveau_ebios >= 3) {
                                    $classe_risque_ebios = 'risk-high';
                                    $label_risque_ebios = 'Élevé';
                                } elseif ($niveau_ebios >= 2) {
                                    $classe_risque_ebios = 'risk-medium';
                                    $label_risque_ebios = 'Modéré';
                                } else {
                                    $classe_risque_ebios = 'risk-low';
                                    $label_risque_ebios = 'Faible';
                                }

                                // Calcul du badge criticité mathématique
                                $priorite = (int)$s['priorite'];
                                $classe_risque_mult = 'risk-low';
                                if ($priorite >= 12) $classe_risque_mult = 'risk-high';
                                elseif ($priorite >= 6) $classe_risque_mult = 'risk-medium';
                        ?>
                            <tr>
                                <td class="drag-handle no-print">⣿</td>
                                <td>
                                    <strong style="font-size: 1rem;"><?= htmlspecialchars($s['titre']) ?></strong><br>
                                    <span style="font-size: 0.8rem; color: #8b949e;"><?= htmlspecialchars($s['description']) ?></span>
                                </td>
                                <td>
                                    <ul style="margin: 0; padding-left: 20px; font-size: 0.85rem;">
                                        <?php foreach ($s['contributions'] as $c): ?>
                                            <li><em><?= htmlspecialchars($c['pseudo']) ?> :</em> <?= htmlspecialchars($c['notes_mj']) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </td>
                                <td style="text-align: center; font-size: 0.85rem;">
                                    <strong><?= $s['impact_estime'] ?></strong> - <?= $lbl_c[$s['impact_estime']] ?? 'N/A' ?>
                                </td>
                                <td style="text-align: center; font-size: 0.85rem;">
                                    <strong><?= $s['vraisemblance_estimee'] ?></strong> - <?= $lbl_v[$s['vraisemblance_estimee']] ?? 'N/A' ?>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-risk <?= $classe_risque_ebios ?>" style="font-size: 1rem; min-width: 80px;">
                                        <?= $niveau_ebios ?> - <?= $label_risque_ebios ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span class="badge-risk <?= $classe_risque_mult ?>"><?= $priorite ?></span>
                                </td>
                                <td class="no-print" style="text-align: center;">
                                    <a href="edit_scenario.php?id=<?= $s['id'] ?>" class="btn" style="padding: 4px 8px; font-size: 0.75rem; background: #30363d; color: white; text-decoration: none; display: inline-block;">✎ Éditer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('sortable-table');
            var sortable = Sortable.create(el, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'risk-medium'
            });
        });
    </script>
</body>
</html>
