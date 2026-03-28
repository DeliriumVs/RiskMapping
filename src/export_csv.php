<?php
// src/export_csv.php
require 'db.php';

// Sécurité MJ
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("Accès refusé.");
}

$session_id = $_SESSION['session_id'];

// Nom de session pour le fichier
$stmtSess = $pdo->prepare("SELECT nom_session FROM sessions WHERE id = ?");
$stmtSess->execute([$session_id]);
$nom_session = $stmtSess->fetchColumn();
$filename = "EBIOS_RM_" . preg_replace('/[^a-zA-Z0-9]+/', '_', $nom_session) . "_" . date('Ymd') . ".csv";

// Download CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Flux écriture
$output = fopen('php://output', 'w');

// UTF-8 BOM pour Excel (Français)
fputs($output, "\xEF\xBB\xBF");

// Les entêtes de colonnes (Structure image_0.png corrigée)
fputcsv($output, [
    'Scénario de Menace', 
    'Notes MJ (Impacts / Craintes métiers)', 
    'Gravité (Conséquence)', 
    'Vraisemblance (Probabilité)', 
    'Criticité Mathématique (C x V)', 
    'Niveau de Risque EBIOS OFFICIAL (MAX)', -- LA NOUVELLE COLONNE EBIOS RM EST ICI
    'Auteur d\'origine'
], ';');

// Données validées
$stmt = $pdo->prepare("
    SELECT s.id, s.titre, s.description, s.impact_estime, s.vraisemblance_estimee, s.niveau_ebios, s.priorite, p.pseudo
    FROM scenarios_bruts s
    JOIN participants p ON s.auteur_id = p.id
    WHERE s.session_id = ? AND s.statut = 'traite'
    ORDER BY s.priorite DESC
");
$stmt->execute([$session_id]);
$scenarios = $stmt->fetchAll();

// Dictionnaires EBIOS RM pour la traduction des notes
$lbl_c = [1 => '1 - Mineure', 2 => '2 - Significative', 3 => '3 - Grave', 4 => '4 - Critique'];
$lbl_v = [1 => '1 - Très faible', 2 => '2 - Faible', 3 => '3 - Élevée', 4 => '4 - Très élevée'];

foreach ($scenarios as $s) {
    // Regroupement des notes MJ
    $stmtNotes = $pdo->prepare("
        SELECT p.pseudo, c.notes_mj 
        FROM contributions c 
        JOIN participants p ON c.participant_id = p.id 
        WHERE c.scenario_id = ? AND c.notes_mj IS NOT NULL AND c.notes_mj != ''
    ");
    $stmtNotes->execute([$s['id']]);
    $notes = $stmtNotes->fetchAll();
    
    $consequences_str = "";
    foreach ($notes as $n) {
        $consequences_str .= "- [" . $n['pseudo'] . "] : " . $n['notes_mj'] . "\n";
    }
    
    $menace_str = $s['titre'] . "\n" . $s['description'];
    
    // Calcul du label textuel pour le niveau EBIOS (MAX)
    $niveau_ebios = (int)$s['niveau_ebios'];
    if ($niveau_ebios >= 4) {
        $label_risque_ebios = 'Critique';
    } elseif ($niveau_ebios >= 3) {
        $label_risque_ebios = 'Élevé';
    } elseif ($niveau_ebios >= 2) {
        $label_risque_ebios = 'Modéré';
    } else {
        $label_risque_ebios = 'Faible';
    }
    
    $niveau_ebios_str = $niveau_ebios . " - " . $label_risque_ebios;
    
    // Écriture ligne CSV
    fputcsv($output, [
        $menace_str,
        trim($consequences_str),
        $lbl_c[$s['impact_estime']] ?? $s['impact_estime'],
        $lbl_v[$s['vraisemblance_estimee']] ?? $s['vraisemblance_estimee'],
        $s['priorite'],
        $niveau_ebios_str, -- On exporte la nouvelle valeur officielle EBIOS
        $s['pseudo']
    ], ';');
}

// Close
fclose($output);
exit;
?>
