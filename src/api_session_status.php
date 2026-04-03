<?php
// src/api_session_status.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'participant' || !isset($_SESSION['session_id'])) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Accès refusé."]);
    exit;
}

$session_id = $_SESSION['session_id'];
$stmt = $pdo->prepare("SELECT statut FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$statut = $stmt->fetchColumn();

echo json_encode([
    "status"         => "success",
    "session_statut" => $statut ?: "unknown"
]);
