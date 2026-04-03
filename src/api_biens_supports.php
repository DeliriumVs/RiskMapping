<?php
// src/api_biens_supports.php
header('Content-Type: application/json; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Accès refusé.']);
    exit;
}

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
$method = $_SERVER['REQUEST_METHOD'];

try {

    // ==========================================================
    // GET : Récupérer tous les BS + toutes les VMs (pour les checkboxes)
    // ==========================================================
    if ($method === 'GET') {
        $stmt = $pdo->query("
            SELECT bs.id, bs.nom, bs.type_bien, bs.description,
                   GROUP_CONCAT(vbs.valeur_metier_id ORDER BY vbs.valeur_metier_id ASC) as vm_ids
            FROM biens_supports bs
            LEFT JOIN valeur_bien_support vbs ON bs.id = vbs.bien_support_id
            GROUP BY bs.id
            ORDER BY bs.id ASC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['vm_ids'] = $row['vm_ids']
                ? array_map('intval', explode(',', $row['vm_ids']))
                : [];
        }
        unset($row);

        $vms = $pdo->query("SELECT id, nom, critere_impacte FROM valeurs_metier ORDER BY id ASC")
                   ->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status'         => 'success',
            'data'           => $data,
            'valeurs_metier' => $vms,
            'user_role'      => $admin_role
        ]);
        exit;
    }

    // ==========================================================
    // POST : Ajouter un Bien Support (+ liens VM optionnels)
    // ==========================================================
    if ($method === 'POST') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $nom         = trim($input['nom']         ?? '');
        $type_bien   = trim($input['type_bien']   ?? 'Autre');
        $description = trim($input['description'] ?? '');
        $vm_ids      = $input['vm_ids'] ?? [];

        if (empty($nom)) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Le nom du Bien Support est obligatoire.']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO biens_supports (nom, type_bien, description) VALUES (?, ?, ?)");
        $stmt->execute([$nom, $type_bien, $description]);
        $bs_id = (int)$pdo->lastInsertId();

        if (!empty($vm_ids)) {
            $stmtLink = $pdo->prepare("INSERT IGNORE INTO valeur_bien_support (valeur_metier_id, bien_support_id) VALUES (?, ?)");
            foreach ($vm_ids as $vm_id) {
                $stmtLink->execute([(int)$vm_id, $bs_id]);
            }
        }

        log_audit($pdo, $_SESSION['admin_id'], 'BS_ADDED', "Ajout Bien Support : $nom");

        echo json_encode(['status' => 'success', 'message' => "Bien Support « $nom » ajouté."]);
        exit;
    }

    // ==========================================================
    // PATCH : Mettre à jour les liens VM d'un BS existant
    // ==========================================================
    if ($method === 'PATCH') {
        if ($admin_role === 'lecteur') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Droits insuffisants.']);
            exit;
        }

        $input  = json_decode(file_get_contents('php://input'), true);
        $bs_id  = (int)($input['bs_id']  ?? 0);
        $vm_ids = $input['vm_ids'] ?? [];

        if (!$bs_id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID Bien Support invalide.']);
            exit;
        }

        $pdo->prepare("DELETE FROM valeur_bien_support WHERE bien_support_id = ?")->execute([$bs_id]);

        if (!empty($vm_ids)) {
            $stmtLink = $pdo->prepare("INSERT IGNORE INTO valeur_bien_support (valeur_metier_id, bien_support_id) VALUES (?, ?)");
            foreach ($vm_ids as $vm_id) {
                $stmtLink->execute([(int)$vm_id, $bs_id]);
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Associations VM mises à jour.']);
        exit;
    }

    // ==========================================================
    // DELETE : Supprimer un Bien Support (admin uniquement)
    // ==========================================================
    if ($method === 'DELETE') {
        if ($admin_role !== 'admin') {
            http_response_code(403);
            echo json_encode(['status' => 'error', 'message' => 'Seul un administrateur peut supprimer.']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $id    = (int)($input['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'ID invalide.']);
            exit;
        }

        $nom_del = $pdo->prepare("SELECT nom FROM biens_supports WHERE id = ?");
        $nom_del->execute([$id]);
        $nom_del = $nom_del->fetchColumn() ?: "ID $id";

        $pdo->prepare("DELETE FROM biens_supports WHERE id = ?")->execute([$id]);

        log_audit($pdo, $_SESSION['admin_id'], 'BS_DELETED', "Suppression Bien Support : $nom_del");

        echo json_encode(['status' => 'success', 'message' => "Bien Support « $nom_del » supprimé."]);
        exit;
    }

    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Méthode non autorisée.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Erreur serveur : ' . $e->getMessage()]);
}
?>
