<?php
// src/revue_scenarios.php
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("Accès refusé.");
}
$session_id = $_SESSION['session_id'];

// Gestion des requêtes AJAX (pour mettre à jour les votes en temps réel)
if (isset($_GET['ajax_votes']) && isset($_GET['scenario_id'])) {
    header('Content-Type: application/json');
    $scen_id = (int)$_GET['scenario_id'];
    $stmtP = $pdo->prepare("
        SELECT p.id, p.pseudo, 
        (SELECT COUNT(*) FROM votes_poker v WHERE v.participant_id = p.id AND v.scenario_id = ?) as a_vote 
        FROM participants p WHERE p.session_id = ?
    ");
    $stmtP->execute([$scen_id, $session_id]);
    echo json_encode($stmtP->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Gestion des actions du formulaire (Timer, Vote, Consolidation)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $scenario_id = (int)$_POST['scenario_id'];

    if (isset($_POST['start_timer'])) {
        $minutes = (int)$_POST['timer_minutes'];
        $pdo->prepare("UPDATE scenarios_bruts SET timer_end_at = DATE_ADD(NOW(), INTERVAL ? MINUTE) WHERE id = ?")->execute([$minutes, $scenario_id]);
        
        if (isset($_POST['notes']) && is_array($_POST['notes'])) {
            $stmtNote = $pdo->prepare("UPDATE contributions SET notes_mj = ? WHERE id = ?");
            foreach ($_POST['notes'] as $contrib_id => $note) {
                $stmtNote->execute([$note, (int)$contrib_id]);
            }
        }
    }
    elseif (isset($_POST['lancer_vote'])) {
        if (isset($_POST['notes']) && is_array($_POST['notes'])) {
            $stmtNote = $pdo->prepare("UPDATE contributions SET notes_mj = ? WHERE id = ?");
            foreach ($_POST['notes'] as $contrib_id => $note) {
                $stmtNote->execute([$note, (int)$contrib_id]);
            }
        }
        $pdo->prepare("UPDATE scenarios_bruts SET statut = 'vote' WHERE id = ?")->execute([$scenario_id]);
    }
    elseif (isset($_POST['cloturer_vote'])) {
        $stmtAvg = $pdo->prepare("SELECT AVG(impact_vote) as avg_i, AVG(vraisemblance_vote) as avg_v FROM votes_poker WHERE scenario_id = ?");
        $stmtAvg->execute([$scenario_id]);
        $avgs = $stmtAvg->fetch();
        
        $impact = $avgs['avg_i'] ? (int)round($avgs['avg_i']) : 1;
        $vraisemblance = $avgs['avg_v'] ? (int)round($avgs['avg_v']) : 1;
        // Lookup EBIOS RM : G=1 (Critique) → 4 (Mineure), V=1 (Très faible) → 4 (Très élevée)
        // Zone : 3=Élevé (rouge), 2=Modéré (orange), 1=Faible (teal)
        $heatmap_zones = [
            '1,1'=>2,'1,2'=>2,'1,3'=>3,'1,4'=>3,
            '2,1'=>1,'2,2'=>2,'2,3'=>3,'2,4'=>3,
            '3,1'=>1,'3,2'=>1,'3,3'=>2,'3,4'=>3,
            '4,1'=>1,'4,2'=>1,'4,3'=>2,'4,4'=>2,
        ];
        $niveau_ebios = $heatmap_zones["$impact,$vraisemblance"] ?? 1;
        $priorite_mult = $impact * $vraisemblance;

        $stmt = $pdo->prepare("UPDATE scenarios_bruts SET statut = 'resultat', impact_estime = ?, vraisemblance_estimee = ?, priorite = ?, niveau_ebios = ? WHERE id = ?");
        $stmt->execute([$impact, $vraisemblance, $priorite_mult, $niveau_ebios, $scenario_id]);
    }
    elseif (isset($_POST['suivant'])) {
        $just_impact = trim($_POST['justification_impact'] ?? '');
        $just_vraisemblance = trim($_POST['justification_vraisemblance'] ?? '');
        $commentaire_global = trim($_POST['commentaire_global'] ?? '');

        $pdo->prepare("UPDATE scenarios_bruts SET statut = 'traite', justification_impact = ?, justification_vraisemblance = ?, commentaire_global = ? WHERE id = ?")
            ->execute([$just_impact, $just_vraisemblance, $commentaire_global, $scenario_id]);
    }
    
    header("Location: revue_scenarios.php");
    exit;
}

// 1. On cherche un scénario en cours de traitement
$stmt = $pdo->prepare("SELECT s.*, p.pseudo as auteur_nom, p.role as auteur_role FROM scenarios_bruts s JOIN participants p ON s.auteur_id = p.id WHERE s.session_id = ? AND s.statut IN ('discussion', 'vote', 'resultat') LIMIT 1");
$stmt->execute([$session_id]);
$scenario_actif = $stmt->fetch();

// 2. S'il n'y en a pas, on en tire un nouveau
if (!$scenario_actif) {
    $stmt = $pdo->prepare("SELECT id, auteur_id FROM scenarios_bruts WHERE session_id = ? AND statut = 'en_attente' ORDER BY id ASC LIMIT 1");
    $stmt->execute([$session_id]);
    $nouveau = $stmt->fetch();
    
    if ($nouveau) {
        $pdo->prepare("UPDATE scenarios_bruts SET statut = 'discussion', timer_end_at = NULL WHERE id = ?")->execute([$nouveau['id']]);
        
        $stmtSess = $pdo->prepare("SELECT max_reacteurs_par_scenario FROM sessions WHERE id = ?");
        $stmtSess->execute([$session_id]);
        $max_r = (int)$stmtSess->fetchColumn();
        
        $stmtPart = $pdo->prepare("SELECT id FROM participants WHERE session_id = :sess AND id != :auteur ORDER BY RAND() LIMIT :limite");
        $stmtPart->bindValue(':sess', $session_id, PDO::PARAM_INT);
        $stmtPart->bindValue(':auteur', $nouveau['auteur_id'], PDO::PARAM_INT);
        $stmtPart->bindValue(':limite', $max_r, PDO::PARAM_INT);
        $stmtPart->execute();
        $reacteurs = $stmtPart->fetchAll();
        
        $stmtContrib = $pdo->prepare("INSERT INTO contributions (scenario_id, participant_id) VALUES (?, ?)");
        foreach ($reacteurs as $r) {
            $stmtContrib->execute([$nouveau['id'], $r['id']]);
        }
        header("Location: revue_scenarios.php");
        exit;
    } else {
        // Tous les scénarios sont traités
        $pdo->prepare("UPDATE sessions SET statut = 'termine' WHERE id = ?")->execute([$session_id]);
        header("Location: registre_risques.php");
        exit;
    }
}

// 3. Récupération des tags (Valeurs Métiers et Menaces) liés à ce scénario actif
$valeurs_associees = [];
$menaces_associees = [];

if ($scenario_actif) {
    $stmtV = $pdo->prepare("SELECT v.nom, v.critere_impacte FROM valeurs_metier v JOIN scenario_valeurs_metier svm ON v.id = svm.valeur_metier_id WHERE svm.scenario_id = ?");
    $stmtV->execute([$scenario_actif['id']]);
    $valeurs_associees = $stmtV->fetchAll();

    $stmtM = $pdo->prepare("SELECT m.type_source, m.motivation FROM menaces m JOIN scenario_menaces sm ON m.id = sm.menace_id WHERE sm.scenario_id = ?");
    $stmtM->execute([$scenario_actif['id']]);
    $menaces_associees = $stmtM->fetchAll();
}

// 4. Récupération des contributions (les personnes tirées au sort)
$stmtContribs = $pdo->prepare("SELECT c.id, p.pseudo, p.role, c.notes_mj FROM contributions c JOIN participants p ON c.participant_id = p.id WHERE c.scenario_id = ?");
$stmtContribs->execute([$scenario_actif['id']]);
$contributions = $stmtContribs->fetchAll();

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM scenarios_bruts WHERE session_id = ? AND statut = 'en_attente'");
$stmtCount->execute([$session_id]);
$restants = $stmtCount->fetchColumn();

// Gestion du compte à rebours
$timer_seconds = 0;
$timer_active = false;
if ($scenario_actif['timer_end_at']) {
    $timer_seconds = strtotime($scenario_actif['timer_end_at']) - time();
    $timer_active = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Revue des Scénarios - RiskMapping</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .scenario-box { background: #161b22; border: 2px solid var(--accent-red); padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .participant-box { background: #0d1117; border-left: 4px solid var(--accent-green); padding: 15px; margin-bottom: 15px; border-radius: 4px; }
        .participant-name { font-size: 1.2rem; font-weight: bold; color: var(--accent-green); }
        .textarea-notes { width: 100%; height: 60px; background: #000; border: 1px solid #30363d; color: #fff; padding: 10px; box-sizing: border-box; margin-top: 10px; font-family: sans-serif; }
        .timer-display { font-size: 2.5rem; font-weight: bold; font-family: monospace; text-align: center; color: var(--accent-green); background: #0d1117; padding: 10px; border-radius: 8px; border: 1px solid #30363d; margin-bottom: 20px; }
        @keyframes blink { 50% { opacity: 0; } }
        .blink { animation: blink 1s linear infinite; }
        
        /* Styles pour les badges EBIOS */
        .tag-badge { display: inline-block; padding: 4px 10px; border-radius: 15px; font-size: 0.85rem; margin-right: 8px; margin-bottom: 8px; border: 1px solid; }
        .tag-valeur { background: rgba(59, 130, 246, 0.1); color: #3b82f6; border-color: #3b82f6; }
        .tag-menace { background: rgba(218, 41, 28, 0.1); color: #da291c; border-color: #da291c; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <h1 style="color: var(--accent-red); margin-top: 0;">📋 Revue Séquentielle</h1>
            <span style="background: #30363d; padding: 5px 15px; border-radius: 20px; font-weight: bold;">En attente : <?= $restants ?></span>
        </div>
        
        <div class="scenario-box">
            <h2 style="margin-top: 0; color: #fff;">"<?= htmlspecialchars($scenario_actif['titre']) ?>"</h2>
            <p style="font-size: 1.2rem; font-style: italic; color: #c9d1d9;">« <?= nl2br(htmlspecialchars($scenario_actif['description'])) ?> »</p>
            <p style="font-size: 0.9rem; color: #8b949e; margin-bottom: 15px;">— Proposé par <?= htmlspecialchars($scenario_actif['auteur_nom']) ?> (<?= htmlspecialchars($scenario_actif['auteur_role']) ?>)</p>
            
            <?php if (!empty($valeurs_associees) || !empty($menaces_associees)): ?>
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px dashed #30363d; display: flex; flex-wrap: wrap;">
                    <?php foreach($valeurs_associees as $v): ?>
                        <span class="tag-badge tag-valeur">💎 <?= htmlspecialchars($v['nom']) ?> (<?= htmlspecialchars($v['critere_impacte']) ?>)</span>
                    <?php endforeach; ?>

                    <?php foreach($menaces_associees as $m): ?>
                        <span class="tag-badge tag-menace">🦹 <?= htmlspecialchars($m['type_source']) ?> (<?= htmlspecialchars($m['motivation']) ?>)</span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <form method="POST" action="revue_scenarios.php" id="mj-form">
            <input type="hidden" name="scenario_id" value="<?= $scenario_actif['id'] ?>">
            
            <?php if ($scenario_actif['statut'] === 'discussion'): ?>
                <div style="display: flex; justify-content: space-between; align-items: flex-end; border-bottom: 1px solid #30363d; padding-bottom: 10px; margin-bottom: 20px;">
                    <h3 style="color: var(--accent-green); margin: 0;">🎙️ Instruction du scénario</h3>
                    
                    <?php if (!$timer_active): ?>
                        <div style="display: flex; gap: 10px;">
                            <span style="color: #8b949e; font-size: 0.9rem; align-self: center;">Timebox :</span>
                            <button type="submit" name="start_timer" value="1" onclick="document.getElementById('timer_minutes').value=3;" class="btn" style="padding: 5px 10px; background: #30363d; color: #fff; font-size: 0.85rem;">⏳ 3 min</button>
                            <button type="submit" name="start_timer" value="1" onclick="document.getElementById('timer_minutes').value=5;" class="btn" style="padding: 5px 10px; background: #30363d; color: #fff; font-size: 0.85rem;">⏳ 5 min</button>
                            <input type="hidden" id="timer_minutes" name="timer_minutes" value="3">
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($timer_active): ?>
                    <div id="chrono-display" class="timer-display">--:--</div>
                <?php endif; ?>

                <?php foreach ($contributions as $c): ?>
                    <div class="participant-box">
                        <div class="participant-name">► <?= htmlspecialchars($c['pseudo']) ?> <span style="font-size: 0.9rem; color: #8b949e; font-weight: normal;">(<?= htmlspecialchars($c['role']) ?>)</span></div>
                        <textarea name="notes[<?= $c['id'] ?>]" class="textarea-notes" placeholder="Notes et éléments de contexte apportés pendant le débat..."><?= htmlspecialchars($c['notes_mj'] ?? '') ?></textarea>
                    </div>
                <?php endforeach; ?>
                <button type="submit" name="lancer_vote" class="btn btn-mj" style="width: 100%; font-size: 1.2rem; padding: 15px; margin-top: 20px; background-color: #3b82f6; border-color: #3b82f6;">
                    📢 Fin de l'instruction : Lancer l'évaluation secrète
                </button>

            <?php elseif ($scenario_actif['statut'] === 'vote'): ?>
                <h3 style="color: #3b82f6;">📊 Évaluation en cours</h3>
                <div id="vote-list" style="background: #21262d; padding: 15px; border-radius: 8px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                    <span style="color: #8b949e;">Chargement des terminaux...</span>
                </div>
                <button type="submit" name="cloturer_vote" id="btn-cloturer" class="btn btn-mj" style="width: 100%; font-size: 1.1rem; padding: 15px; margin-top: 20px; background-color: var(--accent-red); border-color: var(--accent-red);">
                    🛑 Clôturer de force l'évaluation
                </button>

                <script>
                    function updateVotes() {
                        fetch(`revue_scenarios.php?ajax_votes=1&scenario_id=<?= $scenario_actif['id'] ?>`)
                        .then(res => res.json())
                        .then(data => {
                            let html = '';
                            let allVoted = true;
                            let countVotes = 0;
                            data.forEach(p => {
                                let icon = p.a_vote > 0 ? '✅' : '⏳';
                                let color = p.a_vote > 0 ? 'var(--accent-green)' : 'orange';
                                if (p.a_vote == 0) allVoted = false; else countVotes++;
                                html += `<div style="padding: 8px; border: 1px solid #30363d; border-radius: 4px; background: #0d1117;"><span style="color: ${color}">${icon}</span> <strong>${p.pseudo}</strong></div>`;
                            });
                            document.getElementById('vote-list').innerHTML = html;
                            let btn = document.getElementById('btn-cloturer');
                            if (allVoted && data.length > 0) {
                                btn.style.backgroundColor = 'var(--accent-green)'; btn.style.borderColor = 'var(--accent-green)';
                                btn.innerHTML = '✅ Tous les votes sont validés ! Consolider le résultat';
                            } else {
                                btn.innerHTML = `🛑 Clôturer les votes (${countVotes}/${data.length})`;
                            }
                        });
                    }
                    setInterval(updateVotes, 2000); updateVotes();
                </script>

            <?php elseif ($scenario_actif['statut'] === 'resultat'): ?>
                <h3 style="color: var(--accent-green);">📋 Consolidation des cotations</h3>
                
                <div style="display: flex; gap: 20px; margin-bottom: 20px;">
                    <div style="flex: 1; background: #161b22; border: 1px solid #30363d; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="color: #8b949e; text-transform: uppercase; margin-bottom: 10px;">Gravité Moyenne (Consensus)</div>
                        <div style="font-size: 3rem; color: var(--accent-green); font-weight: bold; margin-bottom: 15px;"><?= $scenario_actif['impact_estime'] ?></div>
                        <textarea name="justification_impact" class="textarea-notes" placeholder="Justification des impacts retenus (A renseigner en direct avec le groupe)..." style="text-align: left;"></textarea>
                    </div>
                    
                    <div style="flex: 1; background: #161b22; border: 1px solid #30363d; padding: 20px; border-radius: 8px; text-align: center;">
                        <div style="color: #8b949e; text-transform: uppercase; margin-bottom: 10px;">Vraisemblance Moyenne (Consensus)</div>
                        <div style="font-size: 3rem; color: var(--accent-green); font-weight: bold; margin-bottom: 15px;"><?= $scenario_actif['vraisemblance_estimee'] ?></div>
                        <textarea name="justification_vraisemblance" class="textarea-notes" placeholder="Justification de la probabilité retenue (A renseigner en direct avec le groupe)..." style="text-align: left;"></textarea>
                    </div>
                </div>

                <div style="background: #161b22; border: 1px dashed #30363d; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                    <div style="color: #8b949e; text-transform: uppercase; font-size: 0.8rem; margin-bottom: 8px;">💬 Commentaire général de l'animateur <span style="font-weight: normal; text-transform: none;">(optionnel)</span></div>
                    <textarea name="commentaire_global" class="textarea-notes" placeholder="Note générale sur ce scénario (ex : à l'unanimité, il est convenu de réhausser la vraisemblance d'un point)..." style="text-align: left; height: 80px;"></textarea>
                </div>

                <button type="submit" name="suivant" class="btn btn-mj" style="width: 100%; font-size: 1.2rem; padding: 15px; background-color: var(--accent-green); border-color: var(--accent-green); color: #000;">
                    💾 Enregistrer l'argumentation et passer au scénario suivant
                </button>
            <?php endif; ?>
        </form>
    </div>

    <?php if ($timer_active && $scenario_actif['statut'] === 'discussion'): ?>
    <script>
        let timeRemaining = <?= $timer_seconds ?>;
        const display = document.getElementById('chrono-display');
        function updateTimer() {
            if (timeRemaining < 0) timeRemaining = 0;
            let minutes = parseInt(timeRemaining / 60, 10);
            let seconds = parseInt(timeRemaining % 60, 10);
            minutes = minutes < 10 ? "0" + minutes : minutes;
            seconds = seconds < 10 ? "0" + seconds : seconds;
            display.textContent = "⏳ " + minutes + ":" + seconds;
            if (timeRemaining <= 30 && timeRemaining > 0) { display.style.color = "orange"; } 
            else if (timeRemaining === 0) { display.style.color = "var(--accent-red)"; display.classList.add("blink"); display.textContent = "🛑 TEMPS ÉCOULÉ !"; clearInterval(interval); }
            timeRemaining--;
        }
        updateTimer();
        const interval = setInterval(updateTimer, 1000);
    </script>
    <?php endif; ?>
</body>
</html>
