<?php
// src/api_registre.php
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

// Table de correspondance heatmap EBIOS RM (gravité × vraisemblance → niveau 1-3)
$HEATMAP = [
    '1,1'=>2,'1,2'=>2,'1,3'=>3,'1,4'=>3,
    '2,1'=>1,'2,2'=>2,'2,3'=>3,'2,4'=>3,
    '3,1'=>1,'3,2'=>1,'3,3'=>2,'3,4'=>3,
    '4,1'=>1,'4,2'=>1,'4,3'=>2,'4,4'=>2,
];

try {
    // ==========================================================
    // GET : liste des scénarios de l'analyse active
    // ==========================================================
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT *
            FROM scenarios_bruts
            WHERE analyse_id = ?
            ORDER BY id ASC
        ");
        $stmt->execute([$analyse_id]);
        $scenarios = $stmt->fetchAll();

        $counter = 1;
        foreach ($scenarios as &$s) {
            $s['visual_id'] = 'R' . str_pad($counter++, 3, '0', STR_PAD_LEFT);
        }
        unset($s);

        echo json_encode([
            "status"    => "success",
            "data"      => $scenarios,
            "user_role" => $admin_role
        ]);
        exit;
    }

    // ==========================================================
    // POST : créer un nouveau scénario
    // ==========================================================
    if ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $titre = trim($input['titre'] ?? '');
        $description = trim($input['description'] ?? '');
        $impact = max(1, min(4, (int)($input['impact_estime'] ?? 0)));
        $vrai   = max(1, min(4, (int)($input['vraisemblance_estimee'] ?? 0)));

        if (empty($titre)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Le titre est obligatoire."]);
            exit;
        }

        $niveau = ($impact && $vrai) ? ($HEATMAP["$impact,$vrai"] ?? 1) : 0;

        $stmt = $pdo->prepare("
            INSERT INTO scenarios_bruts
                (analyse_id, titre, description, impact_estime, vraisemblance_estimee, niveau_ebios)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$analyse_id, $titre, $description, $impact ?: 0, $vrai ?: 0, $niveau]);

        log_audit($pdo, $_SESSION['admin_id'], 'RISK_CREATED', "Nouveau scénario : $titre");
        echo json_encode(["status" => "success", "message" => "Scénario créé.", "id" => (int)$pdo->lastInsertId()]);
        exit;
    }

    // ==========================================================
    // PUT : mettre à jour un scénario complet (depuis edit_scenario)
    // ==========================================================
    if ($method === 'PUT') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id    = (int)($input['id'] ?? 0);
        if (!$id) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID manquant."]);
            exit;
        }

        $titre          = trim($input['titre'] ?? '');
        $description    = trim($input['description'] ?? '');
        $impact         = max(1, min(4, (int)($input['impact_estime'] ?? 1)));
        $vrai           = max(1, min(4, (int)($input['vraisemblance_estimee'] ?? 1)));
        $strategie      = trim($input['strategie_traitement'] ?? 'À définir');
        $just_trait     = trim($input['justification_traitement'] ?? '');
        $just_imp       = trim($input['justification_impact'] ?? '');
        $just_vrai      = trim($input['justification_vraisemblance'] ?? '');
        $titre_tech     = trim($input['titre_technique'] ?? '');
        $scenario_tech  = trim($input['scenario_technique'] ?? '');

        $niveau = $HEATMAP["$impact,$vrai"] ?? 1;

        $pdo->prepare("
            UPDATE scenarios_bruts SET
                titre=?, description=?, impact_estime=?, vraisemblance_estimee=?, niveau_ebios=?,
                strategie_traitement=?, justification_traitement=?, justification_impact=?,
                justification_vraisemblance=?, titre_technique=?, scenario_technique=?,
                traitement_updated_at=NOW()
            WHERE id=? AND analyse_id=?
        ")->execute([$titre, $description, $impact, $vrai, $niveau, $strategie, $just_trait, $just_imp, $just_vrai, $titre_tech, $scenario_tech, $id, $analyse_id]);

        log_audit($pdo, $_SESSION['admin_id'], 'RISK_UPDATED', "Scénario #$id mis à jour : $titre");
        echo json_encode(["status" => "success", "message" => "Scénario mis à jour."]);
        exit;
    }

    // ==========================================================
    // PATCH : basculer statut_qualification
    // ==========================================================
    if ($method === 'PATCH') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]);
            exit;
        }

        $input  = json_decode(file_get_contents('php://input'), true);
        $id     = (int)($input['id'] ?? 0);
        $statut = trim($input['statut_qualification'] ?? '');

        if (!$id || !in_array($statut, ['a_qualifier', 'qualifie'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Paramètres invalides."]);
            exit;
        }

        $pdo->prepare("UPDATE scenarios_bruts SET statut_qualification=? WHERE id=? AND analyse_id=?")
            ->execute([$statut, $id, $analyse_id]);

        log_audit($pdo, $_SESSION['admin_id'], 'RISK_QUALIFIED', "Qualification scénario #$id → $statut");
        echo json_encode(["status" => "success", "message" => "Statut mis à jour."]);
        exit;
    }

    // ==========================================================
    // DELETE : supprimer un scénario
    // ==========================================================
    if ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Droits d'administration requis."]);
            exit;
        }

        $input  = json_decode(file_get_contents('php://input'), true);
        $del_id = (int)($input['id_scenario'] ?? 0);

        if ($del_id > 0) {
            $stmt_info = $pdo->prepare("SELECT titre FROM scenarios_bruts WHERE id=? AND analyse_id=?");
            $stmt_info->execute([$del_id, $analyse_id]);
            $titre_del = $stmt_info->fetchColumn() ?: "ID $del_id";

            // La cascade ON DELETE CASCADE supprime actions_traitement automatiquement
            $pdo->prepare("DELETE FROM scenarios_bruts WHERE id=? AND analyse_id=?")->execute([$del_id, $analyse_id]);

            log_audit($pdo, $_SESSION['admin_id'], 'RISK_DELETED', "Suppression : $titre_del");
            echo json_encode(["status" => "success", "message" => "Scénario supprimé."]);
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
