<?php
// src/api_objectifs_vises.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Accès refusé.']);
    exit;
}

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
$method     = $_SERVER['REQUEST_METHOD'];

try {

    // ==========================================================
    // GET : Tous les OV (avec SR joint) + liste des SR pour dropdown
    // ==========================================================
    if ($method === 'GET') {
        $stmt = $pdo->query("
            SELECT ov.id,
                   ov.menace_id,
                   ov.description,
                   ov.pertinence,
                   ov.notes,
                   ov.created_at,
                   m.type_source AS sr_nom
            FROM objectifs_vises ov
            JOIN menaces m ON m.id = ov.menace_id
            ORDER BY ov.menace_id ASC, ov.id ASC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $srs = $pdo->query("SELECT id, type_source FROM menaces ORDER BY id ASC")
                   ->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status'     => 'success',
            'data'       => $data,
            'sources'    => $srs,
            'user_role'  => $admin_role
        ]);
        exit;
    }

    // ==========================================================
    // POST : Créer un Objectif Visé lié à un SR
    // ==========================================================
    if ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }

        $input       = json_decode(file_get_contents('php://input'), true);
        $menace_id   = (int)($input['menace_id']   ?? 0);
        $description = trim($input['description']  ?? '');
        $pertinence  = trim($input['pertinence']   ?? 'A évaluer');
        $notes       = trim($input['notes']        ?? '');

        if (!$menace_id || empty($description)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'La source de risque et la description sont obligatoires.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO objectifs_vises (menace_id, description, pertinence, notes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$menace_id, $description, $pertinence, $notes]);

        $sr_nom = $pdo->prepare("SELECT type_source FROM menaces WHERE id = ?");
        $sr_nom->execute([$menace_id]);
        $sr_nom = $sr_nom->fetchColumn() ?: "SR-$menace_id";

        log_audit($pdo, $_SESSION['admin_id'], 'OV_ADDED', "Ajout OV « $description » pour SR : $sr_nom");

        echo json_encode(['status' => 'success', 'message' => "Objectif Visé ajouté au couple avec $sr_nom."]);
        exit;
    }

    // ==========================================================
    // PATCH : Mettre à jour la pertinence et/ou les notes d'un OV
    // ==========================================================
    if ($method === 'PATCH') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }

        $input      = json_decode(file_get_contents('php://input'), true);
        $id         = (int)($input['id']         ?? 0);
        $pertinence = trim($input['pertinence']  ?? '');
        $notes      = isset($input['notes']) ? trim($input['notes']) : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID invalide.']);
            exit;
        }

        if ($pertinence && $notes !== null) {
            $pdo->prepare("UPDATE objectifs_vises SET pertinence = ?, notes = ? WHERE id = ?")
                ->execute([$pertinence, $notes, $id]);
        } elseif ($pertinence) {
            $pdo->prepare("UPDATE objectifs_vises SET pertinence = ? WHERE id = ?")
                ->execute([$pertinence, $id]);
        } elseif ($notes !== null) {
            $pdo->prepare("UPDATE objectifs_vises SET notes = ? WHERE id = ?")
                ->execute([$notes, $id]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Objectif Visé mis à jour.']);
        exit;
    }

    // ==========================================================
    // DELETE : Supprimer un OV (admin uniquement)
    // ==========================================================
    if ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Seul un administrateur peut supprimer.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id    = (int)($input['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID invalide.']);
            exit;
        }

        $desc = $pdo->prepare("SELECT description FROM objectifs_vises WHERE id = ?");
        $desc->execute([$id]);
        $desc = $desc->fetchColumn() ?: "ID $id";

        $pdo->prepare("DELETE FROM objectifs_vises WHERE id = ?")->execute([$id]);
        log_audit($pdo, $_SESSION['admin_id'], 'OV_DELETED', "Suppression OV : $desc");

        echo json_encode(['status' => 'success', 'message' => "Objectif Visé « $desc » supprimé."]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>
