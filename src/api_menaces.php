<?php
// src/api_menaces.php
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
    // LECTURE (GET)
    // ==========================================================
    if ($method === 'GET') {
        $menaces = $pdo->query("SELECT * FROM menaces ORDER BY type_source ASC")->fetchAll();
        echo json_encode([
            "status" => "success", 
            "data" => $menaces,
            "user_role" => $admin_role
        ]);
        exit;
    }

    // ==========================================================
    // CRÉATION (POST)
    // ==========================================================
    elseif ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        
        $type = trim($input['type_source'] ?? '');
        $motiv = trim($input['motivation'] ?? '');
        $capa = trim($input['niveau_capacite'] ?? '');

        if (empty($type) || empty($capa)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "La source et la capacité sont obligatoires."]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO menaces (type_source, motivation, niveau_capacite) VALUES (?, ?, ?)");
        $stmt->execute([$type, $motiv, $capa]);
        
        log_audit($pdo, $_SESSION['admin_id'], 'THREAT_ADDED', "Ajout menace : $type");
        
        echo json_encode(["status" => "success", "message" => "Source de menace ajoutée."]);
        exit;
    }

    // ==========================================================
    // SUPPRESSION (DELETE)
    // ==========================================================
    elseif ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Seul un administrateur peut supprimer."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id_del = (int)($input['id'] ?? 0);

        if ($id_del > 0) {
            $stmt_info = $pdo->prepare("SELECT type_source FROM menaces WHERE id = ?");
            $stmt_info->execute([$id_del]);
            $nom_del = $stmt_info->fetchColumn() ?: "ID $id_del";

            $pdo->prepare("DELETE FROM menaces WHERE id = ?")->execute([$id_del]);
            log_audit($pdo, $_SESSION['admin_id'], 'THREAT_DELETED', "Suppression menace : $nom_del");
            
            echo json_encode(["status" => "success", "message" => "Menace supprimée."]);
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
