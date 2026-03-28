<?php
// src/ajax_load_historique.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') { die("Accès refusé."); }

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';

if (isset($_GET['delete_session_id'])) {
    if ($admin_role === 'admin') {
        $del_id = (int)$_GET['delete_session_id'];
        
        $stmt_info = $pdo->prepare("SELECT nom_session FROM sessions WHERE id = ?");
        $stmt_info->execute([$del_id]);
        $nom_del = $stmt_info->fetchColumn() ?: "ID $del_id";
        
        $pdo->prepare("DELETE c FROM contributions c JOIN scenarios_bruts s ON c.scenario_id = s.id WHERE s.session_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE svm FROM scenario_valeurs_metier svm JOIN scenarios_bruts s ON svm.scenario_id = s.id WHERE s.session_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE sm FROM scenario_menaces sm JOIN scenarios_bruts s ON sm.scenario_id = s.id WHERE s.session_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE v FROM votes_poker v JOIN scenarios_bruts s ON v.scenario_id = s.id WHERE s.session_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM scenarios_bruts WHERE session_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM participants WHERE session_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM sessions WHERE id = ?")->execute([$del_id]);
        
        log_audit($pdo, $_SESSION['admin_id'], 'SESSION_DELETED', "Suppression en cascade de la session : $nom_del");
    } else {
        log_audit($pdo, $_SESSION['admin_id'], 'UNAUTHORIZED_ACTION', "Tentative de suppression de session bloquée.");
        die("Accès refusé. Droits d'administration requis.");
    }
}

$sessions = $pdo->query("SELECT sess.*, (SELECT COUNT(*) FROM scenarios_bruts WHERE session_id = sess.id) as nb_risques, (SELECT COUNT(*) FROM participants WHERE session_id = sess.id) as nb_joueurs FROM sessions sess ORDER BY created_at DESC")->fetchAll();
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">📂 Historique des Ateliers</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Retrouvez ici la liste de toutes les sessions passées et leur statut.</p>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 10px; border-bottom: 2px solid #30363d;">Date</th>
                <th style="text-align: left; padding: 10px; border-bottom: 2px solid #30363d;">Nom de la session</th>
                <th style="text-align: left; padding: 10px; border-bottom: 2px solid #30363d;">Code</th>
                <th style="text-align: center; padding: 10px; border-bottom: 2px solid #30363d;">Risques Evalués</th>
                <th style="text-align: center; padding: 10px; border-bottom: 2px solid #30363d;">Statut</th>
                <?php if ($admin_role === 'admin'): ?>
                    <th style="text-align: right; padding: 10px; border-bottom: 2px solid #30363d;">Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sessions)): ?>
                <tr><td colspan="6" style="padding: 20px; text-align: center; color: #8b949e;">Aucun atelier dans l'historique.</td></tr>
            <?php else: ?>
                <?php foreach ($sessions as $sess): ?>
                <tr>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; color: #c9d1d9;"><?= date('d/m/Y', strtotime($sess['created_at'])) ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d;">
                        <strong style="color: #fff;"><?= htmlspecialchars($sess['nom_session']) ?></strong><br>
                        <span style="font-size: 0.8rem; color: #8b949e;"><?= $sess['nb_joueurs'] ?> participants</span>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; color: var(--accent-green); font-family: monospace; font-size: 1.1rem;"><?= htmlspecialchars($sess['code_session']) ?></td>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; text-align: center;">
                        <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 3px 8px; border-radius: 4px; font-weight: bold;"><?= $sess['nb_risques'] ?></span>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; text-align: center;">
                        <?php if ($sess['statut'] === 'termine'): ?>
                            <span style="background: rgba(0, 255, 204, 0.1); color: #00ffcc; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Terminé</span>
                        <?php else: ?>
                            <span style="background: rgba(255, 165, 0, 0.1); color: orange; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">En cours</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($admin_role === 'admin'): ?>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; text-align: right;">
                        <button onclick="supprimerSession(<?= $sess['id'] ?>)" class="btn" style="background: rgba(218, 41, 28, 0.1); color: #da291c; border: 1px solid #da291c; padding: 5px 10px; font-size: 0.8rem;">🗑️ Suppr.</button>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
