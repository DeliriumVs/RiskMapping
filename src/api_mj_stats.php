<?php
// src/api_mj_stats.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Accès refusé."]);
    exit;
}

$session_id = $_SESSION['session_id'];

$stmt_p = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE session_id = ?");
$stmt_p->execute([$session_id]);
$nb_participants = (int)$stmt_p->fetchColumn();

$stmt_s = $pdo->prepare("SELECT COUNT(*) FROM scenarios_bruts WHERE session_id = ?");
$stmt_s->execute([$session_id]);
$nb_scenarios = (int)$stmt_s->fetchColumn();

$stmt_list = $pdo->prepare("SELECT pseudo, role FROM participants WHERE session_id = ? ORDER BY id DESC");
$stmt_list->execute([$session_id]);
$participants = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

$stmt_sess = $pdo->prepare("SELECT statut FROM sessions WHERE id = ?");
$stmt_sess->execute([$session_id]);
$session_statut = $stmt_sess->fetchColumn();

echo json_encode([
    "status"          => "success",
    "nb_participants" => $nb_participants,
    "nb_scenarios"    => $nb_scenarios,
    "participants"    => $participants,
    "session_statut"  => $session_statut ?: "unknown"
]);
