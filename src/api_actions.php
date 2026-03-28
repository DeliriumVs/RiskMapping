<?php
// src/api_actions.php
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
    // --- GET : Récupérer les actions d'un scénario précis ---
    if ($method === 'GET') {
        $scenario_id = (int)($_GET['scenario_id'] ?? 0);
        if ($scenario_id <= 0) {
            echo json_encode(["status" => "error", "message" => "ID de scénario manquant."]); exit;
        }

        $stmt = $pdo->prepare("SELECT * FROM actions_traitement WHERE scenario_id = ? ORDER BY statut ASC, date_cible ASC");
        $stmt->execute([$scenario_id]);
        $actions = $stmt->fetchAll();

        echo json_encode(["status" => "success", "data" => $actions, "user_role" => $admin_role]);
        exit;
    }

    // --- POST : Ajouter une nouvelle action ---
    elseif ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]); exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $scenario_id = (int)($input['scenario_id'] ?? 0);
        $titre = trim($input['titre'] ?? '');
        $responsable = trim($input['responsable'] ?? '');
        $date_cible = !empty($input['date_cible']) ? $input['date_cible'] : null;
        $lien_ticket = trim($input['lien_ticket'] ?? '');

        if ($scenario_id <= 0 || empty($titre)) {
            echo json_encode(["status" => "error", "message" => "Le titre et l'ID du scénario sont obligatoires."]); exit;
        }

        $stmt = $pdo->prepare("INSERT INTO actions_traitement (scenario_id, titre, responsable, date_cible, lien_ticket) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$scenario_id, $titre, $responsable, $date_cible, $lien_ticket]);
        
        log_audit($pdo, $_SESSION['admin_id'], 'ACTION_ADDED', "Ajout d'une action sur le scénario #$scenario_id : $titre");
        echo json_encode(["status" => "success", "message" => "Action ajoutée au plan de traitement."]);
        exit;
    }

    // --- PATCH : Mettre à jour uniquement le statut ---
    elseif ($method === 'PATCH') {
        if ($admin_role === 'lecteur') {
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]); exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['action_id'] ?? 0);
        $statut = $input['statut'] ?? '';

        if ($id > 0 && in_array($statut, ['a_faire', 'en_cours', 'fait', 'bloque'])) {
            $pdo->prepare("UPDATE actions_traitement SET statut = ? WHERE id = ?")->execute([$statut, $id]);
            echo json_encode(["status" => "success", "message" => "Statut mis à jour."]);
        } else {
            echo json_encode(["status" => "error", "message" => "Données invalides."]);
        }
        exit;
    }

    // --- PUT : Mettre à jour une action complète (Édition) ---
    elseif ($method === 'PUT') {
        if ($admin_role === 'lecteur') {
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]); exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $action_id = (int)($input['action_id'] ?? 0);
        $titre = trim($input['titre'] ?? '');
        $responsable = trim($input['responsable'] ?? '');
        $date_cible = !empty($input['date_cible']) ? $input['date_cible'] : null;
        $lien_ticket = trim($input['lien_ticket'] ?? '');

        if ($action_id <= 0 || empty($titre)) {
            echo json_encode(["status" => "error", "message" => "Données invalides pour la mise à jour."]); exit;
        }

        $stmt = $pdo->prepare("UPDATE actions_traitement SET titre = ?, responsable = ?, date_cible = ?, lien_ticket = ? WHERE id = ?");
        $stmt->execute([$titre, $responsable, $date_cible, $lien_ticket, $action_id]);
        
        log_audit($pdo, $_SESSION['admin_id'], 'ACTION_UPDATED', "Modification de l'action #$action_id : $titre");
        echo json_encode(["status" => "success", "message" => "Action mise à jour avec succès."]);
        exit;
    }

    // --- DELETE : Supprimer une action ---
    elseif ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            echo json_encode(["status" => "error", "message" => "Droits d'administration requis."]); exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['action_id'] ?? 0);

        if ($id > 0) {
            $pdo->prepare("DELETE FROM actions_traitement WHERE id = ?")->execute([$id]);
            log_audit($pdo, $_SESSION['admin_id'], 'ACTION_DELETED', "Suppression d'une action #$id");
            echo json_encode(["status" => "success", "message" => "Action supprimée."]);
        } else {
            echo json_encode(["status" => "error", "message" => "ID invalide."]);
        }
        exit;
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erreur serveur : " . $e->getMessage()]);
}
?>
