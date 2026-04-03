<?php
// src/view_registre.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') { die("Accès refusé."); }
?>

<style>
    /* ========================================= */
    /* STYLES ÉCRAN (WEB)                        */
    /* ========================================= */
    .heatmap-wrapper { display: flex; flex-direction: column; align-items: center; margin: 40px 0; padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d; }
    .heatmap-core { display: flex; align-items: stretch; }
    .heatmap-y-axis { display: flex; flex-direction: column; justify-content: space-around; padding-right: 15px; text-align: right; color: #8b949e; font-size: 0.85rem; font-weight: bold; }
    
    /* Grille propre, le background sert de couleur de ligne via le gap */
    .heatmap-grid { display: grid; grid-template-columns: repeat(4, 1fr); grid-template-rows: repeat(4, 1fr); width: 600px; height: 400px; gap: 2px; background: #30363d; border: 2px solid #30363d; border-radius: 4px;}
    .heatmap-cell { display: flex; flex-wrap: wrap; align-content: flex-start; gap: 4px; padding: 8px; overflow: hidden; }
    .heatmap-x-axis { display: grid; grid-template-columns: repeat(4, 1fr); width: 600px; padding-top: 15px; text-align: center; color: #8b949e; font-size: 0.85rem; font-weight: bold; margin-left: 90px; }
    
    .bg-critical { background-color: rgba(255, 0, 85, 0.25) !important; }
    .bg-high { background-color: rgba(255, 68, 68, 0.25) !important; }
    .bg-medium { background-color: rgba(255, 165, 0, 0.25) !important; }
    .bg-low { background-color: rgba(0, 255, 204, 0.25) !important; }

    .risk-dot { display: inline-flex; justify-content: center; align-items: center; background: #0d1117; color: #fff; border: 1px solid #8b949e; border-radius: 50%; width: 28px; height: 28px; font-size: 0.75rem; font-weight: bold; cursor: help; box-shadow: 0 2px 4px rgba(0,0,0,0.5); margin: 0; }
    .risk-dot:hover { border-color: #fff; transform: scale(1.1); }

    table { width: 100%; border-collapse: collapse; background: #161b22; color: #fff; font-size: 0.85rem;}
    th, td { padding: 8px; border: 1px solid #30363d; vertical-align: top; }
    th { background: #21262d; color: var(--accent-green); position: sticky; top: 0; }
    tr:hover { background: #2c3238; }
    
    .badge-risk { padding: 4px 8px; border-radius: 4px; font-weight: bold; text-align: center; display: inline-block; min-width: 25px; }
    .risk-critical { background: rgba(255, 0, 85, 0.2); color: #ff0055; border: 1px solid #ff0055; text-transform: uppercase; }
    .risk-high { background: rgba(255, 68, 68, 0.2); color: #ff4444; border: 1px solid #ff4444; }
    .risk-medium { background: rgba(255, 165, 0, 0.2); color: orange; border: 1px solid orange; }
    .risk-low { background: rgba(0, 255, 204, 0.2); color: #00ffcc; border: 1px solid #00ffcc; }
    
    .badge-traitement { background: #30363d; color: #fff; padding: 4px 8px; border-radius: 4px; display: inline-block; }
    .trait-A, .trait-À { border-left: 3px solid gray; }
    .trait-Réduire { border-left: 3px solid var(--accent-green); }
    .trait-Transférer { border-left: 3px solid #3b82f6; }
    .trait-Éviter { border-left: 3px solid var(--accent-red); }
    .trait-Accepter { border-left: 3px solid orange; }

    /* ========================================= */
    /* RÈGLES D'IMPRESSION PDF                   */
    /* ========================================= */
    @media print {
        @page { size: A3 landscape; margin: 15mm; }

        body, .container { background-color: #ffffff !important; }

        /* Nettoyage du conteneur de la Heatmap */
        .heatmap-wrapper { border: none !important; background: transparent !important; margin: 0 auto !important; padding: 0 !important; page-break-inside: avoid; }
        
        /* Agrandissement de la Heatmap et traits noirs simples */
        .heatmap-grid { 
            width: 900px !important; 
            height: 550px !important; 
            border: 2px solid #000 !important; 
            background: #000 !important; 
            gap: 2px !important; /* C'est ce gap qui crée les lignes noires parfaites */
        }
        .heatmap-x-axis { width: 900px !important; margin-left: 90px !important; font-size: 1rem !important; }
        .heatmap-y-axis { font-size: 1rem !important; }
        .heatmap-cell { padding: 15px !important; } /* Aère les points de risque */
        
        /* Forcer les polices de la heatmap en Noir/Gris Foncé */
        .heatmap-wrapper h2 { color: #000 !important; font-size: 1.8rem !important; margin-bottom: 20px !important;}
        .heatmap-y-axis, .heatmap-x-axis { color: #000 !important; }
        .heatmap-y-axis span, .heatmap-x-axis span { color: #333 !important; }
        /* "Gravité" */
        .heatmap-wrapper > div > div:first-child { color: #000 !important; font-size: 1.2rem !important; font-weight: bold !important; }
        /* "Vraisemblance" */
        .heatmap-wrapper > div:last-child { color: #000 !important; font-size: 1.2rem !important; font-weight: bold !important; margin-top: 15px !important;}

        /* Couleurs des quadrants for l'impression */
        .bg-critical { background-color: #ff4d4d !important; }
        .bg-high { background-color: #ff9999 !important; }
        .bg-medium { background-color: #ffcc99 !important; }
        .bg-low { background-color: #99ffcc !important; }
        
        .risk-dot { width: 32px !important; height: 32px !important; font-size: 0.9rem !important; background: #000 !important; color: #fff !important; border: 1px solid #fff !important; font-weight: bold !important; box-shadow: none !important; }
        
        /* FORCER LE SAUT DE PAGE AVANT LE TABLEAU */
        .page-break-before { page-break-before: always !important; break-before: page !important; margin-top: 0 !important; padding-top: 20px !important; }

        table { border-collapse: collapse !important; width: 100% !important; border: 2px solid #000 !important; margin-top: 10px !important; background-color: #ffffff !important; }
        th { background: #e2e8f0 !important; color: #000 !important; border: 1px solid #000 !important; padding: 10px !important;}
        td { border: 1px solid #000 !important; color: #000 !important; background-color: #ffffff !important; padding: 8px !important; page-break-inside: avoid; }
        .badge-risk { border: 1px solid #000 !important; }
        .badge-traitement { background-color: #f1f5f9 !important; color: #000 !important; border: 1px solid #cbd5e1 !important; font-weight: bold; }

        .print-title-dark { color: #000000 !important; }

        /* PACS: fond blanc radical */
        tr.action-row-container, 
        tr.action-row-container td, 
        td.action-content-wrapper,
        div[id^="actions-content-"] { 
            background-color: #ffffff !important; 
            background: #ffffff !important; 
            border: none !important; 
            box-shadow: none !important;
        }

        table.action-table { background-color: #ffffff !important; margin: 10px auto 20px auto !important; border: 2px solid #000 !important; width: 95% !important; }
        table.action-table th { background-color: #f8fafc !important; color: #000 !important; font-size: 0.75rem !important; padding: 6px !important; border: 1px solid #000 !important; }
        table.action-table td { background-color: #ffffff !important; color: #000 !important; font-size: 0.75rem !important; padding: 6px !important; border: 1px solid #000 !important; }
        
        .action-table select { -webkit-appearance: none; -moz-appearance: none; appearance: none; border: none !important; background: transparent !important; color: #000 !important; font-weight: bold; padding: 0 !important; font-size: 0.75rem !important; }
    }
</style>

<div class="print-only" style="border-bottom: 3px solid #3b82f6; padding-bottom: 10px; margin-bottom: 20px;">
    <h1 style="color: #3b82f6 !important; font-size: 24pt; margin: 0;">Rapport d'Analyse des Risques (EBIOS RM)</h1>
    <p style="font-size: 12pt; margin: 5px 0 0 0;"><strong>Généré par :</strong> RiskMapping Suite | <strong>Date :</strong> <?= date('d/m/Y') ?></p>
    <p style="font-size: 10pt; color: #ff4d4d !important; font-weight: bold; text-transform: uppercase;">Mention : Diffusion Restreinte / Confidentiel</p>
</div>

<div id="api-message-registre" style="display: none; padding: 10px; border-radius: 4px; margin-bottom: 20px;"></div>

<div class="heatmap-wrapper" id="heatmap-container" style="display: none;">
    <h2 style="margin-top: 0; color: #c9d1d9;">Cartographie des Risques (Heatmap)</h2>
    <div style="display: flex; align-items: center;">
        <div style="transform: rotate(-90deg); color: #c9d1d9; font-weight: bold; font-size: 1.1rem; margin-right: -40px; margin-left: -50px;">Gravité</div>
        <div>
            <div class="heatmap-core">
                <div class="heatmap-y-axis">
                    <div>4<br><span style="font-weight: normal; font-size: 0.75rem;">Critique</span></div>
                    <div>3<br><span style="font-weight: normal; font-size: 0.75rem;">Grave</span></div>
                    <div>2<br><span style="font-weight: normal; font-size: 0.75rem;">Significative</span></div>
                    <div>1<br><span style="font-weight: normal; font-size: 0.75rem;">Mineure</span></div>
                </div>
                <div class="heatmap-grid" id="heatmap-grid"></div>
            </div>
            <div class="heatmap-x-axis">
                <div>1<br><span style="font-weight: normal; font-size: 0.75rem;">Très faible</span></div><div>2<br><span style="font-weight: normal; font-size: 0.75rem;">Faible</span></div><div>3<br><span style="font-weight: normal; font-size: 0.75rem;">Élevée</span></div><div>4<br><span style="font-weight: normal; font-size: 0.75rem;">Très élevée</span></div>
            </div>
        </div>
    </div>
    <div style="color: #c9d1d9; font-weight: bold; font-size: 1.1rem; margin-top: 15px; margin-left: 80px;">Vraisemblance</div>
</div>

<div class="page-break-before" style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 30px; margin-bottom: 10px;">
    <h3 class="print-title-dark" style="color: var(--accent-green); margin: 0;">Plan d'Action / Risques Traités</h3>
    <div style="display: flex; gap: 10px;">
        <button id="btn-toggle-all" onclick="toggleAllActions()" class="btn no-print" style="background: #3b82f6; border: none; color: #fff; padding: 5px 10px; font-size: 0.8rem; border-radius: 4px; cursor: pointer;">📂 Tout déplier</button>
        <button onclick="resetOrder()" class="btn no-print" style="background: transparent; border: 1px solid #8b949e; color: #8b949e; padding: 5px 10px; font-size: 0.8rem; border-radius: 4px; cursor: pointer;">🔄 Réinitialiser le tri</button>
    </div>
</div>

<div style="overflow-x: auto;">
    <table>
        <thead>
            <tr id="table-head">
            </tr>
        </thead>
        <tbody id="sortable-table">
            <tr><td colspan="10" style="text-align:center; padding:20px; color:#8b949e;">Chargement des données via API...</td></tr>
        </tbody>
    </table>
</div>

<script>
    var apiRegistre = 'api_registre.php';
    var apiActions = 'api_actions.php'; 
    var sortableInstance = null;
    var allExpanded = false; // État pour le bouton "Tout déplier"

    function showMsgReg(text, isError = false) {
        const box = document.getElementById('api-message-registre');
        box.style.display = 'block'; box.textContent = text;
        box.style.backgroundColor = isError ? 'rgba(255, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.1)';
        box.style.color = isError ? '#ff4d4d' : '#3b82f6';
        box.style.border = `1px solid ${isError ? '#ff4d4d' : '#3b82f6'}`;
        setTimeout(() => box.style.display = 'none', 4000);
    }

    function initHeatmapGrid() {
        const grid = document.getElementById('heatmap-grid');
        grid.innerHTML = '';
        for (let i = 4; i >= 1; i--) {
            for (let v = 1; v <= 4; v++) {
                let maxVal = Math.max(i, v);
                let bgClass = maxVal === 4 ? 'bg-critical' : (maxVal === 3 ? 'bg-high' : (maxVal === 2 ? 'bg-medium' : 'bg-low'));
                grid.innerHTML += `<div class="heatmap-cell ${bgClass}" id="cell-${i}-${v}"></div>`;
            }
        }
    }

    function buildQualBadge(s, userRole) {
        const isQualifie = s.statut_qualification === 'qualifie';
        const label  = isQualifie ? '✅ Qualifié' : '⚠️ À qualifier';
        const style  = isQualifie
            ? 'background:rgba(0,230,184,0.1); color:var(--accent-green); border:1px solid var(--accent-green);'
            : 'background:rgba(255,165,0,0.1); color:orange; border:1px solid orange;';
        const base   = `font-size:0.7rem; padding:1px 8px; border-radius:10px; font-family:monospace; margin-top:4px; display:inline-block; ${style}`;
        if (userRole !== 'lecteur') {
            const next = isQualifie ? 'a_qualifier' : 'qualifie';
            return `<span onclick="toggleQual(${s.id}, '${next}')" style="cursor:pointer; ${base}" title="Cliquer pour basculer">${label}</span>`;
        }
        return `<span style="${base}">${label}</span>`;
    }

    async function toggleQual(id, newStatut) {
        try {
            const res  = await fetch(apiRegistre, {
                method: 'PATCH',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id, statut_qualification: newStatut })
            });
            const json = await res.json();
            if (json.status === 'success') loadRegistre();
            else showMsgReg(json.message, true);
        } catch(e) { showMsgReg("Erreur lors de la mise à jour.", true); }
    }

    async function loadRegistre() {
        try {
            const res = await fetch(apiRegistre);
            const json = await res.json();
            if (json.status !== 'success') { showMsgReg(json.message, true); return; }

            const tbody = document.getElementById('sortable-table');
            const thead = document.getElementById('table-head');
            
            let thHtml = `
                <th class="no-print">↕</th>
                <th style="width: 50px; text-align: center;">ID</th>
                <th style="width: 20%;">Scénario Technique</th>
                <th style="width: 22%;">Scénario de Menace (terrain)</th>
                <th>Gravité</th>
                <th>Vraisemblance</th>
                <th>Niveau</th>
                <th>Criticité</th>
                <th style="width: 100px;">Atelier</th>
                <th style="width: 18%;">Traitement & Plan d'Action</th>`;
            if (json.user_role !== 'lecteur') thHtml += `<th class="no-print">Actions</th>`;
            thead.innerHTML = thHtml;
            
            tbody.innerHTML = '';
            initHeatmapGrid();

            if (json.data.length === 0) {
                document.getElementById('heatmap-container').style.display = 'none';
                tbody.innerHTML = '<tr><td colspan="11" style="text-align:center; padding:20px;">Aucun risque enregistré.</td></tr>';
                return;
            }

            document.getElementById('heatmap-container').style.display = 'flex';

            json.data.forEach(s => {
                const imp = Math.min(Math.max(parseInt(s.impact_estime), 1), 4);
                const vrai = Math.min(Math.max(parseInt(s.vraisemblance_estimee), 1), 4);
                
                const cell = document.getElementById(`cell-${imp}-${vrai}`);
                if(cell) {
                    cell.innerHTML += `<span class="risk-dot" title="${s.titre}">${s.visual_id}</span>`;
                }

                const niv = parseInt(s.niveau_ebios);
                const c_risk = niv >= 4 ? 'risk-critical' : (niv >= 3 ? 'risk-high' : (niv >= 2 ? 'risk-medium' : 'risk-low'));
                const prio = parseInt(s.priorite);
                const c_mult = prio >= 12 ? 'risk-high' : (prio >= 6 ? 'risk-medium' : 'risk-low');
                const trait = s.strategie_traitement;
                const c_trait = "trait-" + trait.split(' ')[0];
                const dateC = new Date(s.created_at).toLocaleDateString('fr-FR', {day: '2-digit', month: '2-digit', year: '2-digit'});

                const tr = document.createElement('tr');
                tr.setAttribute('data-id', s.id);
                tr.classList.add('main-row');
                
                const hasTitreTech = s.titre_technique && s.titre_technique.trim() !== '';
                const techniqueTd = hasTitreTech
                    ? `<strong style="color:#a78bfa;">${s.titre_technique}</strong><br>${buildQualBadge(s, json.user_role)}`
                    : buildQualBadge(s, json.user_role);

                let html = `
                    <td class="drag-handle no-print" style="vertical-align: middle;">⣿</td>
                    <td style="text-align: center; vertical-align: middle;"><span class="risk-dot">${s.visual_id}</span></td>
                    <td>${techniqueTd}</td>
                    <td><strong>${s.titre}</strong></td>
                    <td style="text-align: center;"><strong>${imp}</strong></td>
                    <td style="text-align: center;"><strong>${vrai}</strong></td>
                    <td style="text-align: center;"><span class="badge-risk ${c_risk}">${niv}</span></td>
                    <td style="text-align: center;"><span class="badge-risk ${c_mult}">${prio}</span></td>
                    <td style="font-size:0.78rem; color:#c9d1d9;">${s.nom_session}<br><span style="font-size:0.7rem; color:#8b949e;">${dateC}</span></td>
                    <td style="text-align: center;">
                        <span class="badge-traitement ${c_trait}" style="display:block; margin-bottom:5px;">${trait}</span>
                        <button id="btn-toggle-${s.id}" onclick="toggleActions(${s.id}, this)" class="btn no-print" style="font-size: 0.75rem; background: #0d1117; border: 1px solid #3b82f6; color: #3b82f6; width: 100%; cursor: pointer;">📋 Plan d'action 🔽</button>
                    </td>
                `;
                
                if (json.user_role !== 'lecteur') {
                    let btnHtml = `<a href="edit_scenario.php?id=${s.id}&from=master" class="btn" style="padding: 6px; font-size: 0.8rem; background: #484f58; color: #ffffff; border: 1px solid #c9d1d9; display: block; margin-bottom: 8px; text-decoration:none; text-align:center; font-weight:bold; border-radius:4px;">✎ Éditer</a>`;
                    if (json.user_role === 'admin') {
                        btnHtml += `<button onclick="deleteScenario(${s.id})" class="btn" style="padding: 6px; font-size: 0.8rem; background: rgba(255,0,0,0.2); color: #ff4444; border: 1px solid #ff4444; display: block; width:100%; cursor:pointer; border-radius:4px;">🗑️ Suppr.</button>`;
                    }
                    html += `<td class="no-print" style="vertical-align: middle;">${btnHtml}</td>`;
                }

                tr.innerHTML = html;
                tbody.appendChild(tr);

                const trActions = document.createElement('tr');
                trActions.id = `actions-row-${s.id}`;
                trActions.className = 'action-row-container';
                trActions.dataset.technique = s.scenario_technique || '';
                trActions.style.display = 'none';
                trActions.style.backgroundColor = '#0d1117'; 
                trActions.innerHTML = `<td colspan="11" class="action-content-wrapper" style="padding: 15px; border: 1px dashed #3b82f6;"><div id="actions-content-${s.id}">Chargement...</div></td>`;
                tbody.appendChild(trActions);
            });

            applySortable();
            
            // Remise à zéro de l'état "Tout déplier" au chargement
            allExpanded = false;
            document.getElementById('btn-toggle-all').innerHTML = "📂 Tout déplier";

        } catch (error) { showMsgReg("Erreur réseau.", true); }
    }

    // --- NOUVEAU : Fonction "Tout déplier/replier" ---
    async function toggleAllActions() {
        const rows = document.querySelectorAll('.main-row');
        const btnAll = document.getElementById('btn-toggle-all');
        
        allExpanded = !allExpanded;
        btnAll.innerHTML = "⏳ Chargement...";
        btnAll.disabled = true;

        const promises = [];

        for (let row of rows) {
            const id = row.getAttribute('data-id');
            const actionRow = document.getElementById(`actions-row-${id}`);
            const content = document.getElementById(`actions-content-${id}`);
            const toggleBtn = document.getElementById(`btn-toggle-${id}`);
            
            if (allExpanded && actionRow.style.display === 'none') {
                actionRow.style.display = 'table-row';
                if(toggleBtn) toggleBtn.innerHTML = "📋 Fermer 🔼";
                promises.push(loadActions(id, content));
            } else if (!allExpanded && actionRow.style.display !== 'none') {
                actionRow.style.display = 'none';
                if(toggleBtn) toggleBtn.innerHTML = "📋 Plan d'action 🔽";
            }
        }

        if (promises.length > 0) {
            await Promise.all(promises); // Exécute les appels API en parallèle
        }

        btnAll.innerHTML = allExpanded ? "📂 Tout replier" : "📂 Tout déplier";
        btnAll.disabled = false;
    }

    async function toggleActions(scenarioId, btn) {
        const row = document.getElementById(`actions-row-${scenarioId}`);
        const content = document.getElementById(`actions-content-${scenarioId}`);
        
        if (row.style.display === 'none') {
            row.style.display = 'table-row';
            btn.innerHTML = "📋 Fermer 🔼";
            await loadActions(scenarioId, content);
        } else {
            row.style.display = 'none';
            btn.innerHTML = "📋 Plan d'action 🔽";
        }
    }

    async function loadActions(scenarioId, container) {
        try {
            const res = await fetch(`${apiActions}?scenario_id=${scenarioId}`);
            const json = await res.json();

            const technique = document.getElementById(`actions-row-${scenarioId}`)?.dataset.technique || '';
            let html = `<h4 class="no-print print-title-dark" style="color: #3b82f6; margin-top:0; margin-bottom: 10px;">Suivi des actions (PACS)</h4>`;
            if (technique) {
                html += `<div style="background:rgba(167,139,250,0.06); border:1px solid rgba(167,139,250,0.25); border-radius:6px; padding:11px 15px; margin-bottom:14px; font-size:0.875rem; color:#c9d1d9; line-height:1.6;">
                    <strong style="color:#a78bfa; font-size:0.75rem; text-transform:uppercase; letter-spacing:0.04em; display:block; margin-bottom:5px;">🔬 Reformulation technique (équipe sécurité)</strong>
                    ${technique}
                </div>`;
            }
            
            if (json.data.length > 0) {
                html += `<table class="action-table" style="width:100%; background: #161b22; margin-bottom: 15px;">
                            <tr style="color: #8b949e; border-bottom: 1px solid #30363d;">
                                <th style="text-align:left;">Action à réaliser</th>
                                <th>Porteur</th>
                                <th>Échéance</th>
                                <th>Ticket</th>
                                <th>Statut</th>
                                ${json.user_role === 'admin' ? '<th class="no-print">Actions</th>' : ''}
                            </tr>`;
                json.data.forEach(act => {
                    const statusColors = { 'a_faire': '#8b949e', 'en_cours': 'orange', 'fait': '#00ffcc', 'bloque': '#ff4d4d' };
                    const color = statusColors[act.statut];
                    
                    let statusSelect = `<select onchange="updateActionStatus(${act.id}, this.value, ${scenarioId})" style="background:#0d1117; color:${color}; border:1px solid ${color}; padding:2px; border-radius:4px;">
                        <option value="a_faire" ${act.statut === 'a_faire' ? 'selected' : ''}>À faire</option>
                        <option value="en_cours" ${act.statut === 'en_cours' ? 'selected' : ''}>En cours</option>
                        <option value="fait" ${act.statut === 'fait' ? 'selected' : ''}>Terminé</option>
                        <option value="bloque" ${act.statut === 'bloque' ? 'selected' : ''}>Bloqué</option>
                    </select>`;

                    let ticketLink = '-';
                    if (act.lien_ticket) {
                        if (act.lien_ticket.startsWith('http://') || act.lien_ticket.startsWith('https://')) {
                            ticketLink = `<a href="${act.lien_ticket}" target="_blank" style="color:#3b82f6;" title="${act.lien_ticket}">🔗 Lien</a>`;
                        } else {
                            ticketLink = `<span style="color:#c9d1d9;">${act.lien_ticket}</span>`;
                        }
                    }
                    
                    const safeTitre = act.titre.replace(/"/g, '&quot;');
                    
                    html += `<tr>
                        <td><strong>${act.titre}</strong></td>
                        <td style="text-align:center;">${act.responsable || '-'}</td>
                        <td style="text-align:center;">${act.date_cible || '-'}</td>
                        <td style="text-align:center;">${ticketLink}</td>
                        <td style="text-align:center;">${statusSelect}</td>
                        ${json.user_role === 'admin' ? `
                        <td class="no-print" style="text-align:center; white-space:nowrap;">
                            <button onclick="prepareEditAction(this, ${scenarioId})" data-id="${act.id}" data-titre="${safeTitre}" data-resp="${act.responsable || ''}" data-date="${act.date_cible || ''}" data-link="${act.lien_ticket || ''}" style="background:none; border:none; color:#3b82f6; cursor:pointer; margin-right:8px;" title="Modifier l'action">✎</button>
                            <button onclick="deleteAction(${act.id}, ${scenarioId})" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" title="Supprimer l'action">🗑️</button>
                        </td>` : ''}
                    </tr>`;
                });
                html += `</table>`;
            } else {
                html += `<p style="color: #8b949e; font-style: italic; font-size: 0.9rem;">Aucune action définie pour le moment.</p>`;
            }

            if (json.user_role !== 'lecteur') {
                html += `
                <div class="no-print" style="background: #21262d; padding: 10px; border-radius: 4px; display:flex; gap:10px; flex-wrap:wrap;">
                    <input type="text" id="new-act-titre-${scenarioId}" placeholder="Action (ex: Déployer MFA)" style="flex:2; padding:6px; background:#0d1117; color:#fff; border:1px solid #30363d; border-radius:4px;">
                    <input type="text" id="new-act-resp-${scenarioId}" placeholder="Porteur (ex: DSI)" style="flex:1; padding:6px; background:#0d1117; color:#fff; border:1px solid #30363d; border-radius:4px;">
                    <input type="date" id="new-act-date-${scenarioId}" style="flex:1; padding:6px; background:#0d1117; color:#fff; border:1px solid #30363d; border-radius:4px;">
                    <input type="text" id="new-act-ticket-${scenarioId}" placeholder="Lien/Ticket" style="flex:1; padding:6px; background:#0d1117; color:#fff; border:1px solid #30363d; border-radius:4px;">
                    
                    <button id="btn-save-act-${scenarioId}" data-action-id="" onclick="saveAction(${scenarioId})" class="btn btn-mj" style="padding:6px 12px; background:#3b82f6; border:none; color:white; border-radius:4px; transition:0.2s;">+ Ajouter</button>
                    <button id="btn-cancel-act-${scenarioId}" onclick="cancelEditAction(${scenarioId})" class="btn" style="padding:6px 12px; background:#484f58; border:none; color:white; display:none; border-radius:4px;">Annuler</button>
                </div>`;
            }

            container.innerHTML = html;
        } catch (e) { container.innerHTML = "Erreur de chargement du plan d'action."; }
    }

    function prepareEditAction(btn, scenarioId) {
        document.getElementById(`new-act-titre-${scenarioId}`).value = btn.dataset.titre;
        document.getElementById(`new-act-resp-${scenarioId}`).value = btn.dataset.resp;
        document.getElementById(`new-act-date-${scenarioId}`).value = btn.dataset.date;
        document.getElementById(`new-act-ticket-${scenarioId}`).value = btn.dataset.link;
        
        const saveBtn = document.getElementById(`btn-save-act-${scenarioId}`);
        saveBtn.dataset.actionId = btn.dataset.id; 
        saveBtn.innerHTML = "💾 Modifier";
        saveBtn.style.background = "var(--accent-green)";
        saveBtn.style.color = "#000";

        document.getElementById(`btn-cancel-act-${scenarioId}`).style.display = 'inline-block';
    }

    function cancelEditAction(scenarioId) {
        document.getElementById(`new-act-titre-${scenarioId}`).value = '';
        document.getElementById(`new-act-resp-${scenarioId}`).value = '';
        document.getElementById(`new-act-date-${scenarioId}`).value = '';
        document.getElementById(`new-act-ticket-${scenarioId}`).value = '';
        
        const saveBtn = document.getElementById(`btn-save-act-${scenarioId}`);
        saveBtn.dataset.actionId = '';
        saveBtn.innerHTML = "+ Ajouter";
        saveBtn.style.background = "#3b82f6";
        saveBtn.style.color = "#fff";

        document.getElementById(`btn-cancel-act-${scenarioId}`).style.display = 'none';
    }

    async function saveAction(scenarioId) {
        const actionId = document.getElementById(`btn-save-act-${scenarioId}`).dataset.actionId;
        
        const payload = {
            scenario_id: scenarioId,
            action_id: actionId ? parseInt(actionId) : null,
            titre: document.getElementById(`new-act-titre-${scenarioId}`).value,
            responsable: document.getElementById(`new-act-resp-${scenarioId}`).value,
            date_cible: document.getElementById(`new-act-date-${scenarioId}`).value,
            lien_ticket: document.getElementById(`new-act-ticket-${scenarioId}`).value
        };
        if(!payload.titre) { alert("Le titre de l'action est obligatoire."); return; }

        if (actionId) {
            await fetch(apiActions, { method: 'PUT', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
        } else {
            await fetch(apiActions, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(payload) });
        }
        
        loadActions(scenarioId, document.getElementById(`actions-content-${scenarioId}`)); 
    }

    async function updateActionStatus(actionId, newStatus, scenarioId) {
        await fetch(apiActions, { method: 'PATCH', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action_id: actionId, statut: newStatus}) });
        loadActions(scenarioId, document.getElementById(`actions-content-${scenarioId}`));
    }

    async function deleteAction(actionId, scenarioId) {
        if(!confirm("Supprimer cette action ?")) return;
        await fetch(apiActions, { method: 'DELETE', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({action_id: actionId}) });
        loadActions(scenarioId, document.getElementById(`actions-content-${scenarioId}`));
    }

    async function deleteScenario(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer définitivement ce scénario ?')) return;
        try {
            const res = await fetch(apiRegistre, { method: 'DELETE', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({id_scenario: id}) });
            const json = await res.json();
            if(json.status === 'success') { showMsgReg(json.message); loadRegistre(); } else { showMsgReg(json.message, true); }
        } catch(e) { showMsgReg("Erreur lors de la suppression", true); }
    }

    function applySortable() {
        const tbody = document.getElementById('sortable-table');
        if (!tbody) return;

        let savedOrder = localStorage.getItem('riskmapping_order');
        if (savedOrder) {
            let orderArray = savedOrder.split(',');
            orderArray.forEach(id => {
                let row = tbody.querySelector(`tr[data-id="${id}"]`);
                if (row) {
                    tbody.appendChild(row); 
                    let actionRow = document.getElementById(`actions-row-${id}`);
                    if(actionRow) tbody.appendChild(actionRow);
                }
            });
        }

        if (sortableInstance) sortableInstance.destroy(); 
        sortableInstance = Sortable.create(tbody, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'risk-medium',
            draggable: ".main-row", 
            onEnd: function () {
                let newOrder = Array.from(tbody.querySelectorAll('tr.main-row')).map(row => row.getAttribute('data-id'));
                localStorage.setItem('riskmapping_order', newOrder.join(','));
                loadRegistre(); 
            }
        });
    }

    function resetOrder() {
        if (confirm("Voulez-vous réinitialiser le tri par défaut EBIOS ?")) {
            localStorage.removeItem('riskmapping_order');
            loadRegistre();
        }
    }

    loadRegistre();
</script>
