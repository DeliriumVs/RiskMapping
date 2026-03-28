<?php
// src/api_equipes.php
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
        $equipes = $pdo->query("SELECT * FROM equipes ORDER BY nom ASC")->fetchAll();
        echo json_encode([
            "status" => "success", 
            "data" => $equipes,
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
        $nom = trim($input['nom_equipe'] ?? '');

        if (empty($nom)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Le nom de l'équipe est obligatoire."]);
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO equipes (nom) VALUES (?)");
            $stmt->execute([$nom]);
            
            log_audit($pdo, $_SESSION['admin_id'], 'TEAM_ADDED', "Ajout équipe : $nom");
            echo json_encode(["status" => "success", "message" => "Équipe '$nom' ajoutée avec succès."]);
        } catch (PDOException $e) {
            // Gestion de l'erreur si l'équipe existe déjà (contrainte UNIQUE)
            http_response_code(409); // Conflict
            echo json_encode(["status" => "error", "message" => "⚠️ Cette équipe existe déjà."]);
        }
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
            $stmt_info = $pdo->prepare("SELECT nom FROM equipes WHERE id = ?");
            $stmt_info->execute([$id_del]);
            $nom_del = $stmt_info->fetchColumn() ?: "ID $id_del";

            $pdo->prepare("DELETE FROM equipes WHERE id = ?")->execute([$id_del]);
            log_audit($pdo, $_SESSION['admin_id'], 'TEAM_DELETED', "Suppression équipe : $nom_del");
            
            echo json_encode(["status" => "success", "message" => "Équipe supprimée."]);
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
