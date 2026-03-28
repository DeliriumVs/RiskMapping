<?php
// src/admin_menaces.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $admin_role === 'lecteur') { die("<div style='color:red; padding:20px;'>Accès refusé.</div>"); }

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $type = trim($_POST['type_source']);
        $motiv = trim($_POST['motivation']);
        $capa = $_POST['niveau_capacite'];
        if (!empty($type)) {
            $stmt = $pdo->prepare("INSERT INTO menaces (type_source, motivation, niveau_capacite) VALUES (?, ?, ?)");
            $stmt->execute([$type, $motiv, $capa]);
            $message = "✅ Source de menace ajoutée avec succès.";
            log_audit($pdo, $_SESSION['admin_id'], 'THREAT_ADDED', "Ajout menace : $type");
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if ($admin_role === 'admin') {
            $id_del = (int)$_POST['id'];
            
            $stmt_info = $pdo->prepare("SELECT type_source FROM menaces WHERE id = ?");
            $stmt_info->execute([$id_del]);
            $nom_del = $stmt_info->fetchColumn() ?: "ID $id_del";
            
            $pdo->prepare("DELETE FROM menaces WHERE id = ?")->execute([$id_del]);
            $message = "🗑️ Menace supprimée.";
            log_audit($pdo, $_SESSION['admin_id'], 'THREAT_DELETED', "Suppression menace : $nom_del");
        } else {
            $message = "⚠️ Action bloquée. Seul un administrateur peut supprimer.";
            log_audit($pdo, $_SESSION['admin_id'], 'UNAUTHORIZED_ACTION', "Tentative de suppression de menace.");
        }
    }
}

$menaces = $pdo->query("SELECT * FROM menaces ORDER BY type_source ASC")->fetchAll();
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">🦹 Gestion des Sources de Menaces</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Identifiez les attaquants potentiels.</p>

    <?php if ($message): ?>
        <div style="padding: 10px; background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 4px; margin-bottom: 20px; color: #3b82f6;"><?= $message ?></div>
    <?php endif; ?>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px;">Type de Source</th>
                <th style="padding: 10px;">Motivation</th>
                <th style="padding: 10px;">Capacité</th>
                <th style="padding: 10px; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($menaces as $m): ?>
            <tr style="border-bottom: 1px solid #30363d;">
                <td style="padding: 10px; color: #fff; font-weight: bold;"><?= htmlspecialchars($m['type_source']) ?></td>
                <td style="padding: 10px; color: #c9d1d9;"><?= htmlspecialchars($m['motivation']) ?></td>
                <td style="padding: 10px;"><span style="background: rgba(255,165,0,0.1); color: orange; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;"><?= htmlspecialchars($m['niveau_capacite']) ?></span></td>
                <td style="padding: 10px; text-align: right;">
                    <?php if ($admin_role === 'admin'): ?>
                        <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_menaces.php');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
                            <button type="submit" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" onclick="return confirm('Supprimer cette source de menace ?');">🗑️</button>
                        </form>
                    <?php else: ?>
                        <span style="color: #8b949e; font-size: 0.8rem;" title="Droits administrateur requis">🔒</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 style="color: #3b82f6; margin-bottom: 15px;">➕ Ajouter une Source de Menace</h4>
    <form method="POST" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_menaces.php');" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
        <input type="hidden" name="action" value="add">
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Source</label>
            <input type="text" name="type_source" placeholder="Ex: Cybercriminel" required style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Motivation</label>
            <input type="text" name="motivation" placeholder="Ex: Gain financier" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Niveau Capacité</label>
            <select name="niveau_capacite" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
                <option>Individuelle (Novice)</option><option>Standard (Criminel)</option><option>Élevée (Groupe structuré)</option><option>Très Élevée (Étatique)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: #3b82f6; border: none; color: white;">Ajouter</button>
    </form>
</div>
