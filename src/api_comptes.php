<?php
// src/api_comptes.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

// 1. SÉCURITÉ STRICTE : Seul un Administrateur global peut interroger cette API
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || ($_SESSION['admin_role'] ?? '') !== 'admin') { 
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Accès refusé. Privilèges administrateur requis."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$current_admin_id = $_SESSION['admin_id'];

try {
    // ==========================================================
    // LECTURE (GET) : Liste des comptes
    // ==========================================================
    if ($method === 'GET') {
        $utilisateurs = $pdo->query("SELECT id, username, role, motif_demande, is_locked, created_at FROM admin_users ORDER BY CASE WHEN role = 'en_attente' THEN 1 ELSE 2 END, role ASC, username ASC")->fetchAll();
        echo json_encode([
            "status" => "success", 
            "data" => $utilisateurs,
            "current_user_id" => $current_admin_id // Pour que le JS sache qui on est
        ]);
        exit;
    }

    // ==========================================================
    // CRÉATION (POST) : Nouveau compte
    // ==========================================================
    elseif ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $role = $input['role_utilisateur'] ?? 'lecteur';
        
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/';
        if (!preg_match($regex, $password)) {
            echo json_encode(["status" => "error", "message" => "⚠️ Mot de passe trop faible (Min 12, Maj, Min, Chiffre, Spécial)."]);
            exit;
        }

        try {
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role) VALUES (?, ?, ?)");
            $stmt->execute([$username, $hash, $role]);
            
            log_audit($pdo, $current_admin_id, 'ACCOUNT_CREATED', "Création du compte '$username' (Rôle: $role)");
            echo json_encode(["status" => "success", "message" => "✅ Compte '$username' provisionné."]);
        } catch (PDOException $e) {
            http_response_code(409);
            echo json_encode(["status" => "error", "message" => "⚠️ Cet identifiant existe déjà."]);
        }
        exit;
    }

    // ==========================================================
    // MISES À JOUR PARTIELLES (PATCH) : Rôle, Lock, Password
    // ==========================================================
    elseif ($method === 'PATCH') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        $target_id = (int)($input['id_user'] ?? 0);

        if ($target_id <= 0) {
            echo json_encode(["status" => "error", "message" => "ID invalide."]); exit;
        }

        // --- A. Changer le rôle ---
        if ($action === 'edit_role') {
            if ($target_id === $current_admin_id) {
                echo json_encode(["status" => "error", "message" => "⚠️ Vous ne pouvez pas modifier votre propre rôle."]); exit;
            }
            $new_role = $input['new_role'] ?? '';
            if (in_array($new_role, ['admin', 'animateur', 'lecteur', 'en_attente'])) {
                $stmt = $pdo->prepare("SELECT username, role FROM admin_users WHERE id = ?");
                $stmt->execute([$target_id]);
                $user = $stmt->fetch();
                
                if ($user && $user['role'] !== $new_role) {
                    $pdo->prepare("UPDATE admin_users SET role = ? WHERE id = ?")->execute([$new_role, $target_id]);
                    if ($user['role'] === 'en_attente') {
                        log_audit($pdo, $current_admin_id, 'ACCOUNT_APPROVED', "Le compte '{$user['username']}' a été validé (Rôle : $new_role)");
                    } else {
                        log_audit($pdo, $current_admin_id, 'ROLE_UPDATED', "Changement de rôle pour '{$user['username']}' : {$user['role']} -> $new_role");
                    }
                    echo json_encode(["status" => "success", "message" => "🔄 Rôle de '{$user['username']}' mis à jour."]);
                } else {
                    echo json_encode(["status" => "success", "message" => "Aucun changement nécessaire."]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Rôle invalide."]);
            }
            exit;
        }
        
        // --- B. Verrouiller / Déverrouiller ---
        elseif ($action === 'toggle_lock') {
            if ($target_id === $current_admin_id) {
                echo json_encode(["status" => "error", "message" => "⚠️ Vous ne pouvez pas verrouiller votre propre compte."]); exit;
            }
            $stmt = $pdo->prepare("SELECT username, is_locked FROM admin_users WHERE id = ?");
            $stmt->execute([$target_id]);
            $user = $stmt->fetch();
            
            if ($user) {
                $new_state = $user['is_locked'] ? 0 : 1;
                $pdo->prepare("UPDATE admin_users SET is_locked = ? WHERE id = ?")->execute([$new_state, $target_id]);
                $txt_action = $new_state ? 'verrouillé' : 'déverrouillé';
                log_audit($pdo, $current_admin_id, 'ACCOUNT_LOCK_TOGGLED', "Le compte '{$user['username']}' a été $txt_action.");
                echo json_encode(["status" => "success", "message" => "🔒 Le compte de '{$user['username']}' a été $txt_action."]);
            }
            exit;
        }

        // --- C. Réinitialiser le mot de passe ---
        elseif ($action === 'reset_password') {
            $new_password = $input['new_password'] ?? '';
            $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}$/';
            if (!preg_match($regex, $new_password)) {
                echo json_encode(["status" => "error", "message" => "⚠️ Le mot de passe ne respecte pas la politique."]); exit;
            }
            
            $hash = password_hash($new_password, PASSWORD_ARGON2ID);
            $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")->execute([$hash, $target_id]);
            
            $username = $pdo->query("SELECT username FROM admin_users WHERE id = $target_id")->fetchColumn();
            log_audit($pdo, $current_admin_id, 'PASSWORD_RESET', "Réinitialisation du mot de passe pour : $username");
            
            echo json_encode(["status" => "success", "message" => "🔑 Mot de passe mis à jour pour '$username'."]);
            exit;
        }
    }

    // ==========================================================
    // SUPPRESSION (DELETE) : Retrait d'un compte
    // ==========================================================
    elseif ($method === 'DELETE') {
        $input = json_decode(file_get_contents('php://input'), true);
        $target_id = (int)($input['id_user'] ?? 0);

        if ($target_id === $current_admin_id) {
            echo json_encode(["status" => "error", "message" => "⚠️ Vous ne pouvez pas supprimer votre propre compte."]); exit;
        }

        if ($target_id > 0) {
            $stmt = $pdo->prepare("SELECT username FROM admin_users WHERE id = ?");
            $stmt->execute([$target_id]);
            $user_del = $stmt->fetchColumn();

            $pdo->prepare("DELETE FROM admin_users WHERE id = ?")->execute([$target_id]);
            log_audit($pdo, $current_admin_id, 'ACCOUNT_DELETED', "Suppression du compte : $user_del");
            
            echo json_encode(["status" => "success", "message" => "🗑️ Compte '$user_del' supprimé."]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID invalide."]);
        }
        exit;
    }

    else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Méthode non autorisée."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erreur serveur : " . $e->getMessage()]);
}
?>
