<?php
// src/mj_dashboard.php
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("<h1 style='color: red; text-align: center;'>Accès classifié. Vous n'êtes pas le Maître du Jeu.</h1>");
}

$session_id = $_SESSION['session_id'];

// Lancement de la revue
if (isset($_POST['start_discussion'])) {
    $stmt = $pdo->prepare("UPDATE sessions SET statut = 'discussion' WHERE id = ?");
    $stmt->execute([$session_id]);
    header("Location: revue_scenarios.php");
    exit;
}

// Données de la session en cours
$stmt = $pdo->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$session_info = $stmt->fetch();

// Compteurs en temps réel
$count_participants = $pdo->prepare("SELECT COUNT(*) FROM participants WHERE session_id = ?");
$count_participants->execute([$session_id]);
$nb_participants = $count_participants->fetchColumn();

$count_scenarios = $pdo->prepare("SELECT COUNT(*) FROM scenarios_bruts WHERE session_id = ?");
$count_scenarios->execute([$session_id]);
$nb_scenarios = $count_scenarios->fetchColumn();

// Liste des personnes dans la salle d'attente
$list_participants = $pdo->prepare("SELECT pseudo, role FROM participants WHERE session_id = ? ORDER BY id DESC");
$list_participants->execute([$session_id]);
$participants = $list_participants->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Dashboard MJ - Lundi Noir</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-grid { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-box { flex: 1; background-color: var(--bg-color); padding: 20px; border-radius: 8px; border: 1px solid #30363d; text-align: center; }
        .stat-number { font-size: 3rem; font-weight: bold; color: var(--accent-green); margin: 10px 0; }
        .participant-list { text-align: left; background: var(--bg-color); padding: 15px; border-radius: 8px; max-height: 150px; overflow-y: auto; border: 1px solid #30363d; }
        .badge { font-size: 0.8rem; background: #30363d; padding: 2px 6px; border-radius: 4px; margin-left: 10px; color: #fff; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 800px;">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="text-align: left;">
                <h1 style="margin-top: 0;">👁️ Vue globale</h1>
                <p class="subtitle" style="margin-top: -10px;"><?= htmlspecialchars($session_info['nom_session']) ?></p>
            </div>
            <div>
                <a href="registre_risques.php" class="btn" style="background: #30363d; color: #c9d1d9; font-size: 0.9rem; padding: 8px 15px;">🌐 Registre Global EBIOS</a>
            </div>
        </div>
        
        <div style="background-color: #000; border: 2px dashed var(--accent-green); padding: 20px; border-radius: 8px; margin: 10px 0 20px 0; text-align: center;">
            <p style="margin: 0; color: #8b949e; font-size: 1.2rem;">Pour rejoindre l'atelier, entrez le code PIN :</p>
            <div style="font-size: 5rem; font-weight: bold; color: var(--accent-green); letter-spacing: 15px;">
                <?= htmlspecialchars($session_info['code_session']) ?>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-box">
                <div>Opérateurs connectés</div>
                <div class="stat-number" id="nb-participants"><?= $nb_participants ?></div>
            </div>
            <div class="stat-box" style="border-color: var(--accent-red);">
                <div>Scénarios proposés</div>
                <div class="stat-number" id="nb-scenarios" style="color: var(--accent-red);"><?= $nb_scenarios ?></div>
            </div>
        </div>

        <div class="participant-list">
            <h3 style="margin-top: 0; color: var(--text-color);">Dans la salle d'attente :</h3>
            <div id="participant-list">
                <?php if (empty($participants)): ?>
                    <p style="font-style: italic; color: #8b949e;">En attente de connexions...</p>
                <?php else: ?>
                    <ul style="list-style-type: none; padding: 0; margin: 0;">
                        <?php foreach ($participants as $p): ?>
                            <li style="padding: 5px 0; border-bottom: 1px solid #30363d;">
                                🟢 <strong><?= htmlspecialchars($p['pseudo']) ?></strong> <span class="badge"><?= htmlspecialchars($p['role']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <form method="POST" style="margin-top: 30px; text-align: center; display: block;">
            <div id="submit-zone">
                <?php if ($nb_scenarios > 0): ?>
                    <button type="submit" name="start_discussion" class="btn btn-mj" style="font-size: 1.2rem; padding: 15px 30px; width: 100%;">
                        ⚠️ FERMER LES SOUMISSIONS ET LANCER LA REVUE
                    </button>
                <?php else: ?>
                    <button type="button" class="btn" style="background-color: #555; cursor: not-allowed; width: 100%; color: #aaa;" disabled>
                        En attente d'au moins 1 scénario...
                    </button>
                <?php endif; ?>
            </div>
        </form>

        <script>
            function escapeHtml(str) {
                const d = document.createElement('div');
                d.textContent = str;
                return d.innerHTML;
            }

            function updateDashboard(data) {
                document.getElementById('nb-participants').textContent = data.nb_participants;
                document.getElementById('nb-scenarios').textContent   = data.nb_scenarios;

                const listEl = document.getElementById('participant-list');
                if (data.participants.length === 0) {
                    listEl.innerHTML = '<p style="font-style: italic; color: #8b949e;">En attente de connexions...</p>';
                } else {
                    listEl.innerHTML = '<ul style="list-style-type: none; padding: 0; margin: 0;">' +
                        data.participants.map(p =>
                            `<li style="padding: 5px 0; border-bottom: 1px solid #30363d;">
                                🟢 <strong>${escapeHtml(p.pseudo)}</strong>
                                <span class="badge">${escapeHtml(p.role)}</span>
                            </li>`
                        ).join('') + '</ul>';
                }

                const submitZone = document.getElementById('submit-zone');
                if (data.nb_scenarios > 0) {
                    submitZone.innerHTML = `<button type="submit" name="start_discussion" class="btn btn-mj" style="font-size: 1.2rem; padding: 15px 30px; width: 100%;">⚠️ FERMER LES SOUMISSIONS ET LANCER LA REVUE</button>`;
                } else {
                    submitZone.innerHTML = `<button type="button" class="btn" style="background-color: #555; cursor: not-allowed; width: 100%; color: #aaa;" disabled>En attente d'au moins 1 scénario...</button>`;
                }
            }

            setInterval(async () => {
                try {
                    const res  = await fetch('api_mj_stats.php');
                    const data = await res.json();
                    if (data.status === 'success') updateDashboard(data);
                } catch(e) {}
            }, 3000);
        </script>
    </div>
</body>
</html>
