<?php
// src/api_entites.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Accès refusé.']);
    exit;
}

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
$method = $_SERVER['REQUEST_METHOD'];

try {

    // GET : liste toutes les entités avec le nombre d'analyses
    if ($method === 'GET') {
        $stmt = $pdo->query("
            SELECT e.id, e.nom, e.secteur, e.description, e.created_at,
                   COUNT(a.id) AS nb_analyses
            FROM entites e
            LEFT JOIN analyses a ON a.entite_id = e.id
            GROUP BY e.id
            ORDER BY e.nom ASC
        ");
        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // POST : créer une entité
    if ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $nom = trim($input['nom'] ?? '');
        $secteur = trim($input['secteur'] ?? '');
        $description = trim($input['description'] ?? '');

        if (empty($nom)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Le nom de l\'entité est obligatoire.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO entites (nom, secteur, description) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $secteur, $description]);
        $id = (int)$pdo->lastInsertId();

        log_audit($pdo, $_SESSION['admin_id'], 'ENTITY_CREATED', "Entité créée : $nom");
        echo json_encode(['status' => 'success', 'message' => "Entité « $nom » créée.", 'id' => $id]);
        exit;
    }

    // PATCH : renommer / modifier
    if ($method === 'PATCH') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        $nom = trim($input['nom'] ?? '');
        $secteur = trim($input['secteur'] ?? '');
        $description = trim($input['description'] ?? '');

        if (!$id || empty($nom)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID et nom obligatoires.']);
            exit;
        }

        $pdo->prepare("UPDATE entites SET nom=?, secteur=?, description=? WHERE id=?")
            ->execute([$nom, $secteur, $description, $id]);

        log_audit($pdo, $_SESSION['admin_id'], 'ENTITY_UPDATED', "Entité #$id modifiée : $nom");
        echo json_encode(['status' => 'success', 'message' => 'Entité mise à jour.']);
        exit;
    }

    // DELETE : supprimer (admin uniquement)
    if ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits d\'administration requis.']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID invalide.']);
            exit;
        }

        $nom = $pdo->prepare("SELECT nom FROM entites WHERE id=?");
        $nom->execute([$id]);
        $nom = $nom->fetchColumn() ?: "ID $id";

        $pdo->prepare("DELETE FROM entites WHERE id=?")->execute([$id]);

        log_audit($pdo, $_SESSION['admin_id'], 'ENTITY_DELETED', "Entité supprimée : $nom (cascade analyses)");
        echo json_encode(['status' => 'success', 'message' => "Entité « $nom » et toutes ses analyses supprimées."]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>
