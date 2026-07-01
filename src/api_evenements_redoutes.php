<?php
// src/api_evenements_redoutes.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Accès refusé.']);
    exit;
}

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
$analyse_id = (int)($_SESSION['analyse_id'] ?? 0);
if (!$analyse_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Aucune analyse active.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

$CATEGORIES = ['Financier','Opérationnel','Juridique','Image','Santé'];
$IMPACTS    = ['Mineur','Significatif','Majeur','Critique'];

try {

    // GET : tous les ER de l'analyse (+ VMs pour construction)
    if ($method === 'GET') {
        $vm_id = (int)($_GET['valeur_metier_id'] ?? 0);

        if ($vm_id) {
            $stmt = $pdo->prepare("
                SELECT er.*, vm.nom AS vm_nom
                FROM evenements_redoutes er
                JOIN valeurs_metier vm ON vm.id = er.valeur_metier_id
                WHERE er.analyse_id = ? AND er.valeur_metier_id = ?
                ORDER BY er.id ASC
            ");
            $stmt->execute([$analyse_id, $vm_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT er.*, vm.nom AS vm_nom
                FROM evenements_redoutes er
                JOIN valeurs_metier vm ON vm.id = er.valeur_metier_id
                WHERE er.analyse_id = ?
                ORDER BY er.valeur_metier_id ASC, er.id ASC
            ");
            $stmt->execute([$analyse_id]);
        }

        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(), 'user_role' => $admin_role]);
        exit;
    }

    // POST : créer un ER
    if ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }
        $input           = json_decode(file_get_contents('php://input'), true);
        $vm_id           = (int)($input['valeur_metier_id'] ?? 0);
        $categorie       = trim($input['categorie'] ?? '');
        $description     = trim($input['description'] ?? '');
        $impact          = trim($input['impact'] ?? 'Mineur');
        $notes           = trim($input['notes'] ?? '');

        if (!$vm_id || empty($description) || !in_array($categorie, $CATEGORIES) || !in_array($impact, $IMPACTS)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'VM, catégorie, description et impact sont obligatoires.']);
            exit;
        }

        $pdo->prepare("
            INSERT INTO evenements_redoutes (analyse_id, valeur_metier_id, categorie, description, impact, notes)
            VALUES (?, ?, ?, ?, ?, ?)
        ")->execute([$analyse_id, $vm_id, $categorie, $description, $impact, $notes]);

        log_audit($pdo, $_SESSION['admin_id'], 'ER_ADDED', "ER ajouté : $categorie / $description (VM #$vm_id)");
        echo json_encode(['status' => 'success', 'message' => 'Événement Redouté ajouté.', 'id' => (int)$pdo->lastInsertId()]);
        exit;
    }

    // PATCH : modifier impact ou notes
    if ($method === 'PATCH') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }
        $input  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($input['id'] ?? 0);
        $impact = trim($input['impact'] ?? '');
        $notes  = isset($input['notes']) ? trim($input['notes']) : null;

        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID invalide.']);
            exit;
        }

        if ($impact && in_array($impact, $IMPACTS)) {
            $pdo->prepare("UPDATE evenements_redoutes SET impact=? WHERE id=? AND analyse_id=?")
                ->execute([$impact, $id, $analyse_id]);
        }
        if ($notes !== null) {
            $pdo->prepare("UPDATE evenements_redoutes SET notes=? WHERE id=? AND analyse_id=?")
                ->execute([$notes, $id, $analyse_id]);
        }

        echo json_encode(['status' => 'success', 'message' => 'Événement Redouté mis à jour.']);
        exit;
    }

    // DELETE : supprimer un ER (admin)
    if ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits d\'administration requis.']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $id    = (int)($input['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID invalide.']);
            exit;
        }

        $desc_stmt = $pdo->prepare("SELECT description FROM evenements_redoutes WHERE id=? AND analyse_id=?");
        $desc_stmt->execute([$id, $analyse_id]);
        $desc = $desc_stmt->fetchColumn() ?: "ID $id";

        $pdo->prepare("DELETE FROM evenements_redoutes WHERE id=? AND analyse_id=?")->execute([$id, $analyse_id]);
        log_audit($pdo, $_SESSION['admin_id'], 'ER_DELETED', "ER supprimé : $desc");
        echo json_encode(['status' => 'success', 'message' => 'Événement Redouté supprimé.']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>
