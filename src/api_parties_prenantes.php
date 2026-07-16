<?php
// src/api_parties_prenantes.php
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

// ── Matrices de calcul ──────────────────────────────────────────
// Exposition[dependance][penetration] → 1-4
$EXPOSITION = [
    1 => [1=>1, 2=>1, 3=>2, 4=>2],
    2 => [1=>1, 2=>2, 3=>2, 4=>3],
    3 => [1=>2, 2=>2, 3=>3, 4=>3],
    4 => [1=>2, 2=>3, 3=>3, 4=>4],
];
// Niveau Menace[exposition][fiabilite] → 1-4 (fiabilité haute = menace basse)
$MENACE = [
    1 => [1=>2, 2=>1, 3=>1, 4=>1],
    2 => [1=>3, 2=>2, 3=>1, 4=>1],
    3 => [1=>4, 2=>3, 3=>2, 4=>1],
    4 => [1=>4, 2=>4, 3=>3, 4=>2],
];

function compute_pp(array $pp, array $EXPOSITION, array $MENACE): array {
    $dep  = max(1, min(4, (int)($pp['dependance']    ?? 1)));
    $pen  = max(1, min(4, (int)($pp['penetration']   ?? 1)));
    $mat  = max(1, min(4, (int)($pp['maturite_cyber']?? 1)));
    $conf = max(1, min(4, (int)($pp['confiance']     ?? 1)));
    $expo    = $EXPOSITION[$dep][$pen];
    $fiab    = max(1, min(4, (int)round(($mat + $conf) / 2)));
    $menace  = $MENACE[$expo][$fiab];
    $pp['exposition_calc']   = $expo;
    $pp['fiabilite_calc']    = $fiab;
    $pp['niveau_menace_calc']= $menace;
    return $pp;
}

$method = $_SERVER['REQUEST_METHOD'];
$TYPES  = ['Interne','Externe','Partenaire','Fournisseur','Client','Prestataire','Autre'];

try {
    // ── GET ──────────────────────────────────────────────────────
    if ($method === 'GET') {
        $stmt = $pdo->prepare("SELECT * FROM parties_prenantes WHERE analyse_id=? ORDER BY id ASC");
        $stmt->execute([$analyse_id]);
        $rows = array_map(fn($r) => compute_pp($r, $EXPOSITION, $MENACE), $stmt->fetchAll());
        echo json_encode(['status'=>'success','data'=>$rows,'user_role'=>$admin_role]);
        exit;
    }

    // ── POST : créer ──────────────────────────────────────────────
    if ($method === 'POST') {
        if ($admin_role === 'lecteur') { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Droits insuffisants.']); exit; }
        $i   = json_decode(file_get_contents('php://input'), true);
        $nom = trim($i['nom'] ?? '');
        if (empty($nom)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Le nom est obligatoire.']); exit; }
        $type = in_array($i['type_pp'] ?? '', $TYPES) ? $i['type_pp'] : 'Externe';
        $dep  = max(1, min(4, (int)($i['dependance']    ?? 1)));
        $pen  = max(1, min(4, (int)($i['penetration']   ?? 1)));
        $mat  = max(1, min(4, (int)($i['maturite_cyber']?? 1)));
        $conf = max(1, min(4, (int)($i['confiance']     ?? 1)));
        $desc = trim($i['description'] ?? '');
        $pdo->prepare("INSERT INTO parties_prenantes (analyse_id,nom,type_pp,description,dependance,penetration,maturite_cyber,confiance) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$analyse_id,$nom,$type,$desc,$dep,$pen,$mat,$conf]);
        log_audit($pdo,$_SESSION['admin_id'],'PP_ADDED',"PP ajoutée : $nom");
        echo json_encode(['status'=>'success','message'=>'Partie Prenante ajoutée.','id'=>(int)$pdo->lastInsertId()]);
        exit;
    }

    // ── PATCH : modifier un critère (1-4) ────────────────────────
    if ($method === 'PATCH') {
        if ($admin_role === 'lecteur') { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Droits insuffisants.']); exit; }
        $i     = json_decode(file_get_contents('php://input'), true);
        $id    = (int)($i['id']    ?? 0);
        $field = $i['field']       ?? '';
        $value = (int)($i['value'] ?? 1);
        // Mise à jour nom/type/description
        if (isset($i['nom'])) {
            $nom  = trim($i['nom'] ?? '');
            $type = in_array($i['type_pp'] ?? '', $TYPES) ? $i['type_pp'] : 'Externe';
            $desc = trim($i['description'] ?? '');
            if (empty($nom)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Le nom est obligatoire.']); exit; }
            $pdo->prepare("UPDATE parties_prenantes SET nom=?, type_pp=?, description=? WHERE id=? AND analyse_id=?")
                ->execute([$nom, $type, $desc, $id, $analyse_id]);
            log_audit($pdo,$_SESSION['admin_id'],'PP_UPDATED',"PP modifiée #$id : $nom");
            echo json_encode(['status'=>'success','message'=>'Partie Prenante mise à jour.']);
            exit;
        }

        $allowed = ['dependance','penetration','maturite_cyber','confiance'];
        if (!$id || !in_array($field,$allowed,true)) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'Paramètre invalide.']); exit; }
        $val = max(1, min(4, $value));
        $pdo->prepare("UPDATE parties_prenantes SET $field=? WHERE id=? AND analyse_id=?")->execute([$val,$id,$analyse_id]);
        // Retourner les nouvelles valeurs calculées
        $row = $pdo->prepare("SELECT * FROM parties_prenantes WHERE id=? AND analyse_id=?");
        $row->execute([$id,$analyse_id]);
        $pp  = compute_pp($row->fetch(),$EXPOSITION,$MENACE);
        echo json_encode(['status'=>'success','message'=>'Critère mis à jour.','computed'=>$pp]);
        exit;
    }

    // ── DELETE ───────────────────────────────────────────────────
    if ($method === 'DELETE') {
        if ($admin_role !== 'admin') { http_response_code(403); echo json_encode(['status'=>'error','message'=>'Droits admin requis.']); exit; }
        $i  = json_decode(file_get_contents('php://input'), true);
        $id = (int)($i['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['status'=>'error','message'=>'ID invalide.']); exit; }
        $s  = $pdo->prepare("SELECT nom FROM parties_prenantes WHERE id=? AND analyse_id=?"); $s->execute([$id,$analyse_id]);
        $n  = $s->fetchColumn() ?: "ID $id";
        $pdo->prepare("DELETE FROM parties_prenantes WHERE id=? AND analyse_id=?")->execute([$id,$analyse_id]);
        log_audit($pdo,$_SESSION['admin_id'],'PP_DELETED',"PP supprimée : $n");
        echo json_encode(['status'=>'success','message'=>'Partie Prenante supprimée.']);
        exit;
    }

    http_response_code(405); echo json_encode(['status'=>'error','message'=>'Méthode non autorisée.']);
} catch (Exception $e) {
    http_response_code(500); echo json_encode(['status'=>'error','message'=>'Erreur : '.$e->getMessage()]);
}
?>
