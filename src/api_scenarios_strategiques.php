<?php
// src/api_scenarios_strategiques.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    http_response_code(403); echo json_encode(['status'=>'error','message'=>'Accès refusé.']); exit;
}
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
$analyse_id = (int)($_SESSION['analyse_id'] ?? 0);
if (!$analyse_id) {
    http_response_code(400); echo json_encode(['status'=>'error','message'=>'Aucune analyse active.']); exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ── GET ──────────────────────────────────────────────────────
    if ($method === 'GET') {
        $stmt = $pdo->prepare("
            SELECT ss.id, ss.analyse_id, ss.menace_id, ss.pp_id, ss.ov_id,
                   ss.description, ss.gravite, ss.vraisemblance, ss.statut, ss.created_at,
                   m.type_source  AS sr_nom,
                   pp.nom         AS pp_nom, pp.type_pp,
                   ov.description AS ov_desc
            FROM scenarios_strategiques ss
            JOIN menaces           m  ON m.id  = ss.menace_id
            JOIN parties_prenantes pp ON pp.id = ss.pp_id
            LEFT JOIN objectifs_vises ov ON ov.id = ss.ov_id
            WHERE ss.analyse_id = ?
            ORDER BY ss.id ASC
        ");
        $stmt->execute([$analyse_id]);

        // Listes pour les selects du formulaire
        $srs = $pdo->prepare("SELECT id, type_source FROM menaces WHERE analyse_id=? ORDER BY id ASC");
        $srs->execute([$analyse_id]);
        $pps = $pdo->prepare("SELECT id, nom, type_pp FROM parties_prenantes WHERE analyse_id=? ORDER BY id ASC");
        $pps->execute([$analyse_id]);
        $ovs = $pdo->prepare("SELECT ov.id, ov.description, m.type_source AS sr_nom FROM objectifs_vises ov JOIN menaces m ON m.id=ov.menace_id WHERE ov.analyse_id=? AND ov.pertinence='Retenu' ORDER BY ov.menace_id, ov.id");
        $ovs->execute([$analyse_id]);

        echo json_encode([
            'status'    => 'success',
            'data'      => $stmt->fetchAll(),
            'sources'   => $srs->fetchAll(),
            'pp_list'   => $pps->fetchAll(),
            'ov_list'   => $ovs->fetchAll(),
            'user_role' => $admin_role
        ]);
        exit;
    }

    // ── POST : créer ──────────────────────────────────────────────
    if ($method === 'POST') {
        if ($admin_role === 'lecteur') { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Droits insuffisants.']); exit; }
        $i         = json_decode(file_get_contents('php://input'), true);
        $menace_id = (int)($i['menace_id'] ?? 0);
        $pp_id     = (int)($i['pp_id']     ?? 0);
        $ov_id     = !empty($i['ov_id']) ? (int)$i['ov_id'] : null;
        $desc      = trim($i['description'] ?? '');
        $grav      = max(0, min(4, (int)($i['gravite']       ?? 0)));
        $vrai      = max(0, min(4, (int)($i['vraisemblance'] ?? 0)));
        if (!$menace_id || !$pp_id) {
            http_response_code(400); echo json_encode(['status'=>'error','message'=>'SR et PP sont obligatoires.']); exit;
        }
        $pdo->prepare("INSERT INTO scenarios_strategiques (analyse_id,menace_id,pp_id,ov_id,description,gravite,vraisemblance) VALUES (?,?,?,?,?,?,?)")
            ->execute([$analyse_id,$menace_id,$pp_id,$ov_id,$desc,$grav,$vrai]);
        log_audit($pdo,$_SESSION['admin_id'],'SS_ADDED',"Scénario stratégique ajouté SR#$menace_id × PP#$pp_id");
        echo json_encode(['status'=>'success','message'=>'Scénario stratégique ajouté.','id'=>(int)$pdo->lastInsertId()]);
        exit;
    }

    // ── PATCH : statut, gravite, vraisemblance ───────────────────
    if ($method === 'PATCH') {
        if ($admin_role === 'lecteur') { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Droits insuffisants.']); exit; }
        $i  = json_decode(file_get_contents('php://input'), true);
        $id = (int)($i['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'ID invalide.']); exit; }
        $STATUTS = ['a_evaluer','retenu','non_retenu'];
        if (isset($i['statut']) && in_array($i['statut'], $STATUTS, true)) {
            $pdo->prepare("UPDATE scenarios_strategiques SET statut=? WHERE id=? AND analyse_id=?")->execute([$i['statut'],$id,$analyse_id]);
        }
        if (isset($i['gravite'])) {
            $pdo->prepare("UPDATE scenarios_strategiques SET gravite=? WHERE id=? AND analyse_id=?")->execute([max(0,min(4,(int)$i['gravite'])),$id,$analyse_id]);
        }
        if (isset($i['vraisemblance'])) {
            $pdo->prepare("UPDATE scenarios_strategiques SET vraisemblance=? WHERE id=? AND analyse_id=?")->execute([max(0,min(4,(int)$i['vraisemblance'])),$id,$analyse_id]);
        }
        echo json_encode(['status'=>'success','message'=>'Scénario mis à jour.']);
        exit;
    }

    // ── DELETE ───────────────────────────────────────────────────
    if ($method === 'DELETE') {
        if ($admin_role !== 'admin') { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Droits admin requis.']); exit; }
        $i  = json_decode(file_get_contents('php://input'), true);
        $id = (int)($i['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'ID invalide.']); exit; }
        $pdo->prepare("DELETE FROM scenarios_strategiques WHERE id=? AND analyse_id=?")->execute([$id,$analyse_id]);
        log_audit($pdo,$_SESSION['admin_id'],'SS_DELETED',"Scénario stratégique supprimé #$id");
        echo json_encode(['status'=>'success','message'=>'Scénario supprimé.']);
        exit;
    }

    http_response_code(405); echo json_encode(['status'=>'error','message'=>'Méthode non autorisée.']);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['status'=>'error','message'=>'Erreur : '.$e->getMessage()]);
}
?>
