<?php
// src/api_analyses.php
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

    // GET : liste des analyses (filtrées par entite_id si fourni)
    if ($method === 'GET') {
        $entite_id = (int)($_GET['entite_id'] ?? 0);

        if ($entite_id) {
            $stmt = $pdo->prepare("
                SELECT a.*, e.nom AS entite_nom,
                       COUNT(DISTINCT s.id) AS nb_scenarios
                FROM analyses a
                JOIN entites e ON e.id = a.entite_id
                LEFT JOIN scenarios_bruts s ON s.analyse_id = a.id
                WHERE a.entite_id = ?
                GROUP BY a.id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$entite_id]);
        } else {
            $stmt = $pdo->query("
                SELECT a.*, e.nom AS entite_nom,
                       COUNT(DISTINCT s.id) AS nb_scenarios
                FROM analyses a
                JOIN entites e ON e.id = a.entite_id
                LEFT JOIN scenarios_bruts s ON s.analyse_id = a.id
                GROUP BY a.id
                ORDER BY e.nom ASC, a.created_at DESC
            ");
        }

        echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
        exit;
    }

    // POST : créer une analyse
    if ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $entite_id = (int)($input['entite_id'] ?? 0);
        $nom = trim($input['nom'] ?? '');
        $perimetre = trim($input['perimetre'] ?? '');
        $date_debut = !empty($input['date_debut']) ? $input['date_debut'] : null;

        if (!$entite_id || empty($nom)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Entité et nom obligatoires.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO analyses (entite_id, nom, perimetre, date_debut) VALUES (?, ?, ?, ?)");
        $stmt->execute([$entite_id, $nom, $perimetre, $date_debut]);
        $id = (int)$pdo->lastInsertId();

        log_audit($pdo, $_SESSION['admin_id'], 'ANALYSIS_CREATED', "Analyse créée : $nom (entite_id=$entite_id)");
        echo json_encode(['status' => 'success', 'message' => "Analyse « $nom » créée.", 'id' => $id]);
        exit;
    }

    // PATCH : modifier statut / nom / périmètre
    if ($method === 'PATCH') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        $nom = trim($input['nom'] ?? '');
        $perimetre = trim($input['perimetre'] ?? '');
        $statut = trim($input['statut'] ?? '');
        $date_debut = !empty($input['date_debut']) ? $input['date_debut'] : null;

        if (!$id || empty($nom)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID et nom obligatoires.']);
            exit;
        }
        if ($statut && !in_array($statut, ['en_cours', 'finalisee', 'archivee'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Statut invalide.']);
            exit;
        }

        $pdo->prepare("UPDATE analyses SET nom=?, perimetre=?, date_debut=?, statut=COALESCE(NULLIF(?,''),(SELECT statut FROM analyses WHERE id=?)) WHERE id=?")
            ->execute([$nom, $perimetre, $date_debut, $statut, $id, $id]);

        log_audit($pdo, $_SESSION['admin_id'], 'ANALYSIS_UPDATED', "Analyse #$id modifiée : $nom");
        echo json_encode(['status' => 'success', 'message' => 'Analyse mise à jour.']);
        exit;
    }

    // DELETE : supprimer une analyse (admin uniquement)
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

        // Empêcher de supprimer l'analyse active
        if ((int)($_SESSION['analyse_id'] ?? 0) === $id) {
            http_response_code(409);
            echo json_encode(['status' => 'error', 'message' => 'Impossible de supprimer l\'analyse actuellement active.']);
            exit;
        }

        $nom_stmt = $pdo->prepare("SELECT nom FROM analyses WHERE id=?");
        $nom_stmt->execute([$id]);
        $nom = $nom_stmt->fetchColumn() ?: "ID $id";

        $pdo->prepare("DELETE FROM analyses WHERE id=?")->execute([$id]);

        log_audit($pdo, $_SESSION['admin_id'], 'ANALYSIS_DELETED', "Analyse supprimée : $nom (toutes les données en cascade)");
        echo json_encode(['status' => 'success', 'message' => "Analyse « $nom » et toutes ses données supprimées."]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>
