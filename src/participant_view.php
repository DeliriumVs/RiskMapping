<?php
// src/participant_view.php
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'participant' || !isset($_SESSION['participant_id'])) {
    header("Location: index.php");
    exit;
}
$session_id = $_SESSION['session_id'];
$participant_id = $_SESSION['participant_id'];

$stmtSess = $pdo->prepare("SELECT nom_session, statut FROM sessions WHERE id = ?");
$stmtSess->execute([$session_id]);
$session_info = $stmtSess->fetch();
$sess_statut = $session_info['statut'];

$stats = ['nb_scen' => 0, 'nb_critiques' => 0];
if ($sess_statut === 'termine') {
    $stmtStats = $pdo->prepare("
        SELECT COUNT(*) as nb_scen, SUM(CASE WHEN niveau_ebios >= 3 THEN 1 ELSE 0 END) as nb_critiques 
        FROM scenarios_bruts WHERE session_id = ? AND statut = 'traite'
    ");
    $stmtStats->execute([$session_id]);
    $stats = $stmtStats->fetch();
}

$stmt = $pdo->prepare("SELECT id, titre, description, statut, timer_end_at FROM scenarios_bruts WHERE session_id = ? AND statut IN ('discussion', 'vote', 'resultat') LIMIT 1");
$stmt->execute([$session_id]);
$scenario_actif = $stmt->fetch();

$a_deja_vote = false;
$contributions = [];

if ($scenario_actif) {
    if ($scenario_actif['statut'] === 'discussion') {
        $stmtC = $pdo->prepare("SELECT p.id, p.pseudo, p.role FROM contributions c JOIN participants p ON c.participant_id = p.id WHERE c.scenario_id = ?");
        $stmtC->execute([$scenario_actif['id']]);
        $contributions = $stmtC->fetchAll();
    }
    
    if ($scenario_actif['statut'] === 'vote') {
        $stmtVote = $pdo->prepare("SELECT id FROM votes_poker WHERE scenario_id = ? AND participant_id = ?");
        $stmtVote->execute([$scenario_actif['id'], $participant_id]);
        if ($stmtVote->fetch()) $a_deja_vote = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_vote']) && !$a_deja_vote && $scenario_actif['statut'] === 'vote') {
    $impact = (int)$_POST['impact'];
    $vraisemblance = (int)$_POST['vraisemblance'];
    $stmtIn = $pdo->prepare("INSERT INTO votes_poker (scenario_id, participant_id, impact_vote, vraisemblance_vote) VALUES (?, ?, ?, ?)");
    $stmtIn->execute([$scenario_actif['id'], $participant_id, $impact, $vraisemblance]);
    
    header("Location: participant_view.php");
    exit;
}

// Calcul du temps restant
$timer_seconds = 0;
$timer_active = false;
if ($scenario_actif && $scenario_actif['timer_end_at']) {
    $timer_seconds = strtotime($scenario_actif['timer_end_at']) - time();
    $timer_active = true;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Atelier EBIOS - En direct</title>
    <link rel="stylesheet" href="style.css">
    
    <?php if ($sess_statut !== 'termine' && (!$scenario_actif || $scenario_actif['statut'] !== 'vote' || $a_deja_vote)): ?>
        <meta http-equiv="refresh" content="3">
    <?php endif; ?>
    
    <style>
        .vote-btn { width: 100%; padding: 15px; font-size: 1.1rem; margin-bottom: 10px; background: #0d1117; color: #fff; border: 2px solid #30363d; border-radius: 8px; cursor: pointer; display: block; box-sizing: border-box;}
        .vote-btn:hover { border-color: #3b82f6; }
        input[type="radio"] { display: none; }
        input[type="radio"]:checked + label { background: #3b82f6; border-color: #3b82f6; color: white; font-weight: bold; }
        .status-box { text-align: center; padding: 40px; border-radius: 8px; border: 1px solid #30363d; background: #161b22; }
        
        .speaker-box { background: rgba(0, 255, 204, 0.1); border-left: 4px solid #00ffcc; padding: 15px; margin-top: 10px; text-align: left; }
        .me-speaker { border-left-color: #ff0055; background: rgba(255, 0, 85, 0.1); }
        
        .end-stat { font-size: 2.5rem; font-weight: bold; color: var(--accent-green); margin: 10px 0; }
        .end-stat-alert { color: #ffaa00; }
        
        /* Styles du Chrono Participant */
        .timer-display { font-size: 2rem; font-weight: bold; font-family: monospace; text-align: center; color: var(--accent-green); background: #0d1117; padding: 10px; border-radius: 8px; border: 1px solid #30363d; margin-top: 15px; margin-bottom: 5px; }
        @keyframes blink { 50% { opacity: 0; } }
        .blink { animation: blink 1s linear infinite; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 600px;">
        
        <?php if ($sess_statut === 'termine'): ?>
            <div class="status-box" style="border-color: var(--accent-green);">
                <div style="font-size: 4rem; margin-bottom: 10px;">🎯</div>
                <h2 style="color: var(--accent-green); margin-top: 0;">Atelier Terminé !</h2>
                <p style="color: #c9d1d9; font-size: 1.1rem;">La session <strong>"<?= htmlspecialchars($session_info['nom_session']) ?>"</strong> est officiellement clôturée.</p>
                
                <div style="display: flex; gap: 20px; margin: 30px 0;">
                    <div style="flex: 1; background: #0d1117; padding: 15px; border-radius: 8px; border: 1px solid #30363d;">
                        <div style="color: #8b949e; font-size: 0.85rem; text-transform: uppercase;">Situations Analysées</div>
                        <div class="end-stat"><?= $stats['nb_scen'] ?? 0 ?></div>
                    </div>
                    <div style="flex: 1; background: #0d1117; padding: 15px; border-radius: 8px; border: 1px solid #30363d;">
                        <div style="color: #8b949e; font-size: 0.85rem; text-transform: uppercase;">Sujets Prioritaires</div>
                        <div class="end-stat end-stat-alert"><?= $stats['nb_critiques'] ?? 0 ?></div>
                    </div>
                </div>
                
                <div style="text-align: left; background: rgba(59, 130, 246, 0.1); border-left: 4px solid #3b82f6; padding: 15px; border-radius: 0 4px 4px 0; margin-bottom: 20px;">
                    <h4 style="color: #3b82f6; margin-top: 0;">Et maintenant ?</h4>
                    <p style="color: #c9d1d9; font-size: 0.9rem; margin-bottom: 0;">Grâce à votre expertise, l'équipe Sécurité sait exactement sur quoi concentrer ses efforts. Ces sujets prioritaires vont être intégrés au <strong>Plan d'Action Continu</strong> de l'entreprise pour renforcer notre résilience collective.</p>
                </div>
                
                <h3 style="color: #fff;">Merci pour votre participation et votre temps ! 👋</h3>
                
                <a href="logout.php" class="btn btn-part" style="margin-top: 20px; display: inline-block;">Quitter l'application</a>
            </div>

        <?php elseif (!$scenario_actif): ?>
            <div class="status-box">
                <h2 style="color: var(--accent-green);">⏳ Chargement du sujet suivant...</h2>
                <p style="color: #8b949e;">L'animateur prépare la suite de l'atelier.</p>
                <div style="margin-top: 20px; font-size: 2rem;">🔄</div>
            </div>

        <?php elseif ($scenario_actif['statut'] === 'discussion'): ?>
            <div class="status-box" style="border-color: #3b82f6; padding: 20px;">
                <h2 style="color: #3b82f6; margin-top:0;">🎙️ Débat en cours</h2>
                <h3 style="color: #fff;">"<?= htmlspecialchars($scenario_actif['titre']) ?>"</h3>
                <p style="color: #8b949e; font-style: italic; font-size: 0.9rem;">« <?= nl2br(htmlspecialchars($scenario_actif['description'])) ?> »</p>
                
                <?php if ($timer_active): ?>
                    <div id="chrono-display" class="timer-display">--:--</div>
                <?php endif; ?>
                
                <h4 style="margin-top: 20px; margin-bottom: 10px; color: #c9d1d9; text-align: left;">Personnes désignées pour réagir :</h4>
                <?php foreach ($contributions as $c): ?>
                    <?php $is_me = ($c['id'] == $participant_id); ?>
                    <div class="speaker-box <?= $is_me ? 'me-speaker' : '' ?>">
                        <strong style="font-size: 1.2rem; color: <?= $is_me ? '#ff0055' : '#00ffcc' ?>;">
                            ► <?= htmlspecialchars($c['pseudo']) ?> <?= $is_me ? '(C\'est vous !)' : '' ?>
                        </strong>
                        <br>
                        <span style="color: #8b949e; font-size: 0.85rem;"><?= htmlspecialchars($c['role']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($scenario_actif['statut'] === 'vote' && !$a_deja_vote): ?>
            <h1 style="color: #3b82f6; text-align: center;">🃏 C'est à vous de voter</h1>
            <form method="POST" action="participant_view.php">
                <h3 style="color: #c9d1d9;">💥 Gravité (Conséquence)</h3>
                <div style="display: flex; flex-direction: column;">
                    <input type="radio" id="i1" name="impact" value="1" required><label class="vote-btn" for="i1">1 - Mineure</label>
                    <input type="radio" id="i2" name="impact" value="2"><label class="vote-btn" for="i2">2 - Significative</label>
                    <input type="radio" id="i3" name="impact" value="3"><label class="vote-btn" for="i3">3 - Grave</label>
                    <input type="radio" id="i4" name="impact" value="4"><label class="vote-btn" for="i4">4 - Critique</label>
                </div>
                <h3 style="color: #c9d1d9; margin-top: 20px;">🎲 Vraisemblance (Probabilité)</h3>
                <div style="display: flex; flex-direction: column;">
                    <input type="radio" id="v1" name="vraisemblance" value="1" required><label class="vote-btn" for="v1">1 - Très faible</label>
                    <input type="radio" id="v2" name="vraisemblance" value="2"><label class="vote-btn" for="v2">2 - Faible</label>
                    <input type="radio" id="v3" name="vraisemblance" value="3"><label class="vote-btn" for="v3">3 - Élevée</label>
                    <input type="radio" id="v4" name="vraisemblance" value="4"><label class="vote-btn" for="v4">4 - Très élevée</label>
                </div>
                <button type="submit" name="submit_vote" class="btn btn-part" style="width: 100%; margin-top: 20px; font-size: 1.2rem; padding: 15px;">🔒 Verrouiller mon vote secret</button>
            </form>

        <?php elseif ($scenario_actif['statut'] === 'vote' && $a_deja_vote): ?>
            <div class="status-box" style="border-color: var(--accent-green); background: rgba(0, 255, 204, 0.05);">
                <h2 style="color: var(--accent-green);">✅ Vote enregistré</h2>
                <p style="color: #c9d1d9;">En attente des autres participants...</p>
            </div>

        <?php elseif ($scenario_actif['statut'] === 'resultat'): ?>
            <div class="status-box" style="border-color: var(--accent-red);">
                <h2 style="color: var(--accent-red);">📊 Votes clôturés</h2>
                <p style="color: #c9d1d9;">Regardez l'écran de l'animateur pour découvrir les moyennes.</p>
            </div>
        <?php endif; ?>
        
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
            
            if (timeRemaining <= 30 && timeRemaining > 0) {
                display.style.color = "orange";
            } else if (timeRemaining === 0) {
                display.style.color = "var(--accent-red)";
                display.classList.add("blink");
                display.textContent = "🛑 TEMPS ÉCOULÉ !";
                clearInterval(interval);
            }
            timeRemaining--;
        }
        
        updateTimer();
        const interval = setInterval(updateTimer, 1000);
    </script>
    <?php endif; ?>
</body>
</html>
