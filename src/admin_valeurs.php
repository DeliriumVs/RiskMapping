<?php
// src/admin_valeurs.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $admin_role === 'lecteur') { 
    die("<div style='color:red; padding:20px;'>Accès refusé.</div>"); 
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $nom = trim($_POST['nom']);
        $critere = $_POST['critere'];
        $desc = trim($_POST['description']);
        if (!empty($nom)) {
            $stmt = $pdo->prepare("INSERT INTO valeurs_metier (nom, critere_impacte, description) VALUES (?, ?, ?)");
            $stmt->execute([$nom, $critere, $desc]);
            $message = "✅ Valeur métier ajoutée avec succès.";
            log_audit($pdo, $_SESSION['admin_id'], 'VALUE_ADDED', "Ajout valeur métier : $nom");
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if ($admin_role === 'admin') {
            $id_del = (int)$_POST['id'];
            
            // 1. Récupérer le nom AVANT de supprimer
            $stmt_info = $pdo->prepare("SELECT nom FROM valeurs_metier WHERE id = ?");
            $stmt_info->execute([$id_del]);
            $nom_del = $stmt_info->fetchColumn() ?: "ID $id_del"; // Fallback de sécurité
            
            // 2. Supprimer
            $pdo->prepare("DELETE FROM valeurs_metier WHERE id = ?")->execute([$id_del]);
            $message = "🗑️ Valeur métier supprimée.";
            
            // 3. Journaliser avec le nom
            log_audit($pdo, $_SESSION['admin_id'], 'VALUE_DELETED', "Suppression valeur métier : $nom_del");
        } else {
            $message = "⚠️ Action bloquée. Seul un administrateur peut supprimer une valeur.";
            log_audit($pdo, $_SESSION['admin_id'], 'UNAUTHORIZED_ACTION', "Tentative de suppression de valeur métier.");
        }
    }
}

$valeurs = $pdo->query("SELECT * FROM valeurs_metier ORDER BY nom ASC")->fetchAll();
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">💎 Gestion des Valeurs Métier</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Définissez les actifs critiques à protéger.</p>

    <?php if ($message): ?>
        <div style="padding: 10px; background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 4px; margin-bottom: 20px; color: #3b82f6;"><?= $message ?></div>
    <?php endif; ?>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px;">Nom de la Valeur</th>
                <th style="padding: 10px;">Critère de Sécurité</th>
                <th style="padding: 10px;">Description</th>
                <th style="padding: 10px; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($valeurs as $v): ?>
            <tr style="border-bottom: 1px solid #30363d;">
                <td style="padding: 10px; color: #fff; font-weight: bold;"><?= htmlspecialchars($v['nom']) ?></td>
                <td style="padding: 10px;"><span style="background: #30363d; color: #c9d1d9; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;"><?= htmlspecialchars($v['critere_impacte']) ?></span></td>
                <td style="padding: 10px; color: #8b949e; font-size: 0.9rem;"><?= htmlspecialchars($v['description']) ?></td>
                <td style="padding: 10px; text-align: right;">
                    <?php if ($admin_role === 'admin'): ?>
                        <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_valeurs.php');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $v['id'] ?>">
                            <button type="submit" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" onclick="return confirm('Supprimer cette valeur métier ?');">🗑️</button>
                        </form>
                    <?php else: ?>
                        <span style="color: #8b949e; font-size: 0.8rem;" title="Droits administrateur requis">🔒</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 style="color: #3b82f6; margin-bottom: 15px;">➕ Ajouter une Valeur Métier</h4>
    <form method="POST" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_valeurs.php');" style="display: grid; grid-template-columns: 2fr 1fr 2fr auto; gap: 10px; align-items: end;">
        <input type="hidden" name="action" value="add">
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Nom</label>
            <input type="text" name="nom" required style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Critère</label>
            <select name="critere" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
                <option>Disponibilité</option><option>Confidentialité</option><option>Intégrité</option><option>Image / Réputation</option><option>Légal / Conformité</option>
            </select>
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Description</label>
            <input type="text" name="description" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: #3b82f6; border: none; color: white;">Ajouter</button>
    </form>
</div>
