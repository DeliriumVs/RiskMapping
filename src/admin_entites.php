<?php
// src/admin_entites.php — Gestion des Entités et Analyses (fragment SPA)
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $_SESSION['admin_role'] !== 'admin') {
    die("<div style='color:red; padding:20px;'>Accès refusé. Privilèges administrateur requis.</div>");
}

$entites = $pdo->query("
    SELECT e.id, e.nom, e.secteur, e.description, e.created_at,
           COUNT(DISTINCT a.id) AS nb_analyses,
           COUNT(DISTINCT s.id) AS nb_scenarios_total
    FROM entites e
    LEFT JOIN analyses a ON a.entite_id = e.id
    LEFT JOIN scenarios_bruts s ON s.analyse_id = a.id
    GROUP BY e.id
    ORDER BY e.nom ASC
")->fetchAll();

$analyses = $pdo->query("
    SELECT a.id, a.entite_id, a.nom, a.perimetre, a.statut, a.date_debut, a.created_at,
           e.nom AS entite_nom,
           COUNT(s.id) AS nb_scenarios
    FROM analyses a
    JOIN entites e ON e.id = a.entite_id
    LEFT JOIN scenarios_bruts s ON s.analyse_id = a.id
    GROUP BY a.id
    ORDER BY e.nom ASC, a.created_at DESC
")->fetchAll();
?>

<div style="padding:20px; background:#161b22; border-radius:8px; border:1px solid #30363d;">
    <h2 style="color:#fff; margin-top:0;">📁 Entités & Analyses</h2>
    <p style="color:#8b949e; margin-bottom:24px;">Gérez vos clients/entités et leurs analyses de risques EBIOS RM.</p>

    <div id="ent-msg" style="display:none; padding:10px; border-radius:6px; margin-bottom:16px;"></div>

    <!-- Entités -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="color:#c9d1d9; margin:0;">Entités / Clients</h3>
        <button onclick="toggleEF('form-new-ent')" style="background:transparent; border:1px dashed #30363d; color:#8b949e; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:0.85rem;">+ Nouvelle entité</button>
    </div>

    <div id="form-new-ent" style="display:none; background:#0d1117; border:1px solid #30363d; border-radius:6px; padding:16px; margin-bottom:16px;">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
            <input type="text" id="ent-nom" placeholder="Nom de l'entité *" style="background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px;">
            <input type="text" id="ent-secteur" placeholder="Secteur d'activité" style="background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px;">
        </div>
        <textarea id="ent-desc" placeholder="Description (optionnel)" style="width:100%; box-sizing:border-box; background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px; resize:vertical; height:60px;"></textarea>
        <div style="display:flex; gap:8px; margin-top:10px;">
            <button onclick="createEntity()" style="background:#3b82f6; border:none; color:#fff; padding:8px 16px; border-radius:4px; cursor:pointer;">Créer</button>
            <button onclick="toggleEF('form-new-ent')" style="background:#30363d; border:none; color:#8b949e; padding:8px 16px; border-radius:4px; cursor:pointer;">Annuler</button>
        </div>
    </div>

    <table style="width:100%; border-collapse:collapse; margin-bottom:30px;">
        <thead>
            <tr style="color:#8b949e; font-size:0.8rem; text-transform:uppercase; border-bottom:1px solid #30363d;">
                <th style="padding:8px; text-align:left;">Entité</th>
                <th style="padding:8px; text-align:left;">Secteur</th>
                <th style="padding:8px; text-align:left;">Analyses</th>
                <th style="padding:8px; text-align:left;">Scénarios</th>
                <th style="padding:8px;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($entites as $e): ?>
            <tr style="border-bottom:1px solid #21262d;">
                <td style="padding:10px; color:#c9d1d9; font-weight:bold;"><?= htmlspecialchars($e['nom']) ?></td>
                <td style="padding:10px; color:#8b949e;"><?= htmlspecialchars($e['secteur'] ?? '—') ?></td>
                <td style="padding:10px; color:#c9d1d9;"><?= $e['nb_analyses'] ?></td>
                <td style="padding:10px; color:#c9d1d9;"><?= $e['nb_scenarios_total'] ?></td>
                <td style="padding:10px; text-align:center;">
                    <button onclick="deleteEntity(<?= $e['id'] ?>, '<?= htmlspecialchars(addslashes($e['nom'])) ?>')"
                            style="background:rgba(218,41,28,0.1); border:1px solid rgba(218,41,28,0.3); color:#da291c; padding:4px 10px; border-radius:4px; cursor:pointer; font-size:0.8rem;">Supprimer</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($entites)): ?>
            <tr><td colspan="5" style="padding:20px; text-align:center; color:#8b949e;">Aucune entité.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Analyses -->
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="color:#c9d1d9; margin:0;">Analyses de risques</h3>
        <button onclick="toggleEF('form-new-ana')" style="background:transparent; border:1px dashed #30363d; color:#8b949e; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:0.85rem;">+ Nouvelle analyse</button>
    </div>

    <div id="form-new-ana" style="display:none; background:#0d1117; border:1px solid #30363d; border-radius:6px; padding:16px; margin-bottom:16px;">
        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:10px;">
            <select id="ana-entite" style="background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px;">
                <option value="">-- Entité *</option>
                <?php foreach ($entites as $e): ?>
                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="ana-nom" placeholder="Nom de l'analyse *" style="background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px;">
            <input type="date" id="ana-date" style="background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px;">
        </div>
        <textarea id="ana-perim" placeholder="Périmètre / Scope" style="width:100%; box-sizing:border-box; background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px; resize:vertical; height:60px;"></textarea>
        <div style="display:flex; gap:8px; margin-top:10px;">
            <button onclick="createAnalyse()" style="background:#3b82f6; border:none; color:#fff; padding:8px 16px; border-radius:4px; cursor:pointer;">Créer</button>
            <button onclick="toggleEF('form-new-ana')" style="background:#30363d; border:none; color:#8b949e; padding:8px 16px; border-radius:4px; cursor:pointer;">Annuler</button>
        </div>
    </div>

    <table style="width:100%; border-collapse:collapse;">
        <thead>
            <tr style="color:#8b949e; font-size:0.8rem; text-transform:uppercase; border-bottom:1px solid #30363d;">
                <th style="padding:8px; text-align:left;">Entité</th>
                <th style="padding:8px; text-align:left;">Analyse</th>
                <th style="padding:8px; text-align:left;">Périmètre</th>
                <th style="padding:8px; text-align:left;">Statut</th>
                <th style="padding:8px; text-align:left;">Scénarios</th>
                <th style="padding:8px;">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($analyses as $a):
            $colors = ['en_cours'=>'#22c55e','finalisee'=>'#3b82f6','archivee'=>'#8b949e'];
            $labels = ['en_cours'=>'En cours','finalisee'=>'Finalisée','archivee'=>'Archivée'];
            $c = $colors[$a['statut']] ?? '#8b949e';
            $l = $labels[$a['statut']] ?? $a['statut'];
        ?>
            <tr style="border-bottom:1px solid #21262d;">
                <td style="padding:10px; color:#8b949e; font-size:0.85rem;"><?= htmlspecialchars($a['entite_nom']) ?></td>
                <td style="padding:10px; color:#c9d1d9; font-weight:bold;"><?= htmlspecialchars($a['nom']) ?></td>
                <td style="padding:10px; color:#8b949e; font-size:0.85rem;"><?= htmlspecialchars($a['perimetre'] ?? '—') ?></td>
                <td style="padding:10px;">
                    <select onchange="updateStatut(<?= $a['id'] ?>, this.value, '<?= htmlspecialchars(addslashes($a['nom'])) ?>')"
                            style="background:#0d1117; border:1px solid <?= $c ?>; color:<?= $c ?>; padding:3px 6px; border-radius:4px; font-size:0.8rem;">
                        <option value="en_cours" <?= $a['statut']==='en_cours'?'selected':'' ?>>En cours</option>
                        <option value="finalisee" <?= $a['statut']==='finalisee'?'selected':'' ?>>Finalisée</option>
                        <option value="archivee" <?= $a['statut']==='archivee'?'selected':'' ?>>Archivée</option>
                    </select>
                </td>
                <td style="padding:10px; color:#c9d1d9;"><?= $a['nb_scenarios'] ?></td>
                <td style="padding:10px; text-align:center; white-space:nowrap;">
                    <button onclick="deleteAnalyse(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['nom'])) ?>')"
                            style="background:rgba(218,41,28,0.1); border:1px solid rgba(218,41,28,0.3); color:#da291c; padding:4px 10px; border-radius:4px; cursor:pointer; font-size:0.8rem;">Supprimer</button>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($analyses)): ?>
            <tr><td colspan="6" style="padding:20px; text-align:center; color:#8b949e;">Aucune analyse.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
(function() {
    function showMsg(text, ok) {
        var el = document.getElementById('ent-msg');
        el.textContent = text;
        el.style.display = 'block';
        el.style.background = ok ? 'rgba(34,197,94,0.15)' : 'rgba(218,41,28,0.15)';
        el.style.color = ok ? '#22c55e' : '#da291c';
        el.style.border = '1px solid ' + (ok ? 'rgba(34,197,94,0.3)' : 'rgba(218,41,28,0.3)');
        setTimeout(() => el.style.display = 'none', 4000);
    }

    function toggleEF(id) {
        var el = document.getElementById(id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
    window.toggleEF = toggleEF;

    window.createEntity = function() {
        var nom = document.getElementById('ent-nom').value.trim();
        if (!nom) { showMsg('Le nom est obligatoire.', false); return; }
        fetch('api_entites.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({nom, secteur: document.getElementById('ent-secteur').value.trim(), description: document.getElementById('ent-desc').value.trim()})
        }).then(r=>r.json()).then(d => {
            showMsg(d.message, d.status==='success');
            if (d.status==='success') setTimeout(()=>location.reload(), 800);
        });
    };

    window.deleteEntity = function(id, nom) {
        if (!confirm('Supprimer « ' + nom + ' » et TOUTES ses analyses et données ?')) return;
        fetch('api_entites.php', {method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})})
            .then(r=>r.json()).then(d => {
                showMsg(d.message, d.status==='success');
                if (d.status==='success') setTimeout(()=>location.reload(), 800);
            });
    };

    window.createAnalyse = function() {
        var entite_id = parseInt(document.getElementById('ana-entite').value);
        var nom = document.getElementById('ana-nom').value.trim();
        if (!entite_id || !nom) { showMsg('Entité et nom obligatoires.', false); return; }
        fetch('api_analyses.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({entite_id, nom, perimetre: document.getElementById('ana-perim').value.trim(), date_debut: document.getElementById('ana-date').value})
        }).then(r=>r.json()).then(d => {
            showMsg(d.message, d.status==='success');
            if (d.status==='success') setTimeout(()=>location.reload(), 800);
        });
    };

    window.updateStatut = function(id, statut, nom) {
        fetch('api_analyses.php', {
            method:'PATCH', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({id, nom, statut})
        }).then(r=>r.json()).then(d => showMsg(d.message, d.status==='success'));
    };

    window.deleteAnalyse = function(id, nom) {
        if (!confirm('Supprimer l\'analyse « ' + nom + ' » et TOUTES ses données ?')) return;
        fetch('api_analyses.php', {method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})})
            .then(r=>r.json()).then(d => {
                showMsg(d.message, d.status==='success');
                if (d.status==='success') setTimeout(()=>location.reload(), 800);
            });
    };
})();
</script>
