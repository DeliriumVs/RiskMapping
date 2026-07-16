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
                   ov.description AS ov_desc,
                   sb.id          AS registre_id
            FROM scenarios_strategiques ss
            JOIN menaces                m  ON m.id  = ss.menace_id
            LEFT JOIN parties_prenantes pp ON pp.id = ss.pp_id
            LEFT JOIN objectifs_vises   ov ON ov.id = ss.ov_id
            LEFT JOIN scenarios_bruts   sb ON sb.source_atelier='atelier3' AND sb.source_id=ss.id AND sb.analyse_id=ss.analyse_id
            WHERE ss.analyse_id = ?
            ORDER BY ss.id ASC
        ");
        $stmt->execute([$analyse_id]);

        // Listes pour les selects du formulaire
        $srs = $pdo->prepare("SELECT id, type_source FROM menaces WHERE analyse_id=? ORDER BY id ASC");
        $srs->execute([$analyse_id]);
        $sources = $srs->fetchAll();
        foreach ($sources as $i => &$sr) { $sr['display_num'] = $i + 1; }
        unset($sr);
        $pps = $pdo->prepare("SELECT id, nom, type_pp FROM parties_prenantes WHERE analyse_id=? ORDER BY id ASC");
        $pps->execute([$analyse_id]);
        $ovs = $pdo->prepare("SELECT ov.id, ov.description, m.type_source AS sr_nom FROM objectifs_vises ov JOIN menaces m ON m.id=ov.menace_id WHERE ov.analyse_id=? AND ov.pertinence='Retenu' ORDER BY ov.menace_id, ov.id");
        $ovs->execute([$analyse_id]);

        echo json_encode([
            'status'    => 'success',
            'data'      => $stmt->fetchAll(),
            'sources'   => $sources,
            'pp_list'   => $pps->fetchAll(),
            'ov_list'   => $ovs->fetchAll(),
            'user_role' => $admin_role
        ]);
        exit;
    }

    // ── POST : créer ou transférer vers le Registre ──────────────
    if ($method === 'POST') {
        if ($admin_role === 'lecteur') { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Droits insuffisants.']); exit; }
        $i = json_decode(file_get_contents('php://input'), true);

        // ── Transfert vers le Registre des Risques ────────────────
        if (($i['action'] ?? '') === 'transfer') {
            $ss_id = (int)($i['id'] ?? 0);
            if (!$ss_id) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'ID invalide.']); exit; }

            // Déjà transféré ?
            $chk = $pdo->prepare("SELECT id FROM scenarios_bruts WHERE source_atelier='atelier3' AND source_id=? AND analyse_id=?");
            $chk->execute([$ss_id, $analyse_id]);
            if ($chk->fetchColumn()) {
                echo json_encode(['status'=>'error','message'=>'Ce scénario est déjà présent dans le Registre des Risques.']); exit;
            }

            // Récupérer les détails du scénario stratégique
            $stmt2 = $pdo->prepare("
                SELECT ss.*, m.type_source, pp.nom AS pp_nom, ov.description AS ov_desc
                FROM scenarios_strategiques ss
                JOIN menaces m ON m.id = ss.menace_id
                LEFT JOIN parties_prenantes pp ON pp.id = ss.pp_id
                LEFT JOIN objectifs_vises ov ON ov.id = ss.ov_id
                WHERE ss.id=? AND ss.analyse_id=?
            ");
            $stmt2->execute([$ss_id, $analyse_id]);
            $ss = $stmt2->fetch();
            if (!$ss) { http_response_code(404); echo json_encode(['status'=>'error','message'=>'Scénario stratégique introuvable.']); exit; }

            $ssLabel = 'SS-' . str_pad($ss_id, 3, '0', STR_PAD_LEFT);
            $titre   = $ssLabel . ' — ' . $ss['type_source'];
            if (!empty($ss['pp_nom'])) $titre .= ' via ' . $ss['pp_nom'];
            if (!empty($ss['ov_desc'])) $titre .= ' → ' . $ss['ov_desc'];
            if (mb_strlen($titre) > 255) $titre = mb_substr($titre, 0, 252) . '…';

            $pdo->prepare("INSERT INTO scenarios_bruts (analyse_id,titre,description,impact_estime,vraisemblance_estimee,source_atelier,source_id) VALUES (?,?,?,?,?,?,?)")
                ->execute([$analyse_id, $titre, $ss['description'] ?? '', (int)($ss['gravite'] ?? 0), (int)($ss['vraisemblance'] ?? 0), 'atelier3', $ss_id]);

            $new_id = (int)$pdo->lastInsertId();
            log_audit($pdo, $_SESSION['admin_id'], 'SS_TRANSFERRED', "SS #$ss_id transféré au Registre → R#$new_id");
            echo json_encode(['status'=>'success','message'=>'Scénario stratégique ajouté au Registre des Risques (R' . str_pad($new_id,3,'0',STR_PAD_LEFT) . ').','registre_id'=>$new_id]);
            exit;
        }

        // ── Import automatique depuis l'Atelier 2 (SR × OV Retenus) ─
        if (($i['action'] ?? '') === 'import_from_a2') {
            $ovs_stmt = $pdo->prepare("
                SELECT ov.id AS ov_id, ov.menace_id
                FROM objectifs_vises ov
                WHERE ov.analyse_id=? AND ov.pertinence='Retenu'
                ORDER BY ov.menace_id ASC, ov.id ASC
            ");
            $ovs_stmt->execute([$analyse_id]);
            $ovs_rows = $ovs_stmt->fetchAll();

            $created = 0;
            $skipped = 0;
            foreach ($ovs_rows as $row) {
                // Déduplication : un seul SS par couple (menace_id, ov_id)
                $chk = $pdo->prepare("SELECT id FROM scenarios_strategiques WHERE analyse_id=? AND menace_id=? AND ov_id=?");
                $chk->execute([$analyse_id, $row['menace_id'], $row['ov_id']]);
                if ($chk->fetchColumn()) { $skipped++; continue; }
                $pdo->prepare("INSERT INTO scenarios_strategiques (analyse_id,menace_id,pp_id,ov_id) VALUES (?,?,NULL,?)")
                    ->execute([$analyse_id, $row['menace_id'], $row['ov_id']]);
                $created++;
            }
            log_audit($pdo,$_SESSION['admin_id'],'SS_IMPORTED_A2',"Import A2 : $created créés, $skipped ignorés");
            echo json_encode(['status'=>'success','message'=>"$created scénarios stratégiques générés depuis l'Atelier 2.",'created'=>$created,'skipped'=>$skipped]);
            exit;
        }

        $menace_id = (int)($i['menace_id'] ?? 0);
        $pp_id     = !empty($i['pp_id']) ? (int)$i['pp_id'] : null;
        $ov_id     = !empty($i['ov_id']) ? (int)$i['ov_id'] : null;
        $desc      = trim($i['description'] ?? '');
        $grav      = max(0, min(4, (int)($i['gravite']       ?? 0)));
        $vrai      = max(0, min(4, (int)($i['vraisemblance'] ?? 0)));
        if (!$menace_id) {
            http_response_code(400); echo json_encode(['status'=>'error','message'=>'La Source de Risque est obligatoire.']); exit;
        }
        $pdo->prepare("INSERT INTO scenarios_strategiques (analyse_id,menace_id,pp_id,ov_id,description,gravite,vraisemblance) VALUES (?,?,?,?,?,?,?)")
            ->execute([$analyse_id,$menace_id,$pp_id,$ov_id,$desc,$grav,$vrai]);
        log_audit($pdo,$_SESSION['admin_id'],'SS_ADDED',"Scénario stratégique ajouté SR#$menace_id");
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
        if (array_key_exists('pp_id', $i)) {
            $new_pp = !empty($i['pp_id']) ? (int)$i['pp_id'] : null;
            $pdo->prepare("UPDATE scenarios_strategiques SET pp_id=? WHERE id=? AND analyse_id=?")->execute([$new_pp,$id,$analyse_id]);
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
