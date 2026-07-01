<?php
// src/select_analyse.php — Sélecteur de contexte (entité › analyse)
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    header('Location: admin_login.php');
    exit;
}

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
$admin_username = $_SESSION['admin_username'] ?? '';

// Récupération de toutes les entités avec leurs analyses
$entites = $pdo->query("
    SELECT e.id, e.nom, e.secteur, e.description
    FROM entites e
    ORDER BY e.nom ASC
")->fetchAll();

$analyses = $pdo->query("
    SELECT a.id, a.entite_id, a.nom, a.perimetre, a.statut, a.date_debut,
           COUNT(s.id) AS nb_scenarios
    FROM analyses a
    LEFT JOIN scenarios_bruts s ON s.analyse_id = a.id
    GROUP BY a.id
    ORDER BY a.entite_id ASC, a.created_at DESC
")->fetchAll();

// Indexer les analyses par entite_id pour le JS
$analyses_by_entite = [];
foreach ($analyses as $a) {
    $analyses_by_entite[$a['entite_id']][] = $a;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RiskMapping — Sélection de l'analyse</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body { min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 40px 20px; }
        .selector-wrap { width: 100%; max-width: 1100px; }
        .selector-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .selector-title { color: #3b82f6; font-size: 1.5rem; font-weight: bold; }
        .selector-subtitle { color: #8b949e; font-size: 0.9rem; margin-top: 4px; }

        .two-col { display: grid; grid-template-columns: 300px 1fr; gap: 24px; }

        /* Colonne entités */
        .entities-panel { background: #161b22; border: 1px solid #30363d; border-radius: 8px; overflow: hidden; }
        .panel-header { padding: 14px 16px; font-size: 0.8rem; color: #8b949e; text-transform: uppercase; font-weight: bold; background: #0d1117; border-bottom: 1px solid #30363d; display: flex; justify-content: space-between; align-items: center; }
        .entity-item { padding: 14px 16px; cursor: pointer; border-bottom: 1px solid #21262d; transition: background 0.15s; }
        .entity-item:last-child { border-bottom: none; }
        .entity-item:hover { background: #1c2128; }
        .entity-item.active { background: rgba(59,130,246,0.12); border-left: 3px solid #3b82f6; }
        .entity-name { color: #c9d1d9; font-weight: bold; font-size: 0.95rem; }
        .entity-meta { color: #8b949e; font-size: 0.8rem; margin-top: 2px; }
        .entity-badge { background: #21262d; color: #8b949e; font-size: 0.75rem; padding: 1px 7px; border-radius: 10px; margin-left: 6px; }

        /* Colonne analyses */
        .analyses-panel { background: #161b22; border: 1px solid #30363d; border-radius: 8px; overflow: hidden; }
        .analyse-card { padding: 16px; border-bottom: 1px solid #21262d; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
        .analyse-card:last-child { border-bottom: none; }
        .analyse-info { flex: 1; }
        .analyse-nom { color: #c9d1d9; font-weight: bold; font-size: 1rem; }
        .analyse-perim { color: #8b949e; font-size: 0.85rem; margin-top: 3px; }
        .analyse-stats { display: flex; gap: 12px; margin-top: 6px; }
        .analyse-stat { font-size: 0.8rem; color: #8b949e; }
        .analyse-actions { display: flex; gap: 8px; align-items: center; flex-shrink: 0; }

        .badge-statut { padding: 3px 10px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .badge-en_cours  { background: rgba(34,197,94,0.15);  color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
        .badge-finalisee { background: rgba(59,130,246,0.15); color: #3b82f6; border: 1px solid rgba(59,130,246,0.3); }
        .badge-archivee  { background: rgba(139,148,158,0.15);color: #8b949e; border: 1px solid rgba(139,148,158,0.3); }

        .btn-select { background: #3b82f6; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: bold; white-space: nowrap; transition: background 0.15s; }
        .btn-select:hover { background: #2563eb; }
        .btn-danger-sm { background: rgba(218,41,28,0.1); color: #da291c; border: 1px solid rgba(218,41,28,0.3); padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; transition: 0.15s; }
        .btn-danger-sm:hover { background: #da291c; color: #fff; }

        .empty-state { padding: 40px; text-align: center; color: #8b949e; }

        /* Formulaires inline */
        .inline-form { background: #0d1117; border-top: 1px solid #30363d; padding: 16px; display: none; }
        .inline-form.open { display: block; }
        .form-row { display: flex; gap: 10px; flex-wrap: wrap; }
        .form-row input, .form-row select, .form-row textarea {
            background: #161b22; border: 1px solid #30363d; color: #c9d1d9;
            padding: 8px 10px; border-radius: 6px; font-size: 0.9rem; flex: 1; min-width: 140px;
        }
        .form-row textarea { min-width: 100%; resize: vertical; height: 60px; }
        .btn-sm { padding: 8px 14px; border-radius: 6px; border: none; cursor: pointer; font-size: 0.9rem; }
        .btn-sm-primary { background: #3b82f6; color: #fff; }
        .btn-sm-cancel { background: #30363d; color: #8b949e; }
        .msg { padding: 8px 12px; border-radius: 6px; margin-top: 10px; font-size: 0.85rem; display: none; }
        .msg-ok { background: rgba(34,197,94,0.15); color: #22c55e; border: 1px solid rgba(34,197,94,0.3); }
        .msg-err { background: rgba(218,41,28,0.15); color: #da291c; border: 1px solid rgba(218,41,28,0.3); }

        .btn-add-sm { background: transparent; border: 1px dashed #30363d; color: #8b949e; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; transition: 0.15s; }
        .btn-add-sm:hover { border-color: #3b82f6; color: #3b82f6; }
    </style>
</head>
<body>
<div class="selector-wrap">

    <div class="selector-header">
        <div>
            <div class="selector-title">🧭 RiskMapping</div>
            <div class="selector-subtitle">Sélectionnez ou créez une analyse pour commencer</div>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <span style="color:#8b949e; font-size:0.9rem;">Connecté : <strong style="color:#c9d1d9;"><?= htmlspecialchars($admin_username) ?></strong>
            <span style="background:#30363d; color:#fff; padding:2px 8px; border-radius:10px; font-size:0.75rem; text-transform:uppercase; margin-left:6px;"><?= htmlspecialchars($admin_role) ?></span></span>
            <a href="logout.php" style="color:#8b949e; font-size:0.85rem; text-decoration:none; border:1px solid #30363d; padding:6px 12px; border-radius:6px;">Déconnexion</a>
        </div>
    </div>

    <div class="two-col">

        <!-- Entités -->
        <div>
            <div class="entities-panel">
                <div class="panel-header">
                    <span>📁 Entités / Clients</span>
                    <?php if ($admin_role !== 'lecteur'): ?>
                    <button class="btn-add-sm" onclick="toggleForm('form-new-entity')">+ Nouvelle</button>
                    <?php endif; ?>
                </div>

                <div class="inline-form" id="form-new-entity">
                    <div class="form-row" style="flex-direction:column; gap:8px;">
                        <input type="text" id="new-entity-nom" placeholder="Nom de l'entité *" />
                        <input type="text" id="new-entity-secteur" placeholder="Secteur d'activité" />
                        <textarea id="new-entity-desc" placeholder="Description (optionnel)"></textarea>
                    </div>
                    <div style="display:flex; gap:8px; margin-top:10px;">
                        <button class="btn-sm btn-sm-primary" onclick="createEntity()">Créer</button>
                        <button class="btn-sm btn-sm-cancel" onclick="toggleForm('form-new-entity')">Annuler</button>
                    </div>
                    <div class="msg" id="msg-entity"></div>
                </div>

                <div id="entities-list">
                <?php if (empty($entites)): ?>
                    <div class="empty-state">Aucune entité.<br>Créez-en une pour commencer.</div>
                <?php else: ?>
                    <?php foreach ($entites as $i => $e): ?>
                    <div class="entity-item <?= $i === 0 ? 'active' : '' ?>"
                         onclick="selectEntity(<?= $e['id'] ?>, this)"
                         id="entity-<?= $e['id'] ?>">
                        <div class="entity-name">
                            <?= htmlspecialchars($e['nom']) ?>
                            <span class="entity-badge" id="badge-<?= $e['id'] ?>">
                                <?= count($analyses_by_entite[$e['id']] ?? []) ?>
                            </span>
                        </div>
                        <?php if ($e['secteur']): ?>
                        <div class="entity-meta"><?= htmlspecialchars($e['secteur']) ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Analyses -->
        <div>
            <div class="analyses-panel">
                <div class="panel-header">
                    <span id="analyses-title">Analyses</span>
                    <?php if ($admin_role !== 'lecteur'): ?>
                    <button class="btn-add-sm" id="btn-new-analyse" onclick="toggleForm('form-new-analyse')" style="display:none;">+ Nouvelle analyse</button>
                    <?php endif; ?>
                </div>

                <div class="inline-form" id="form-new-analyse">
                    <div class="form-row" style="flex-direction:column; gap:8px;">
                        <input type="text" id="new-analyse-nom" placeholder="Nom de l'analyse *" />
                        <textarea id="new-analyse-perim" placeholder="Périmètre / Scope (optionnel)"></textarea>
                        <input type="date" id="new-analyse-date" />
                    </div>
                    <div style="display:flex; gap:8px; margin-top:10px;">
                        <button class="btn-sm btn-sm-primary" onclick="createAnalyse()">Créer</button>
                        <button class="btn-sm btn-sm-cancel" onclick="toggleForm('form-new-analyse')">Annuler</button>
                    </div>
                    <div class="msg" id="msg-analyse"></div>
                </div>

                <div id="analyses-list">
                    <div class="empty-state">Sélectionnez une entité à gauche.</div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
var analysesData = <?= json_encode($analyses_by_entite, JSON_UNESCAPED_UNICODE) ?>;
var currentEntiteId = null;

<?php if (!empty($entites)): ?>
selectEntity(<?= $entites[0]['id'] ?>, document.getElementById('entity-<?= $entites[0]['id'] ?>'));
<?php endif; ?>

function selectEntity(id, el) {
    document.querySelectorAll('.entity-item').forEach(e => e.classList.remove('active'));
    if (el) el.classList.add('active');
    currentEntiteId = id;

    var nom = el ? el.querySelector('.entity-name').textContent.trim() : '';
    document.getElementById('analyses-title').textContent = '📊 Analyses — ' + nom.replace(/\d+$/, '').trim();

    var btnNew = document.getElementById('btn-new-analyse');
    if (btnNew) btnNew.style.display = 'inline-block';

    renderAnalyses(id);
}

function renderAnalyses(entiteId) {
    var list = document.getElementById('analyses-list');
    var data = analysesData[entiteId] || [];

    if (data.length === 0) {
        list.innerHTML = '<div class="empty-state">Aucune analyse pour cette entité.<br>Créez-en une pour commencer.</div>';
        return;
    }

    var html = '';
    data.forEach(function(a) {
        var badgeClass = 'badge-' + a.statut;
        var badgeLabel = { en_cours: 'En cours', finalisee: 'Finalisée', archivee: 'Archivée' }[a.statut] || a.statut;
        var dateStr = a.date_debut ? ' — Début : ' + a.date_debut : '';
        html += '<div class="analyse-card">' +
            '<div class="analyse-info">' +
                '<div class="analyse-nom">' + escHtml(a.nom) + '</div>' +
                (a.perimetre ? '<div class="analyse-perim">' + escHtml(a.perimetre) + '</div>' : '') +
                '<div class="analyse-stats">' +
                    '<span class="analyse-stat">🎯 ' + a.nb_scenarios + ' scénario' + (a.nb_scenarios != 1 ? 's' : '') + '</span>' +
                    (dateStr ? '<span class="analyse-stat">' + escHtml(dateStr) + '</span>' : '') +
                '</div>' +
            '</div>' +
            '<div class="analyse-actions">' +
                '<span class="badge-statut ' + badgeClass + '">' + badgeLabel + '</span>' +
                '<button class="btn-select" onclick="selectAnalyse(' + a.id + ')">Sélectionner →</button>' +
                <?= $admin_role === 'admin' ? "'<button class=\"btn-danger-sm\" onclick=\"deleteAnalyse(' + a.id + ', \\'\" + escHtml(a.nom) + \"\\')\" title=\"Supprimer\">🗑</button>'" : "''" ?> +
            '</div>' +
        '</div>';
    });
    list.innerHTML = html;
}

function selectAnalyse(id) {
    fetch('api_select_analyse.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({analyse_id: id})
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') window.location.href = d.redirect;
        else alert(d.message);
    });
}

function createEntity() {
    var nom = document.getElementById('new-entity-nom').value.trim();
    if (!nom) { showMsg('msg-entity', 'Le nom est obligatoire.', false); return; }

    fetch('api_entites.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            nom: nom,
            secteur: document.getElementById('new-entity-secteur').value.trim(),
            description: document.getElementById('new-entity-desc').value.trim()
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            showMsg('msg-entity', d.message, true);
            setTimeout(() => location.reload(), 800);
        } else {
            showMsg('msg-entity', d.message, false);
        }
    });
}

function createAnalyse() {
    if (!currentEntiteId) { showMsg('msg-analyse', 'Sélectionnez une entité.', false); return; }
    var nom = document.getElementById('new-analyse-nom').value.trim();
    if (!nom) { showMsg('msg-analyse', 'Le nom est obligatoire.', false); return; }

    fetch('api_analyses.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            entite_id: currentEntiteId,
            nom: nom,
            perimetre: document.getElementById('new-analyse-perim').value.trim(),
            date_debut: document.getElementById('new-analyse-date').value
        })
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') {
            showMsg('msg-analyse', d.message, true);
            setTimeout(() => location.reload(), 800);
        } else {
            showMsg('msg-analyse', d.message, false);
        }
    });
}

function deleteAnalyse(id, nom) {
    if (!confirm('Supprimer l\'analyse « ' + nom + ' » et TOUTES ses données ?')) return;
    fetch('api_analyses.php', {
        method: 'DELETE',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    })
    .then(r => r.json())
    .then(d => {
        if (d.status === 'success') location.reload();
        else alert(d.message);
    });
}

function toggleForm(id) {
    var f = document.getElementById(id);
    f.classList.toggle('open');
}

function showMsg(id, text, ok) {
    var el = document.getElementById(id);
    el.textContent = text;
    el.className = 'msg ' + (ok ? 'msg-ok' : 'msg-err');
    el.style.display = 'block';
}

function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
