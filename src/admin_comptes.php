<?php
// src/admin_comptes.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $_SESSION['admin_role'] !== 'admin') { 
    die("<div style='color:red; padding:20px;'>Accès refusé. Privilèges administrateur requis.</div>"); 
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. CRÉATION
    if (isset($_POST['action']) && $_POST['action'] === 'add') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role_utilisateur'];
        
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/';
        if (!preg_match($regex, $password)) {
            $message = "⚠️ Mot de passe trop faible (Min 12, Maj, Min, Chiffre, Spécial).";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_ARGON2ID);
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES (?, ?, ?)");
                $stmt->execute([$username, $hash, $role]);
                $message = "✅ Compte '$username' provisionné.";
                log_audit($pdo, $_SESSION['admin_id'], 'ACCOUNT_CREATED', "Création du compte '$username' (Rôle: $role)");
            } catch (PDOException $e) {
                $message = "⚠️ Cet identifiant existe déjà.";
            }
        }
    } 
    
    // 2. SUPPRESSION
    elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id_to_delete = (int)$_POST['id_user'];
        if ($id_to_delete === $_SESSION['admin_id']) {
            $message = "⚠️ Vous ne pouvez pas supprimer votre propre compte.";
        } else {
            $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
            $stmt->execute([$id_to_delete]);
            $user_del = $stmt->fetchColumn();
            
            $pdo->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$id_to_delete]);
            $message = "🗑️ Compte supprimé.";
            log_audit($pdo, $_SESSION['admin_id'], 'ACCOUNT_DELETED', "Suppression du compte : $user_del");
        }
    }
    
    // 3. MISE À JOUR DU RÔLE
    elseif (isset($_POST['action']) && $_POST['action'] === 'edit_role') {
        $id_to_edit = (int)$_POST['id_user'];
        $new_role = $_POST['new_role'];
        
        if ($id_to_edit === $_SESSION['admin_id']) {
            $message = "⚠️ Vous ne pouvez pas rétrograder votre propre compte.";
        } else {
            if (in_array($new_role, ['admin', 'animateur', 'lecteur', 'en_attente'])) {
                $stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = ?");
                $stmt->execute([$id_to_edit]);
                $user_info = $stmt->fetch();
                
                if ($user_info && $user_info['role'] !== $new_role) {
                    $pdo->prepare("UPDATE admin_users SET role = ? WHERE id = ?")->execute([$new_role, $id_to_edit]);
                    $message = "🔄 Rôle de '{$user_info['username']}' mis à jour ($new_role).";
                    if ($user_info['role'] === 'en_attente') {
                        log_audit($pdo, $_SESSION['admin_id'], 'ACCOUNT_APPROVED', "Le compte '{$user_info['username']}' a été validé (Rôle : $new_role)");
                    } else {
                        log_audit($pdo, $_SESSION['admin_id'], 'ROLE_UPDATED', "Changement de rôle pour '{$user_info['username']}' : {$user_info['role']} -> $new_role");
                    }
                }
            }
        }
    }

    // 4. VERROUILLAGE / DÉVERROUILLAGE
    elseif (isset($_POST['action']) && $_POST['action'] === 'toggle_lock') {
        $id_to_lock = (int)$_POST['id_user'];
        if ($id_to_lock === $_SESSION['admin_id']) {
            $message = "⚠️ Vous ne pouvez pas verrouiller votre propre compte.";
        } else {
            $stmt = $pdo->prepare("SELECT username, is_locked FROM admin_users WHERE id = ?");
            $stmt->execute([$id_to_lock]);
            $user_info = $stmt->fetch();
            
            if ($user_info) {
                $new_state = $user_info['is_locked'] ? 0 : 1;
                $pdo->prepare("UPDATE admin_users SET is_locked = ? WHERE id = ?")->execute([$new_state, $id_to_lock]);
                
                $action_name = $new_state ? 'verrouillé' : 'déverrouillé';
                $message = "🔒 Le compte de '{$user_info['username']}' a été $action_name.";
                log_audit($pdo, $_SESSION['admin_id'], 'ACCOUNT_LOCK_TOGGLED', "Le compte '{$user_info['username']}' a été $action_name.");
            }
        }
    }

    // 5. RÉINITIALISATION DU MOT DE PASSE
    elseif (isset($_POST['action']) && $_POST['action'] === 'reset_password') {
        $id_to_reset = (int)$_POST['id_user'];
        $new_password = $_POST['new_password'];
        
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/';
        if (!preg_match($regex, $new_password)) {
            $message = "⚠️ Le nouveau mot de passe ne respecte pas la politique de sécurité.";
        } else {
            $hash = password_hash($new_password, PASSWORD_ARGON2ID);
            $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")->execute([$hash, $id_to_reset]);
            
            $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
            $stmt->execute([$id_to_reset]);
            $username_reset = $stmt->fetchColumn();
            
            $message = "🔑 Mot de passe mis à jour avec succès pour '$username_reset'.";
            log_audit($pdo, $_SESSION['admin_id'], 'PASSWORD_RESET', "Réinitialisation du mot de passe pour le compte : $username_reset");
        }
    }
}

$utilisateurs = $pdo->query("SELECT id, username, role, motif_demande, is_locked, created_at FROM admin_users ORDER BY CASE WHEN role = 'en_attente' THEN 1 ELSE 2 END, role ASC, username ASC")->fetchAll();
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">👤 Gestion des Comptes et Approbations</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Gérez les accès, validez les inscriptions et réinitialisez les mots de passe.</p>

    <?php if ($message): ?>
        <div style="padding: 10px; background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; border-radius: 4px; margin-bottom: 20px; color: #3b82f6;"><?= $message ?></div>
    <?php endif; ?>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px;">Email / Identifiant</th>
                <th style="padding: 10px;">Rôle système</th>
                <th style="padding: 10px;">Date d'inscription</th>
                <th style="padding: 10px; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($utilisateurs as $u): ?>
            <tr style="border-bottom: 1px solid #30363d; <?= $u['is_locked'] ? 'background: rgba(255, 68, 68, 0.05);' : ($u['role'] === 'en_attente' ? 'background: rgba(255, 165, 0, 0.05);' : '') ?>">
                
                <td style="padding: 10px; color: #fff; font-weight: bold;">
                    <span style="<?= $u['is_locked'] ? 'text-decoration: line-through; color: #8b949e;' : '' ?>">
                        <?= htmlspecialchars($u['username']) ?>
                    </span>
                    <?php if ($u['is_locked']): ?>
                        <span style="color: #ff4d4d; font-size: 0.8rem; margin-left: 5px;">[VERROUILLÉ]</span>
                    <?php endif; ?>
                    
                    <?php if ($u['role'] === 'en_attente' && !empty($u['motif_demande'])): ?>
                        <div style="font-size: 0.8rem; color: orange; font-weight: normal; margin-top: 5px; font-style: italic;">
                            Motif : "<?= nl2br(htmlspecialchars($u['motif_demande'])) ?>"
                        </div>
                    <?php endif; ?>
                </td>
                
                <td style="padding: 10px;">
                    <?php if ($u['id'] === $_SESSION['admin_id']): ?>
                        <span style="background: rgba(218, 41, 28, 0.1); color: #da291c; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem; text-transform: uppercase;">Administrateur (Vous)</span>
                    <?php else: ?>
                        <form method="POST" style="display:flex; align-items:center; gap:8px;" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_comptes.php');">
                            <input type="hidden" name="action" value="edit_role">
                            <input type="hidden" name="id_user" value="<?= $u['id'] ?>">
                            <select name="new_role" style="padding: 4px; background: #0d1117; color: #fff; border: 1px solid <?= $u['role'] === 'en_attente' ? 'orange' : '#30363d' ?>; border-radius: 4px; font-size: 0.85rem;" <?= $u['is_locked'] ? 'disabled' : '' ?>>
                                <?php if ($u['role'] === 'en_attente'): ?>
                                    <option value="en_attente" selected>⏳ En attente</option>
                                <?php endif; ?>
                                <option value="lecteur" <?= $u['role'] === 'lecteur' ? 'selected' : '' ?>>Lecteur</option>
                                <option value="animateur" <?= $u['role'] === 'animateur' ? 'selected' : '' ?>>Animateur</option>
                                <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <button type="submit" style="background: <?= $u['role'] === 'en_attente' ? 'orange' : '#30363d' ?>; border: 1px solid <?= $u['role'] === 'en_attente' ? 'orange' : '#484f58' ?>; color: <?= $u['role'] === 'en_attente' ? '#000' : '#c9d1d9' ?>; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.75rem; font-weight: bold;" <?= $u['is_locked'] ? 'disabled' : '' ?>>Valider / Modif.</button>
                        </form>
                    <?php endif; ?>
                </td>
                
                <td style="padding: 10px; color: #8b949e; font-size: 0.9rem;"><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                
                <td style="padding: 10px; text-align: right;">
                    <?php if ($u['id'] !== $_SESSION['admin_id']): ?>
                        
                        <form method="POST" style="display:inline; margin-right: 10px;" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_comptes.php');">
                            <input type="hidden" name="action" value="toggle_lock">
                            <input type="hidden" name="id_user" value="<?= $u['id'] ?>">
                            <button type="submit" style="background:none; border:none; color: <?= $u['is_locked'] ? '#00ffcc' : 'orange' ?>; cursor:pointer; font-size: 1.2rem;" title="<?= $u['is_locked'] ? 'Déverrouiller le compte' : 'Verrouiller le compte (sans supprimer)' ?>">
                                <?= $u['is_locked'] ? '🔓' : '🔒' ?>
                            </button>
                        </form>

                        <form method="POST" style="display:inline;" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_comptes.php');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id_user" value="<?= $u['id'] ?>">
                            <button type="submit" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" title="Supprimer définitivement" onclick="return confirm('Révoquer/Rejeter totalement l\'accès de cet utilisateur ?');">🗑️</button>
                        </form>
                        
                    <?php else: ?>
                        <span style="color: #484f58; font-size: 0.8rem;">(Vous-même)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">
        
        <div>
            <h4 style="color: #3b82f6; margin-bottom: 15px;">➕ Créer manuellement un accès</h4>
            <form method="POST" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_comptes.php');" style="display: flex; flex-direction: column; gap: 10px; background: #0d1117; padding: 20px; border-radius: 8px; border: 1px solid #30363d;">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Email / Identifiant</label>
                    <input type="text" name="username" required style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                </div>
                
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Mot de passe de sécurité</label>
                    <input type="password" name="password" required pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}" placeholder="Min 12 car., Maj, Min, Chiffre, Spécial" style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                </div>
                
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Niveau d'habilitation</label>
                    <select name="role_utilisateur" style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                        <option value="lecteur">Lecteur</option><option value="animateur">Animateur</option><option value="admin">Administrateur</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: #3b82f6; border: none; color: white; margin-top: 5px;">Provisionner</button>
            </form>
        </div>

        <div>
            <h4 style="color: var(--accent-green); margin-bottom: 15px;">🔑 Réinitialiser un mot de passe</h4>
            <form method="POST" onsubmit="event.preventDefault(); handleAjaxForm(this, 'admin_comptes.php');" style="display: flex; flex-direction: column; gap: 10px; background: #0d1117; padding: 20px; border-radius: 8px; border: 1px solid #30363d;">
                <input type="hidden" name="action" value="reset_password">
                
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Sélectionner le compte</label>
                    <select name="id_user" required style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                        <?php foreach($utilisateurs as $user_opt): ?>
                            <option value="<?= $user_opt['id'] ?>">
                                <?= htmlspecialchars($user_opt['username']) ?> (<?= $user_opt['role'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Nouveau mot de passe de sécurité</label>
                    <input type="password" name="new_password" required pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}" placeholder="Min 12 car., Maj, Min, Chiffre, Spécial" style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                </div>
                
                <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: var(--accent-green); border: none; color: #000; margin-top: 5px; font-weight:bold;">Forcer la modification</button>
            </form>
        </div>

    </div>
</div>
