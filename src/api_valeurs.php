<?php
// src/api_valeurs.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

// 1. Vérification de sécurité (L'API doit être protégée)
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') { 
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "Accès refusé."]);
    exit;
}

// On identifie la méthode HTTP utilisée par le JavaScript (GET, POST, DELETE)
$method = $_SERVER['REQUEST_METHOD'];

try {
    // ==========================================================
    // LECTURE (GET) : Récupérer toutes les valeurs
    // ==========================================================
    if ($method === 'GET') {
        $valeurs = $pdo->query("SELECT * FROM valeurs_metier ORDER BY nom ASC")->fetchAll();
        
        // On renvoie la donnée en JSON
        echo json_encode([
            "status" => "success", 
            "data" => $valeurs,
            "user_role" => $admin_role // On renvoie le rôle pour que le JS sache s'il doit afficher la poubelle
        ]);
        exit;
    }

    // ==========================================================
    // CRÉATION (POST) : Ajouter une nouvelle valeur
    // ==========================================================
    elseif ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Droits insuffisants."]);
            exit;
        }

        // En REST, on lit les données JSON envoyées dans le corps de la requête
        $input = json_decode(file_get_contents('php://input'), true);
        
        $nom = trim($input['nom'] ?? '');
        $critere = trim($input['critere'] ?? '');
        $desc = trim($input['description'] ?? '');

        if (empty($nom) || empty($critere)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Le nom et le critère sont obligatoires."]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO valeurs_metier (nom, critere_impacte, description) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $critere, $desc]);
        
        log_audit($pdo, $_SESSION['admin_id'], 'VALUE_ADDED', "Ajout valeur métier : $nom");
        
        echo json_encode(["status" => "success", "message" => "Valeur métier ajoutée."]);
        exit;
    }

    // ==========================================================
    // SUPPRESSION (DELETE) : Supprimer une valeur
    // ==========================================================
    elseif ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(["status" => "error", "message" => "Seul un administrateur peut supprimer."]);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id_del = (int)($input['id'] ?? 0);

        if ($id_del > 0) {
            $stmt_info = $pdo->prepare("SELECT nom FROM valeurs_metier WHERE id = ?");
            $stmt_info->execute([$id_del]);
            $nom_del = $stmt_info->fetchColumn() ?: "ID $id_del";

            $pdo->prepare("DELETE FROM valeurs_metier WHERE id = ?")->execute([$id_del]);
            log_audit($pdo, $_SESSION['admin_id'], 'VALUE_DELETED', "Suppression valeur métier : $nom_del");
            
            echo json_encode(["status" => "success", "message" => "Valeur métier supprimée."]);
        } else {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "ID invalide."]);
        }
        exit;
    }

    // Méthode non gérée
    else {
        http_response_code(405);
        echo json_encode(["status" => "error", "message" => "Méthode non autorisée."]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Erreur serveur : " . $e->getMessage()]);
}
?>
