<?php
// src/api_backup.php
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Accès réservé aux administrateurs.']);
    exit;
}

// Ordre d'export (indépendants → dépendants)
const EXPORT_TABLES = [
    'equipes', 'valeurs_metier', 'menaces', 'biens_supports', 'valeur_bien_support',
    'objectifs_vises', 'admin_users', 'sessions', 'participants', 'scenarios_bruts',
    'scenario_valeurs_metier', 'scenario_menaces', 'contributions', 'votes_poker',
    'actions_traitement', 'audit_logs'
];

// Ordre de suppression lors de l'import (plus dépendant en premier)
const DELETE_ORDER = [
    'audit_logs', 'actions_traitement', 'votes_poker', 'contributions',
    'scenario_menaces', 'scenario_valeurs_metier', 'scenarios_bruts',
    'participants', 'sessions', 'objectifs_vises', 'valeur_bien_support',
    'biens_supports', 'menaces', 'valeurs_metier', 'equipes'
    // 'admin_users' volontairement absent : on ne remplace jamais les comptes à l'import
];

// ============================================================
// EXPORT
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'export') {
    $format   = $_GET['format'] ?? 'json';
    $filename = 'riskmapping_backup_' . date('Ymd_His');

    $allData = [];
    foreach (EXPORT_TABLES as $table) {
        try {
            $allData[$table] = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $allData[$table] = [];
        }
    }

    // — JSON —
    if ($format === 'json') {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        echo json_encode([
            'meta'   => ['app' => 'RiskMapping', 'version' => '1.0', 'exported_at' => date('c')],
            'tables' => $allData
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // — SQL —
    if ($format === 'sql') {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.sql"');

        echo "-- RiskMapping SQL Backup\n";
        echo "-- Exporté le : " . date('d/m/Y H:i:s') . "\n";
        echo "-- !! Ne pas importer sans sauvegarde préalable !!\n\n";
        echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach (EXPORT_TABLES as $table) {
            $rows = $allData[$table];
            echo "-- Table : $table (" . count($rows) . " lignes)\n";
            echo "DELETE FROM `$table`;\n";
            foreach ($rows as $row) {
                $cols   = implode(', ', array_map(fn($c) => "`$c`", array_keys($row)));
                $vals   = implode(', ', array_map(
                    fn($v) => $v === null ? 'NULL' : $pdo->quote((string)$v),
                    array_values($row)
                ));
                echo "INSERT INTO `$table` ($cols) VALUES ($vals);\n";
            }
            echo "\n";
        }

        echo "SET FOREIGN_KEY_CHECKS=1;\n";
        exit;
    }

    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Format non supporté. Utilisez json ou sql.']);
    exit;
}

// ============================================================
// IMPORT (JSON uniquement)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'import') {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($_FILES['backup_file']['tmp_name'])) {
        echo json_encode(['status' => 'error', 'message' => 'Aucun fichier reçu.']);
        exit;
    }

    $raw = file_get_contents($_FILES['backup_file']['tmp_name']);
    $backup = json_decode($raw, true);

    if (!$backup || !isset($backup['tables']) || !isset($backup['meta'])) {
        echo json_encode(['status' => 'error', 'message' => 'Fichier JSON invalide ou corrompu.']);
        exit;
    }

    try {
        $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
        $pdo->beginTransaction();

        // Suppression dans l'ordre inverse des dépendances
        foreach (DELETE_ORDER as $table) {
            $pdo->exec("DELETE FROM `$table`");
        }

        // Insertion dans l'ordre des dépendances
        $inserted = 0;
        foreach (EXPORT_TABLES as $table) {
            if ($table === 'admin_users') continue; // sécurité : on ne remplace jamais les comptes
            if (!isset($backup['tables'][$table])) continue;

            $rows = $backup['tables'][$table];
            if (empty($rows)) continue;

            $cols  = implode(', ', array_map(fn($c) => "`$c`", array_keys($rows[0])));
            $slots = implode(', ', array_fill(0, count($rows[0]), '?'));
            $stmt  = $pdo->prepare("INSERT INTO `$table` ($cols) VALUES ($slots)");

            foreach ($rows as $row) {
                $stmt->execute(array_values($row));
                $inserted++;
            }
        }

        $pdo->commit();
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");

        log_audit($pdo, $_SESSION['admin_id'], 'BACKUP_IMPORT',
            "Restauration depuis sauvegarde du {$backup['meta']['exported_at']} — $inserted lignes importées");

        echo json_encode([
            'status'  => 'success',
            'message' => "$inserted lignes importées avec succès. Les comptes administrateurs n'ont pas été modifiés."
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
        echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'import : ' . $e->getMessage()]);
    }
    exit;
}

http_response_code(405);
echo json_encode(['status' => 'error', 'message' => 'Requête non reconnue.']);
