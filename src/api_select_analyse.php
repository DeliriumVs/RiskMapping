<?php
// src/api_select_analyse.php — Définit l'analyse active en session
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Accès refusé.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $analyse_id = (int)($input['analyse_id'] ?? 0);

    if (!$analyse_id) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'ID analyse manquant.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT a.id, a.nom AS analyse_nom, a.statut,
               e.id AS entite_id, e.nom AS entite_nom
        FROM analyses a
        JOIN entites e ON e.id = a.entite_id
        WHERE a.id = ?
    ");
    $stmt->execute([$analyse_id]);
    $row = $stmt->fetch();

    if (!$row) {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Analyse introuvable.']);
        exit;
    }

    $_SESSION['analyse_id']  = $row['id'];
    $_SESSION['analyse_nom'] = $row['analyse_nom'];
    $_SESSION['analyse_statut'] = $row['statut'];
    $_SESSION['entite_id']   = $row['entite_id'];
    $_SESSION['entite_nom']  = $row['entite_nom'];

    log_audit($pdo, $_SESSION['admin_id'], 'ANALYSIS_SELECTED',
        "Analyse active : {$row['entite_nom']} › {$row['analyse_nom']} (#{$row['id']})");

    echo json_encode(['status' => 'success', 'redirect' => 'registre_risques.php']);
    exit;
}

// DELETE : effacer l'analyse active (retour sélecteur)
if ($method === 'DELETE') {
    unset($_SESSION['analyse_id'], $_SESSION['analyse_nom'], $_SESSION['analyse_statut'],
          $_SESSION['entite_id'], $_SESSION['entite_nom']);
    echo json_encode(['status' => 'success']);
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
?>
