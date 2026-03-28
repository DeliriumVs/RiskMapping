<?php
// src/api_historique.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

// 1. Vérification de sécurité (RBAC)
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') { 
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Accès refusé."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ==========================================================
    // LECTURE (GET) : Historique des sessions
    // ==========================================================
    if ($method === 'GET') {
        // On récupère les sessions avec le décompte des risques et des joueurs
        $sql = "SELECT sess.*, 
                (SELECT COUNT(*) FROM scenarios_bruts WHERE session_id = sess.id) as nb_risques, 
                (SELECT COUNT(*) FROM participants WHERE session_id = sess.id) as nb_joueurs 
                FROM sessions sess 
                ORDER BY created_at DESC";
                
        $sessions = $pdo->query($sql)->fetchAll();
        
        echo json_encode([
            "status" => "success", 
            "data" => $sessions,
            "user_role" => $admin_role
        ]);
        exit;
    }

    // ==========================================================
    // SUPPRESSION (DELETE) : Effacement en cascade
    // ==========================================================
    elseif ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Seul un administrateur peut supprimer un atelier."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $del_id = (int)($input['id_session'] ?? 0);

        if ($del_id > 0) {
            // Récupérer le nom pour l'audit
            $stmt_info = $pdo->prepare("SELECT nom_session FROM sessions WHERE id = ?");
            $stmt_info->execute([$del_id]);
            $nom_del = $stmt_info->fetchColumn() ?: "ID $del_id";

            // Suppression en cascade (Bretelles de sécurité SQL)
            $pdo->prepare("DELETE c FROM contributions c JOIN scenarios_bruts s ON c.scenario_id = s.id WHERE s.session_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE svm FROM scenario_valeurs_metier svm JOIN scenarios_bruts s ON svm.scenario_id = s.id WHERE s.session_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE sm FROM scenario_menaces sm JOIN scenarios_bruts s ON sm.scenario_id = s.id WHERE s.session_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE v FROM votes_poker v JOIN scenarios_bruts s ON v.scenario_id = s.id WHERE s.session_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE FROM scenarios_bruts WHERE session_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE FROM participants WHERE session_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE FROM sessions WHERE id = ?")->execute([$del_id]);
            
            // Piste d'audit
            log_audit($pdo, $_SESSION['admin_id'], 'SESSION_DELETED', "Suppression en cascade de la session : $nom_del");
            
            echo json_encode(["status" => "success", "message" => "Atelier '$nom_del' et ses données associées supprimés."]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID de session invalide."]);
        }
        exit;
    }

    else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Méthode HTTP non autorisée."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erreur serveur : " . $e->getMessage()]);
}
?>
