<?php
// src/export_global_csv.php
require 'db.php';

// Sécurité MJ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("Accès refusé.");
}

$filename = "REGISTRE_GLOBAL_EBIOS_" . date('Ymd_Hi') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');
fputs($output, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel

fputcsv($output, [
    'Date d\'identification',
    'Atelier d\'origine',
    'Scénario de Menace', 
    'Notes MJ (Impacts / Craintes)', 
    'Gravité (Conséquence)', 
    'Vraisemblance (Probabilité)', 
    'Niveau EBIOS OFFICIAL (MAX)',
    'Criticité Mathématique (C x V)', 
    'Stratégie de Traitement',
    'Justification / Mesures à prendre', 
    'Auteur d\'origine'
], ';');

// Ajout de traitement_updated_at dans le SELECT
$stmt = $pdo->prepare("
    SELECT s.id, s.titre, s.description, s.impact_estime, s.vraisemblance_estimee, s.niveau_ebios, s.priorite, s.strategie_traitement, s.justification_traitement, s.traitement_updated_at, s.created_at, 
           p.pseudo, sess.nom_session 
    FROM scenarios_bruts s
    JOIN participants p ON s.auteur_id = p.id
    JOIN sessions sess ON s.session_id = sess.id
    WHERE s.statut = 'traite'
    ORDER BY s.niveau_ebios DESC, s.priorite DESC
");
$stmt->execute();
$scenarios = $stmt->fetchAll();

$lbl_c = [1 => '1 - Mineure', 2 => '2 - Significative', 3 => '3 - Grave', 4 => '4 - Critique'];
$lbl_v = [1 => '1 - Très faible', 2 => '2 - Faible', 3 => '3 - Élevée', 4 => '4 - Très élevée'];

foreach ($scenarios as $s) {
    $stmtNotes = $pdo->prepare("SELECT p.pseudo, c.notes_mj FROM contributions c JOIN participants p ON c.participant_id = p.id WHERE c.scenario_id = ? AND c.notes_mj != ''");
    $stmtNotes->execute([$s['id']]);
    $notes = $stmtNotes->fetchAll();
    
    $consequences_str = "";
    foreach ($notes as $n) { $consequences_str .= "- [" . $n['pseudo'] . "] : " . $n['notes_mj'] . "\n"; }
    
    $niv = (int)$s['niveau_ebios'];
    if ($niv >= 4) $lbl_ebios = 'Critique'; elseif ($niv >= 3) $lbl_ebios = 'Élevé'; elseif ($niv >= 2) $lbl_ebios = 'Modéré'; else $lbl_ebios = 'Faible';

    // Injection de l'horodatage dans l'export Excel si existant
    $justif_export = $s['justification_traitement'];
    if (!empty($justif_export) && $s['traitement_updated_at']) {
        $justif_export = "[MAJ le " . date('d/m/Y', strtotime($s['traitement_updated_at'])) . "]\n" . $justif_export;
    }

    fputcsv($output, [
        date('d/m/Y', strtotime($s['created_at'])),
        $s['nom_session'],
        $s['titre'] . "\n" . $s['description'],
        trim($consequences_str),
        $lbl_c[$s['impact_estime']] ?? $s['impact_estime'],
        $lbl_v[$s['vraisemblance_estimee']] ?? $s['vraisemblance_estimee'],
        $niv . " - " . $lbl_ebios,
        $s['priorite'],
        $s['strategie_traitement'],
        $justif_export, 
        $s['pseudo']
    ], ';');
}

fclose($output);
exit;
?>
