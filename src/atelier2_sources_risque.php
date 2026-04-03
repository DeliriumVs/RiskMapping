<?php
// src/atelier2_sources_risque.php
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    header("Location: index.php"); exit;
}
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if ($admin_role === 'lecteur') {
    header("Location: registre_risques.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Atelier 2 — Sources de Risque | RiskMapping</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 8px 8px 0 0;
            border: 1px solid;
            border-bottom: none;
        }
        .section-body {
            border: 1px solid;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .section-sr .section-header { background: rgba(218,41,28,0.07);  border-color: rgba(218,41,28,0.3); }
        .section-sr .section-body   { background: #0d1117; border-color: rgba(218,41,28,0.3); }
        .section-ov .section-header { background: rgba(255,165,0,0.07);  border-color: rgba(255,165,0,0.3); }
        .section-ov .section-body   { background: #0d1117; border-color: rgba(255,165,0,0.3); }

        .cbadge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 10px;
            font-size: 0.72rem;
            font-weight: bold;
            margin-right: 4px;
            font-family: monospace;
        }
        .cb-sr { background: rgba(218,41,28,0.12);  color: #da291c; border: 1px solid #da291c; }
        .cb-ov { background: rgba(255,165,0,0.12);  color: orange;  border: 1px solid orange; }

        table.ov-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        table.ov-table th { background: #161b22; color: #8b949e; padding: 9px 10px; border: 1px solid #30363d; text-align: left; font-weight: bold; }
        table.ov-table td { padding: 10px; border: 1px solid #30363d; vertical-align: middle; color: #c9d1d9; }
        table.ov-table tr:hover td { background: #161b22; }

        .pertinence-select {
            border: 1px solid;
            border-radius: 4px;
            padding: 3px 8px;
            font-size: 0.8rem;
            font-weight: bold;
            background: transparent;
            cursor: pointer;
        }

        .form-input {
            width: 100%;
            padding: 9px 11px;
            background: #161b22;
            border: 1px solid #30363d;
            color: #fff;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 0.875rem;
        }
        .form-input:focus { outline: none; border-color: orange; }
        .form-label { display: block; font-size: 0.78rem; color: #8b949e; margin-bottom: 5px; font-weight: bold; text-transform: uppercase; }
        .form-row { display: grid; gap: 10px; margin-bottom: 12px; }

        .btn-add { background: orange; color: #000; border: none; padding: 9px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.875rem; transition: 0.2s; }
        .btn-add:hover { background: #ffb733; }
        .msg-box { display: none; padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 0.875rem; }

        .ebios-context {
            background: rgba(218,41,28,0.05);
            border: 1px solid rgba(218,41,28,0.2);
            border-left: 4px solid #da291c;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 25px;
            font-size: 0.875rem;
            color: #c9d1d9;
            line-height: 1.65;
        }
        .separator {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 10px 0 28px;
            color: #8b949e;
            font-size: 0.8rem;
        }
        .separator::before, .separator::after { content: ''; flex: 1; height: 1px; background: #30363d; }

        .couple-label {
            font-family: monospace;
            font-size: 0.78rem;
            white-space: nowrap;
            color: #8b949e;
        }
        .conseil-box {
            background: rgba(255,165,0,0.05);
            border: 1px dashed rgba(255,165,0,0.3);
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 0.8rem;
            color: #8b949e;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container" style="max-width: 960px; text-align: left;">

        <!-- En-tête -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                    <span style="background: #da291c; color: #fff; font-size: 0.72rem; font-weight: bold; padding: 3px 10px; border-radius: 20px; text-transform: uppercase;">Atelier 2</span>
                    <h1 style="color: #da291c; margin: 0; font-size: 1.6rem;">Sources de Risque</h1>
                </div>
                <p style="color: #8b949e; margin: 0; font-size: 0.875rem;">
                    Définir les <span class="cbadge cb-sr">SR — Sources de Risque</span> et leurs <span class="cbadge cb-ov">OV — Objectifs Visés</span>
                </p>
            </div>
            <a href="choix_atelier.php" style="color: #8b949e; text-decoration: none; font-size: 0.85rem; white-space: nowrap; padding-top: 5px;">← Retour aux ateliers</a>
        </div>

        <!-- Contexte EBIOS RM -->
        <div class="ebios-context">
            <strong style="color: #da291c;">Objectif EBIOS RM :</strong>
            Identifier les <strong>Sources de Risque (SR)</strong> pertinentes — acteurs malveillants ou accidentels susceptibles de menacer l'organisation — et définir pour chacune ses <strong>Objectifs Visés (OV)</strong> : ce que cet acteur cherche à obtenir ou à provoquer.
            L'association SR + OV forme un <strong>couple SR/OV</strong>, unité de base de l'analyse des scénarios stratégiques.
            <br><br>
            💡 <strong>Recommandation ANSSI :</strong> 3 à 6 couples SR/OV suffisent pour une analyse représentative. Priorisez les plus vraisemblables via la colonne <em>Pertinence</em>.
        </div>

        <!-- ======================================================== -->
        <!-- SECTION 1 : SOURCES DE RISQUE (SR)                       -->
        <!-- ======================================================== -->
        <div class="section-sr">
            <div class="section-header">
                <span style="font-size: 1.3rem;">🦹</span>
                <div>
                    <div style="font-weight: bold; color: #da291c; font-size: 1rem;">Sources de Risque <span class="cbadge cb-sr" style="margin-left:6px;">SR</span></div>
                    <div style="font-size: 0.78rem; color: #8b949e; margin-top: 2px;">Acteurs susceptibles de menacer l'organisation (cybercriminels, initiés, concurrents…)</div>
                </div>
            </div>
            <div class="section-body">
                <div id="sr-content">
                    <div style="text-align:center; padding: 30px; color: #8b949e;">⏳ Chargement...</div>
                </div>
            </div>
        </div>

        <!-- Séparateur -->
        <div class="separator">↓ Pour chaque Source de Risque, définissez les Objectifs Visés (couples SR/OV)</div>

        <!-- ======================================================== -->
        <!-- SECTION 2 : COUPLES SR / OV                              -->
        <!-- ======================================================== -->
        <div class="section-ov">
            <div class="section-header">
                <span style="font-size: 1.3rem;">🎯</span>
                <div>
                    <div style="font-weight: bold; color: orange; font-size: 1rem;">Couples SR / OV <span class="cbadge cb-ov" style="margin-left:6px;">OV</span></div>
                    <div style="font-size: 0.78rem; color: #8b949e; margin-top: 2px;">Ce que chaque source de risque cherche à atteindre ou à provoquer</div>
                </div>
            </div>
            <div class="section-body">

                <div class="conseil-box">
                    📊 Exemples d'objectifs visés : <em>Voler des données clients</em> (SR-001), <em>Paralyser la production</em> (SR-001), <em>Exfiltrer des données RH</em> (SR-002)…
                    Qualifiez ensuite chaque couple avec sa <strong>Pertinence</strong> pour filtrer ceux à retenir.
                </div>

                <div id="ov-msg" class="msg-box"></div>

                <!-- Tableau des couples -->
                <div style="overflow-x: auto; margin-bottom: 25px;">
                    <table class="ov-table">
                        <thead>
                            <tr>
                                <th style="width: 120px;">Couple</th>
                                <th style="width: 140px;">Source (SR)</th>
                                <th>Objectif Visé</th>
                                <th style="width: 150px;">Pertinence</th>
                                <th>Notes</th>
                                <th style="width: 60px; text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="ov-table-body">
                            <tr><td colspan="6" style="text-align:center; padding:20px; color:#8b949e;">Chargement...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Formulaire d'ajout -->
                <div style="background: #161b22; border: 1px solid rgba(255,165,0,0.25); border-radius: 6px; padding: 18px;">
                    <h4 style="color: orange; margin: 0 0 15px 0; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">➕ Ajouter un Objectif Visé</h4>

                    <div class="form-row" style="grid-template-columns: 1fr 2fr 1fr;">
                        <div>
                            <label class="form-label">Source de Risque *</label>
                            <select id="ov-sr" class="form-input">
                                <option value="">— Sélectionner —</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Objectif Visé *</label>
                            <input type="text" id="ov-desc" class="form-input" placeholder="Ex : Voler des données clients, Paralyser la production…">
                        </div>
                        <div>
                            <label class="form-label">Pertinence initiale</label>
                            <select id="ov-pertinence" class="form-input">
                                <option value="A évaluer">À évaluer</option>
                                <option value="Retenu">Retenu</option>
                                <option value="Non retenu">Non retenu</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row" style="grid-template-columns: 1fr;">
                        <div>
                            <label class="form-label">Notes (optionnel)</label>
                            <input type="text" id="ov-notes" class="form-input" placeholder="Contexte, justification, source d'information…">
                        </div>
                    </div>

                    <div style="text-align: right; margin-top: 5px;">
                        <button onclick="addOv()" class="btn-add">+ Ajouter le couple SR/OV</button>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /container -->

    <script>
        // --- SECTION 1 : Charger admin_menaces.php via fetch ---
        (function() {
            fetch('admin_menaces.php')
                .then(r => r.text())
                .then(html => {
                    const c = document.getElementById('sr-content');
                    c.innerHTML = html;
                    c.querySelectorAll('script').forEach(s => {
                        const ns = document.createElement('script');
                        Array.from(s.attributes).forEach(a => ns.setAttribute(a.name, a.value));
                        ns.appendChild(document.createTextNode(s.innerHTML));
                        s.parentNode.replaceChild(ns, s);
                    });
                    // Envelopper loadMenaces pour qu'elle rafraîchisse aussi le dropdown OV
                    if (typeof window.loadMenaces === 'function') {
                        const _origLoad = window.loadMenaces;
                        window.loadMenaces = async function() {
                            await _origLoad();
                            await refreshSrDropdown();
                        };
                    }
                })
                .catch(() => {
                    document.getElementById('sr-content').innerHTML =
                        '<div style="color:var(--accent-red);padding:15px;">Erreur de chargement du module Sources de Risque.</div>';
                });
        })();

        // --- SECTION 2 : Gestion native des Couples SR/OV ---
        const apiOv = 'api_objectifs_vises.php';

        async function refreshSrDropdown() {
            try {
                const res  = await fetch(apiOv);
                const json = await res.json();
                if (json.status === 'success') populateSrDropdown(json.sources);
            } catch(e) {}
        }

        const pertinenceConfig = {
            'A évaluer' : { color: '#8b949e', bg: 'rgba(139,148,158,0.12)' },
            'Retenu'    : { color: 'var(--accent-green)', bg: 'rgba(0,230,184,0.1)' },
            'Non retenu': { color: '#ff4d4d', bg: 'rgba(255,77,77,0.1)' }
        };

        function showOvMsg(text, isError = false) {
            const box = document.getElementById('ov-msg');
            box.style.display = 'block';
            box.textContent   = text;
            box.style.backgroundColor = isError ? 'rgba(255,68,68,0.15)' : 'rgba(255,165,0,0.1)';
            box.style.color           = isError ? '#ff4d4d'              : 'orange';
            box.style.border          = `1px solid ${isError ? '#ff4d4d' : 'orange'}`;
            setTimeout(() => box.style.display = 'none', 4000);
        }

        async function loadOv() {
            try {
                const res  = await fetch(apiOv);
                const json = await res.json();
                if (json.status !== 'success') { showOvMsg(json.message, true); return; }

                populateSrDropdown(json.sources);
                renderOvTable(json.data, json.user_role);
            } catch(e) {
                showOvMsg("Erreur de connexion à l'API.", true);
            }
        }

        function populateSrDropdown(sources) {
            const sel = document.getElementById('ov-sr');
            const current = sel.value;
            sel.innerHTML = '<option value="">— Sélectionner —</option>';
            sources.forEach(s => {
                const srId = 'SR-' + String(s.id).padStart(3, '0');
                const opt  = document.createElement('option');
                opt.value       = s.id;
                opt.textContent = `${srId} — ${s.type_source}`;
                sel.appendChild(opt);
            });
            if (current) sel.value = current;
        }

        function renderOvTable(data, userRole) {
            const tbody = document.getElementById('ov-table-body');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:18px;color:#8b949e;">Aucun couple SR/OV défini. Utilisez le formulaire ci-dessous.</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            data.forEach(ov => {
                const ovId    = 'OV-' + String(ov.id).padStart(3, '0');
                const srId    = 'SR-' + String(ov.menace_id).padStart(3, '0');
                const cfg     = pertinenceConfig[ov.pertinence] || pertinenceConfig['A évaluer'];
                const deleteBtn = userRole === 'admin'
                    ? `<button onclick="deleteOv(${ov.id})" style="background:none;border:none;color:#ff4d4d;cursor:pointer;font-size:1rem;" title="Supprimer">🗑️</button>`
                    : `<span style="color:#8b949e;font-size:0.8rem;" title="Droits administrateur requis">🔒</span>`;

                const pertinenceSelect = `
                    <select class="pertinence-select"
                            onchange="updatePertinence(${ov.id}, this)"
                            style="color:${cfg.color}; border-color:${cfg.color}; background:${cfg.bg};">
                        <option value="A évaluer"  ${ov.pertinence === 'A évaluer'  ? 'selected' : ''}>À évaluer</option>
                        <option value="Retenu"     ${ov.pertinence === 'Retenu'     ? 'selected' : ''}>✅ Retenu</option>
                        <option value="Non retenu" ${ov.pertinence === 'Non retenu' ? 'selected' : ''}>❌ Non retenu</option>
                    </select>`;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="couple-label">
                        <span class="cbadge cb-sr">${srId}</span><br>
                        <span style="color:#8b949e; font-size:0.65rem; margin-left:2px;">→</span>
                        <span class="cbadge cb-ov">${ovId}</span>
                    </td>
                    <td style="font-size:0.82rem; color:#c9d1d9;">${ov.sr_nom}</td>
                    <td style="font-weight:bold; color:#fff;">${ov.description}</td>
                    <td>${pertinenceSelect}</td>
                    <td style="font-size:0.82rem; color:#8b949e; font-style:italic;">${ov.notes || '—'}</td>
                    <td style="text-align:center;">${deleteBtn}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        async function updatePertinence(ovId, selectEl) {
            const newVal = selectEl.value;
            const cfg    = pertinenceConfig[newVal] || pertinenceConfig['A évaluer'];
            selectEl.style.color       = cfg.color;
            selectEl.style.borderColor = cfg.color;
            selectEl.style.background  = cfg.bg;
            try {
                await fetch(apiOv, {
                    method: 'PATCH',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id: ovId, pertinence: newVal })
                });
            } catch(e) { showOvMsg('Erreur lors de la mise à jour.', true); }
        }

        async function addOv() {
            const menace_id   = parseInt(document.getElementById('ov-sr').value);
            const description = document.getElementById('ov-desc').value.trim();
            const pertinence  = document.getElementById('ov-pertinence').value;
            const notes       = document.getElementById('ov-notes').value.trim();

            if (!menace_id || !description) {
                showOvMsg('La source de risque et la description sont obligatoires.', true);
                return;
            }

            try {
                const res  = await fetch(apiOv, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ menace_id, description, pertinence, notes })
                });
                const json = await res.json();
                if (json.status === 'success') {
                    document.getElementById('ov-desc').value  = '';
                    document.getElementById('ov-notes').value = '';
                    showOvMsg(json.message);
                    loadOv();
                } else {
                    showOvMsg(json.message, true);
                }
            } catch(e) {
                showOvMsg("Erreur lors de l'envoi.", true);
            }
        }

        async function deleteOv(id) {
            if (!confirm('Supprimer ce couple SR/OV ?')) return;
            try {
                const res  = await fetch(apiOv, {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({ id })
                });
                const json = await res.json();
                if (json.status === 'success') { showOvMsg(json.message); loadOv(); }
                else showOvMsg(json.message, true);
            } catch(e) {
                showOvMsg('Erreur lors de la suppression.', true);
            }
        }

        loadOv();
    </script>
</body>
</html>
