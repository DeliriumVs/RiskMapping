<?php
// src/ajax_load_registre.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') { die("Accès refusé."); }

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';

if (isset($_GET['delete_id'])) {
    if ($admin_role === 'admin') {
        $del_id = (int)$_GET['delete_id'];
        
        $stmt_info = $pdo->prepare("SELECT titre FROM scenarios_bruts WHERE id = ?");
        $stmt_info->execute([$del_id]);
        $titre_del = $stmt_info->fetchColumn() ?: "ID $del_id";
        
        $pdo->prepare("DELETE FROM contributions WHERE scenario_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM scenario_valeurs_metier WHERE scenario_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM scenario_menaces WHERE scenario_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM votes_poker WHERE scenario_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM scenarios_bruts WHERE id = ?")->execute([$del_id]);
        
        log_audit($pdo, $_SESSION['admin_id'], 'RISK_DELETED', "Suppression du scénario : $titre_del");
    } else {
        log_audit($pdo, $_SESSION['admin_id'], 'UNAUTHORIZED_ACTION', "Tentative de suppression de risque bloquée.");
        die("Accès refusé. Droits d'administration requis.");
    }
}

$stmt = $pdo->prepare("SELECT s.*, p.pseudo, sess.nom_session FROM scenarios_bruts s JOIN participants p ON s.auteur_id = p.id JOIN sessions sess ON s.session_id = sess.id WHERE s.statut = 'traite' ORDER BY s.niveau_ebios DESC, s.priorite DESC, s.created_at DESC");
$stmt->execute();
$scenarios = $stmt->fetchAll();

$matrix = [];
for ($i=1; $i<=4; $i++) { for ($v=1; $v<=4; $v++) { $matrix[$i][$v] = []; } }
$counter = 1;
foreach ($scenarios as &$s) {
    $s['visual_id'] = 'R' . $counter++;
    $imp = min(max((int)$s['impact_estime'], 1), 4);
    $vrai = min(max((int)$s['vraisemblance_estimee'], 1), 4);
    $matrix[$imp][$vrai][] = $s;
}
unset($s); 
?>

<div class="print-only" style="border-bottom: 3px solid #3b82f6; padding-bottom: 10px; margin-bottom: 20px;">
    <h1 style="color: #3b82f6 !important; font-size: 24pt; margin: 0;">Rapport d'Analyse des Risques (EBIOS RM)</h1>
    <p style="font-size: 12pt; margin: 5px 0 0 0;"><strong>Généré par :</strong> RiskMapping Suite | <strong>Date :</strong> <?= date('d/m/Y') ?></p>
    <p style="font-size: 10pt; color: #ff4d4d !important; font-weight: bold; text-transform: uppercase;">Mention : Diffusion Restreinte / Confidentiel</p>
</div>

<?php if (!empty($scenarios)): ?>
<div class="heatmap-wrapper">
    <h2 style="margin-top: 0; color: #c9d1d9;">Cartographie des Risques (Heatmap)</h2>
    <div style="display: flex; align-items: center;">
        <div style="transform: rotate(-90deg); color: #c9d1d9; font-weight: bold; font-size: 1.1rem; margin-right: -40px; margin-left: -50px;" class="heatmap-axis-label">Gravité (Conséquences)</div>
        <div>
            <div class="heatmap-core">
                <div class="heatmap-y-axis">
                    <div>4<br><span style="font-weight: normal; font-size: 0.75rem;">Critique</span></div>
                    <div>3<br><span style="font-weight: normal; font-size: 0.75rem;">Grave</span></div>
                    <div>2<br><span style="font-weight: normal; font-size: 0.75rem;">Significative</span></div>
                    <div>1<br><span style="font-weight: normal; font-size: 0.75rem;">Mineure</span></div>
                </div>
                <div class="heatmap-grid">
                    <?php 
                    for ($i=4; $i>=1; $i--): 
                        for ($v=1; $v<=4; $v++): 
                            $max = max($i, $v);
                            if ($max == 4) $bgClass = 'bg-critical'; elseif ($max == 3) $bgClass = 'bg-high'; elseif ($max == 2) $bgClass = 'bg-medium'; else $bgClass = 'bg-low';
                    ?>
                        <div class="heatmap-cell <?= $bgClass ?>">
                            <?php foreach ($matrix[$i][$v] as $scen): ?>
                                <span class="risk-dot" title="<?= htmlspecialchars($scen['titre']) ?>"><?= $scen['visual_id'] ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endfor; endfor; ?>
                </div>
            </div>
            <div class="heatmap-x-axis">
                <div>1<br><span style="font-weight: normal; font-size: 0.75rem;">Très faible</span></div><div>2<br><span style="font-weight: normal; font-size: 0.75rem;">Faible</span></div><div>3<br><span style="font-weight: normal; font-size: 0.75rem;">Élevée</span></div><div>4<br><span style="font-weight: normal; font-size: 0.75rem;">Très élevée</span></div>
            </div>
        </div>
    </div>
    <div style="color: #c9d1d9; font-weight: bold; font-size: 1.1rem; margin-top: 15px; margin-left: 80px;" class="heatmap-axis-label">Vraisemblance (Probabilité)</div>
</div>
<?php endif; ?>

<div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 30px; margin-bottom: 10px;">
    <h3 style="color: var(--accent-green); margin: 0;">Plan d'Action / Risques Traités</h3>
    <button onclick="resetOrder()" class="btn no-print" style="background: transparent; border: 1px solid #8b949e; color: #8b949e; padding: 5px 10px; font-size: 0.8rem;">🔄 Réinitialiser le tri</button>
</div>

<div style="overflow-x: auto;">
    <table>
        <thead>
            <tr>
                <th class="no-print">↕</th>
                <th style="width: 50px; text-align: center;">ID</th>
                <th>Atelier d'origine</th>
                <th style="width: 25%;">Scénario de Menace</th>
                <th>Gravité</th>
                <th>Vraisemblance</th>
                <th>Niveau de Risque</th>
                <th>Criticité</th>
                <th style="width: 20%;">Traitement & Justification</th>
                <?php if ($admin_role !== 'lecteur'): ?>
                    <th class="no-print">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody id="sortable-table">
            <?php if (empty($scenarios)): ?>
                <tr><td colspan="10" style="text-align: center;">Aucun risque enregistré.</td></tr>
            <?php else: ?>
                <?php 
                    foreach ($scenarios as $s): 
                    $niveau = (int)$s['niveau_ebios'];
                    if ($niveau >= 4) { $c_risk = 'risk-critical'; } elseif ($niveau >= 3) { $c_risk = 'risk-high'; } elseif ($niveau >= 2) { $c_risk = 'risk-medium'; } else { $c_risk = 'risk-low'; }
                    $priorite = (int)$s['priorite'];
                    $c_mult = $priorite >= 12 ? 'risk-high' : ($priorite >= 6 ? 'risk-medium' : 'risk-low');
                    $trait = htmlspecialchars($s['strategie_traitement']);
                    $c_trait = "trait-" . explode(' ', $trait)[0]; 
                ?>
                    <tr data-id="<?= $s['id'] ?>">
                        <td class="drag-handle no-print" style="vertical-align: middle;">⣿</td>
                        <td style="text-align: center; vertical-align: middle;"><span class="risk-dot" style="margin: 0;"><?= $s['visual_id'] ?></span></td>
                        <td><strong style="color: #000;"><?= htmlspecialchars($s['nom_session']) ?></strong><br><span style="font-size: 0.75rem; color: #666;"><?= date('d/m/y', strtotime($s['created_at'])) ?></span></td>
                        <td><strong><?= htmlspecialchars($s['titre']) ?></strong></td>
                        <td style="text-align: center;"><strong><?= $s['impact_estime'] ?></strong></td>
                        <td style="text-align: center;"><strong><?= $s['vraisemblance_estimee'] ?></strong></td>
                        <td style="text-align: center;"><span class="badge-risk <?= $c_risk ?>"><?= $niveau ?></span></td>
                        <td style="text-align: center;"><span class="badge-risk <?= $c_mult ?>"><?= $priorite ?></span></td>
                        <td style="text-align: left;">
                            <div style="text-align: center; margin-bottom: 5px;"><span class="badge-traitement <?= $c_trait ?>"><?= $trait ?></span></div>
                        </td>
                        
                        <?php if ($admin_role !== 'lecteur'): ?>
                        <td class="no-print" style="text-align: center; vertical-align: middle;">
                            <a href="edit_scenario.php?id=<?= $s['id'] ?>&from=master" class="btn" style="padding: 4px; font-size: 0.75rem; background: #30363d; display: block; margin-bottom: 5px;">✎ Éditer</a>
                            <?php if ($admin_role === 'admin'): ?>
                                <a href="#" onclick="supprimerRisque(<?= $s['id'] ?>); return false;" class="btn" style="padding: 4px; font-size: 0.75rem; background: rgba(255,0,0,0.2); color: #ff4444; display: block;">🗑️ Suppr.</a>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                        
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
