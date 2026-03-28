<?php
// src/admin_equipes.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $admin_role === 'lecteur') { die("<div style='color:red; padding:20px;'>Accès refusé.</div>"); }

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $nom = trim($_POST['nom_equipe']);
        if (!empty($nom)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO equipes (nom) VALUES (?)");
                $stmt->execute([$nom]);
                $message = "✅ Équipe '$nom' ajoutée avec succès.";
                log_audit($pdo, $_SESSION['admin_id'], 'TEAM_ADDED', "Ajout équipe : $nom");
            } catch (PDOException $e) {
                $message = "⚠️ Cette équipe existe déjà.";
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        if ($admin_role === 'admin') {
            $id_del = (int)$_POST['id_equipe'];
            
            $stmt_info = $pdo->prepare("SELECT nom FROM equipes WHERE id = ?");
            $stmt_info->execute([$id_del]);
            $nom_del = $stmt_info->fetchColumn() ?: "ID $id_del";
            
            $pdo->prepare("DELETE FROM equipes WHERE id = ?")->execute([$id_del]);
            $message = "🗑️ Équipe supprimée.";
            log_audit($pdo, $_SESSION['admin_id'], 'TEAM_DELETED', "Suppression équipe : $nom_del");
        } else {
            $message = "⚠️ Action bloquée. Seul un administrateur peut supprimer.";
            log_audit($pdo, $_SESSION['admin_id'], 'UNAUTHORIZED_ACTION', "Tentative de suppression d'équipe.");
        }
    }
}

$equipes = $pdo->query("SELECT * FROM equipes ORDER BY nom")->fetchAll();
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">🏢 Gestion des Équipes / Services</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Personnalisez la liste des directions.</p>

    <?php if ($message): ?>
        <div style="padding: 10px; background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 4px; margin-bottom: 20px; color: #3b82f6;"><?= $message ?></div>
    <?php endif; ?>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px;">Nom de la Direction / Équipe</th>
                <th style="padding: 10px; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($equipes as $eq): ?>
            <tr style="border-bottom: 1px solid #30363d;">
                <td style="padding: 10px; color: #fff; font-weight: bold;"><?= htmlspecialchars($eq['nom']) ?></td>
                <td style="padding: 10px; text-align: right;">
                    <?php if ($admin_role === 'admin'): ?>
                        <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_equipes.php');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_equipe" value="<?= $eq['id'] ?>">
                            <button type="submit" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" onclick="return confirm('Supprimer ce service de la liste ?');">🗑️</button>
                        </form>
                    <?php else: ?>
                        <span style="color: #8b949e; font-size: 0.8rem;" title="Droits administrateur requis">🔒</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h4 style="color: #3b82f6; margin-bottom: 15px;">➕ Ajouter un nouveau service</h4>
    <form method="POST" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_equipes.php');" style="display: flex; gap: 10px;">
        <input type="hidden" name="action" value="add">
        <input type="text" name="nom_equipe" placeholder="Ex: Direction des Ressources Humaines" required style="flex: 1; padding: 10px; background: #0d1117; color: #fff; border: 1px solid #3b82f6; border-radius: 4px;">
        <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: #3b82f6; border: none; color: white;">Ajouter</button>
    </form>
</div>
