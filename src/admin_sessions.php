<?php
// src/admin_sessions.php
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("Accès refusé.");
}

// Magie : Si on clique sur un atelier, on change l'ID de session en cours et on redirige !
if (isset($_GET['load_session'])) {
    $_SESSION['session_id'] = (int)$_GET['load_session'];
    header("Location: consolidation.php");
    exit;
}

// On récupère toutes les sessions
$stmt = $pdo->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM participants WHERE session_id = s.id) as nb_participants,
           (SELECT COUNT(*) FROM scenarios_bruts WHERE session_id = s.id) as nb_scenarios
    FROM sessions s 
    ORDER BY s.created_at DESC
");
$stmt->execute();
$sessions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Historique des Ateliers - EBIOS RM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .container { max-width: 900px !important; }
        table { width: 100%; border-collapse: collapse; background: #161b22; color: #fff; margin-top: 20px;}
        th, td { padding: 12px; border: 1px solid #30363d; text-align: left; }
        th { background: #21262d; color: var(--accent-green); }
        tr:hover { background: #2c3238; }
        .badge-status { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .statut-termine { background: rgba(0, 255, 204, 0.2); color: #00ffcc; border: 1px solid #00ffcc; }
        .statut-actif { background: rgba(255, 165, 0, 0.2); color: orange; border: 1px solid orange; }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <h1 style="color: #3b82f6; margin-bottom: 0;">📂 Historique des Ateliers</h1>
                <p class="subtitle" style="margin-top: 5px;">Gestion des sessions passées et actives</p>
            </div>
            <div>
                <a href="registre_risques.php" class="btn" style="background: #30363d; color: #fff;">Retour au Registre</a>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Nom de l'atelier</th>
                    <th style="text-align: center;">Statut</th>
                    <th style="text-align: center;">Participants</th>
                    <th style="text-align: center;">Scénarios</th>
                    <th style="text-align: center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sessions as $s): ?>
                    <tr>
                        <td style="color: #8b949e;"><?= date('d/m/Y H:i', strtotime($s['created_at'])) ?></td>
                        <td><strong><?= htmlspecialchars($s['nom_session']) ?></strong><br><small style="color: #8b949e;">PIN : <?= htmlspecialchars($s['code_session']) ?></small></td>
                        <td style="text-align: center;">
                            <?php if ($s['statut'] === 'termine'): ?>
                                <span class="badge-status statut-termine">Terminé</span>
                            <?php else: ?>
                                <span class="badge-status statut-actif">En cours</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center; font-weight: bold;"><?= $s['nb_participants'] ?></td>
                        <td style="text-align: center; font-weight: bold;"><?= $s['nb_scenarios'] ?></td>
                        <td style="text-align: center;">
                            <a href="admin_sessions.php?load_session=<?= $s['id'] ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem; background: #3b82f6;">Ouvrir</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
