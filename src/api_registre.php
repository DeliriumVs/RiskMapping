<?php
// src/api_registre.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') { 
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Accès refusé."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ==========================================================
    // LECTURE (GET) : Récupération des risques consolidés
    // ==========================================================
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT s.*, p.pseudo, sess.nom_session 
            FROM scenarios_bruts s 
            JOIN participants p ON s.auteur_id = p.id 
            JOIN sessions sess ON s.session_id = sess.id 
            WHERE s.statut = 'traite' 
            ORDER BY s.niveau_ebios DESC, s.priorite DESC, s.created_at DESC
        ");
        $stmt->execute();
        $scenarios = $stmt->fetchAll();

        // Ajout d'un ID visuel (R1, R2...) pour le frontend
        $counter = 1;
        foreach ($scenarios as &$s) {
            $s['visual_id'] = 'R' . $counter++;
        }

        echo json_encode([
            "status" => "success", 
            "data" => $scenarios,
            "user_role" => $admin_role
        ]);
        exit;
    }

    // ==========================================================
    // SUPPRESSION (DELETE) : Retrait d'un risque
    // ==========================================================
    elseif ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Droits d'administration requis."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $del_id = (int)($input['id_scenario'] ?? 0);

        if ($del_id > 0) {
            $stmt_info = $pdo->prepare("SELECT titre FROM scenarios_bruts WHERE id = ?");
            $stmt_info->execute([$del_id]);
            $titre_del = $stmt_info->fetchColumn() ?: "ID $del_id";
            
            // Nettoyage en cascade manuel
            $pdo->prepare("DELETE FROM contributions WHERE scenario_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE FROM scenario_valeurs_metier WHERE scenario_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE FROM scenario_menaces WHERE scenario_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE FROM votes_poker WHERE scenario_id = ?")->execute([$del_id]);
            $pdo->prepare("DELETE FROM scenarios_bruts WHERE id = ?")->execute([$del_id]);
            
            log_audit($pdo, $_SESSION['admin_id'], 'RISK_DELETED', "Suppression du scénario : $titre_del");
            echo json_encode(["status" => "success", "message" => "Scénario supprimé du registre."]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID invalide."]);
        }
        exit;
    }

    // ==========================================================
    // PATCH : Basculer le statut de qualification d'un scénario
    // ==========================================================
    elseif ($method === 'PATCH') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]);
            exit;
        }

        $input  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($input['id'] ?? 0);
        $statut = trim($input['statut_qualification'] ?? '');

        if (!$id || !in_array($statut, ['a_qualifier', 'qualifie'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Paramètres invalides."]);
            exit;
        }

        $pdo->prepare("UPDATE scenarios_bruts SET statut_qualification = ? WHERE id = ?")
            ->execute([$statut, $id]);

        $label = $statut === 'qualifie' ? 'Qualifié' : 'À qualifier';
        log_audit($pdo, $_SESSION['admin_id'], 'RISK_QUALIFIED', "Qualification scénario ID $id → $label");

        echo json_encode(["status" => "success", "message" => "Statut mis à jour : $label."]);
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
