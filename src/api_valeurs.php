<?php
// src/api_valeurs.php
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
        $stmt = $pdo->prepare("SELECT * FROM valeurs_metier WHERE analyse_id=? ORDER BY id ASC");
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
        $nom    = trim($input['nom']         ?? '');
        $critere = trim($input['critere']    ?? '');
        $desc   = trim($input['description'] ?? '');

        if (empty($nom)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Le nom est obligatoire."]);
            exit;
        }

        $pdo->prepare("INSERT INTO valeurs_metier (analyse_id, nom, critere_impacte, description) VALUES (?, ?, ?, ?)")
            ->execute([$analyse_id, $nom, $critere ?: null, $desc]);

        log_audit($pdo, $_SESSION['admin_id'], 'VALUE_ADDED', "Valeur métier ajoutée : $nom");
        echo json_encode(["status" => "success", "message" => "Valeur métier ajoutée."]);
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
            $nom_del = $pdo->prepare("SELECT nom FROM valeurs_metier WHERE id=? AND analyse_id=?");
            $nom_del->execute([$id_del, $analyse_id]);
            $nom_del = $nom_del->fetchColumn() ?: "ID $id_del";

            $pdo->prepare("DELETE FROM valeurs_metier WHERE id=? AND analyse_id=?")->execute([$id_del, $analyse_id]);
            log_audit($pdo, $_SESSION['admin_id'], 'VALUE_DELETED', "Suppression valeur métier : $nom_del");
            echo json_encode(["status" => "success", "message" => "Valeur métier supprimée."]);
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
