<?php
// src/api_seed_atelier2.php — Pré-remplissage Atelier 2 avec le référentiel officiel EBIOS RM
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Accès refusé.']);
    exit;
}
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if ($admin_role === 'lecteur') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
    exit;
}
$analyse_id = (int)($_SESSION['analyse_id'] ?? 0);
if (!$analyse_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Aucune analyse active.']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);
    exit;
}

// ============================================================
// RÉFÉRENTIEL OFFICIEL EBIOS RM
// ============================================================
$REFERENCE = [
    [
        'type_source'     => 'Cybercriminels',
        'motivation'      => 'Gain financier — ransomware, fraude, extorsion',
        'motivation_note' => 3,
        'ressources_note' => 2,
        'activite_note'   => null,
        'ovs' => [
            'Voler des données monétisables, secrets ou fonds',
            'Frauder / Manipuler (virement frauduleux, altération de données)',
            'Prendre le contrôle / Faire pression (ransomware, extorsion)',
        ],
    ],
    [
        'type_source'     => 'États / groupes étatiques (APT)',
        'motivation'      => 'Espionnage, sabotage, déstabilisation stratégique',
        'motivation_note' => 2,
        'ressources_note' => 3,
        'activite_note'   => null,
        'ovs' => [
            'Espionner / Collecter de l\'information sensible ou stratégique',
            'Saboter / Détruire des systèmes ou données critiques',
            'Prendre le contrôle de systèmes d\'importance stratégique',
        ],
    ],
    [
        'type_source'     => 'Concurrents',
        'motivation'      => 'Avantage économique ou industriel',
        'motivation_note' => 2,
        'ressources_note' => 2,
        'activite_note'   => null,
        'ovs' => [
            'Espionner / Collecter (secrets industriels, R&D, offres commerciales)',
            'Voler la propriété intellectuelle ou les données clients',
        ],
    ],
    [
        'type_source'     => 'Hacktivistes',
        'motivation'      => 'Idéologie politique ou militante, protestation',
        'motivation_note' => 3,
        'ressources_note' => 1,
        'activite_note'   => null,
        'ovs' => [
            'Nuire à l\'image / Déstabiliser (défacement, fuite publique)',
            'Saboter / Détruire (déni de service, suppression de données)',
        ],
    ],
    [
        'type_source'     => 'Terroristes / groupes extrémistes',
        'motivation'      => 'Sabotage, déstabilisation, impact psychologique',
        'motivation_note' => 3,
        'ressources_note' => 1,
        'activite_note'   => null,
        'ovs' => [
            'Saboter / Détruire des infrastructures ou systèmes critiques',
            'Nuire à l\'image / Déstabiliser l\'organisation ou l\'État',
        ],
    ],
    [
        'type_source'     => 'Employés / internes malveillants',
        'motivation'      => 'Vengeance, appât du gain, pression externe, négligence',
        'motivation_note' => 2,
        'ressources_note' => 2,
        'activite_note'   => null,
        'ovs' => [
            'Voler des données ou secrets auxquels ils ont accès',
            'Saboter / Détruire (suppression, corruption de données ou systèmes)',
            'Frauder / Manipuler (modification de données, abus de droits)',
        ],
    ],
    [
        'type_source'     => 'Partenaires / sous-traitants / fournisseurs',
        'motivation'      => 'Compromission de la chaîne d\'approvisionnement (supply chain)',
        'motivation_note' => 1,
        'ressources_note' => 2,
        'activite_note'   => null,
        'ovs' => [
            'Espionner / Collecter via les accès légitimes ou les interconnexions',
            'Prendre le contrôle / Faire pression via dépendance ou vulnérabilité tierce',
        ],
    ],
    [
        'type_source'     => 'Accidentels / non intentionnels',
        'motivation'      => 'Erreur humaine, mauvaise manipulation, négligence',
        'motivation_note' => 1,
        'ressources_note' => 1,
        'activite_note'   => null,
        'ovs' => [
            'Saboter / Détruire par accident (suppression, écrasement, mauvaise config)',
        ],
    ],
];

try {
    $input   = json_decode(file_get_contents('php://input'), true);
    $replace = !empty($input['replace']);

    $pdo->beginTransaction();

    if ($replace) {
        // Supprimer les SR et OV existants (cascade via FK)
        $pdo->prepare("DELETE FROM menaces WHERE analyse_id=?")->execute([$analyse_id]);
    }

    $sr_insert = $pdo->prepare("
        INSERT INTO menaces (analyse_id, type_source, motivation, motivation_note, ressources_note, activite_note)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $ov_insert = $pdo->prepare("
        INSERT INTO objectifs_vises (analyse_id, menace_id, description, pertinence)
        VALUES (?, ?, ?, 'A évaluer')
    ");

    $sr_count = 0;
    $ov_count = 0;

    foreach ($REFERENCE as $sr) {
        if (!$replace) {
            // Vérifier si ce type de source existe déjà
            $check = $pdo->prepare("SELECT id FROM menaces WHERE analyse_id=? AND type_source=?");
            $check->execute([$analyse_id, $sr['type_source']]);
            if ($check->fetchColumn()) continue;
        }

        $sr_insert->execute([
            $analyse_id,
            $sr['type_source'],
            $sr['motivation'],
            $sr['motivation_note'],
            $sr['ressources_note'],
            $sr['activite_note'],
        ]);
        $sr_id = (int)$pdo->lastInsertId();
        $sr_count++;

        foreach ($sr['ovs'] as $ov_desc) {
            $ov_insert->execute([$analyse_id, $sr_id, $ov_desc]);
            $ov_count++;
        }
    }

    $pdo->commit();

    log_audit($pdo, $_SESSION['admin_id'], 'SEED_ATELIER2',
        "$sr_count SR et $ov_count OV EBIOS RM insérés pour l'analyse #$analyse_id");

    echo json_encode([
        'status'   => 'success',
        'message'  => "$sr_count sources de risque et $ov_count objectifs visés ajoutés.",
        'sr_count' => $sr_count,
        'ov_count' => $ov_count,
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>
