<?php
// src/api_menaces.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Accès refusé."]);
    exit;
}

$analyse_id = (int)($_SESSION['analyse_id'] ?? 0);
if (!$analyse_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Aucune analyse active."]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM menaces WHERE analyse_id=? ORDER BY id ASC");
        $stmt->execute([$analyse_id]);
        echo json_encode(["status" => "success", "data" => $stmt->fetchAll(), "user_role" => $admin_role]);
        exit;
    }

    if ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $type  = trim($input['type_source']     ?? '');
        $motiv = trim($input['motivation']      ?? '');
        $capa  = trim($input['niveau_capacite'] ?? '');

        if (empty($type)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Le type de source est obligatoire."]);
            exit;
        }

        $pdo->prepare("INSERT INTO menaces (analyse_id, type_source, motivation, niveau_capacite) VALUES (?, ?, ?, ?)")
            ->execute([$analyse_id, $type, $motiv, $capa]);

        log_audit($pdo, $_SESSION['admin_id'], 'THREAT_ADDED', "Source de risque ajoutée : $type");
        echo json_encode(["status" => "success", "message" => "Source de risque ajoutée."]);
        exit;
    }

    if ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Seul un administrateur peut supprimer."]);
            exit;
        }
        $input  = json_decode(file_get_contents('php://input'), true);
        $id_del = (int)($input['id'] ?? 0);

        if ($id_del > 0) {
            $nom_stmt = $pdo->prepare("SELECT type_source FROM menaces WHERE id=? AND analyse_id=?");
            $nom_stmt->execute([$id_del, $analyse_id]);
            $nom_del = $nom_stmt->fetchColumn() ?: "ID $id_del";

            $pdo->prepare("DELETE FROM menaces WHERE id=? AND analyse_id=?")->execute([$id_del, $analyse_id]);
            log_audit($pdo, $_SESSION['admin_id'], 'THREAT_DELETED', "Source de risque supprimée : $nom_del");
            echo json_encode(["status" => "success", "message" => "Source de risque supprimée."]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID invalide."]);
        }
        exit;
    }

    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Méthode non autorisée."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erreur serveur : " . $e->getMessage()]);
}
?>
