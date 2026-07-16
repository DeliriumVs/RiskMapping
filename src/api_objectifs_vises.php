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
$analyse_id = (int)($_SESSION['analyse_id'] ?? 0);
if (!$analyse_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Aucune analyse active.']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {

    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT ov.id, ov.menace_id, ov.description, ov.pertinence, ov.niveau_pertinence, ov.notes, ov.created_at,
                   m.type_source AS sr_nom
            FROM objectifs_vises ov
            JOIN menaces m ON m.id = ov.menace_id
            WHERE ov.analyse_id = ?
            ORDER BY ov.menace_id ASC, ov.id ASC
        ");
        $stmt->execute([$analyse_id]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $srs = $pdo->prepare("SELECT id, type_source FROM menaces WHERE analyse_id=? ORDER BY id ASC");
        $srs->execute([$analyse_id]);
        $sources = $srs->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sources as $i => &$sr) { $sr['display_num'] = $i + 1; }
        unset($sr);
        $srRankMap = [];
        foreach ($sources as $sr) { $srRankMap[$sr['id']] = $sr['display_num']; }
        foreach ($data as $i => &$ov) {
            $ov['display_num']    = $i + 1;
            $ov['sr_display_num'] = $srRankMap[$ov['menace_id']] ?? $ov['menace_id'];
        }
        unset($ov);

        echo json_encode([
            'status'    => 'success',
            'data'      => $data,
            'sources'   => $sources,
            'user_role' => $admin_role
        ]);
        exit;
    }

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

        $niveau = trim($input['niveau_pertinence'] ?? '') ?: null;
        $pdo->prepare("INSERT INTO objectifs_vises (analyse_id, menace_id, description, pertinence, niveau_pertinence, notes) VALUES (?, ?, ?, ?, ?, ?)")
            ->execute([$analyse_id, $menace_id, $description, $pertinence, $niveau, $notes]);

        $sr_stmt = $pdo->prepare("SELECT type_source FROM menaces WHERE id=?");
        $sr_stmt->execute([$menace_id]);
        $sr_nom = $sr_stmt->fetchColumn() ?: "SR-$menace_id";

        log_audit($pdo, $_SESSION['admin_id'], 'OV_ADDED', "OV ajouté : $description (SR: $sr_nom)");
        echo json_encode(['status' => 'success', 'message' => "Objectif Visé ajouté pour $sr_nom."]);
        exit;
    }

    if ($method === 'PATCH') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }
        $input   = json_decode(file_get_contents('php://input'), true);
        $id      = (int)($input['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID invalide.']);
            exit;
        }

        $sets  = [];
        $vals  = [];
        if (!empty($input['pertinence']))        { $sets[] = 'pertinence=?';        $vals[] = trim($input['pertinence']); }
        if (array_key_exists('niveau_pertinence', $input)) {
            $nv = trim($input['niveau_pertinence'] ?? '') ?: null;
            $sets[] = 'niveau_pertinence=?'; $vals[] = $nv;
        }
        if (array_key_exists('notes', $input))   { $sets[] = 'notes=?';             $vals[] = trim($input['notes']); }
        if ($sets) {
            $vals[] = $id; $vals[] = $analyse_id;
            $pdo->prepare("UPDATE objectifs_vises SET " . implode(',', $sets) . " WHERE id=? AND analyse_id=?")->execute($vals);
        }

        echo json_encode(['status' => 'success', 'message' => 'Objectif Visé mis à jour.']);
        exit;
    }

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

        $desc_stmt = $pdo->prepare("SELECT description FROM objectifs_vises WHERE id=? AND analyse_id=?");
        $desc_stmt->execute([$id, $analyse_id]);
        $desc = $desc_stmt->fetchColumn() ?: "ID $id";

        $pdo->prepare("DELETE FROM objectifs_vises WHERE id=? AND analyse_id=?")->execute([$id, $analyse_id]);
        log_audit($pdo, $_SESSION['admin_id'], 'OV_DELETED', "OV supprimé : $desc");
        echo json_encode(['status' => 'success', 'message' => "Objectif Visé supprimé."]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>
