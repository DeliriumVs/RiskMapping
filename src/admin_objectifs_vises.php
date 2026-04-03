<?php
// src/admin_objectifs_vises.php
session_start();
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $admin_role === 'lecteur') {
    die("<div style='color:red; padding:20px;'>Accès refusé.</div>");
}
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">🎯 Gestion des Objectifs Visés</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Couples SR / OV — ce que chaque Source de Risque cherche à atteindre.</p>

    <div id="api-message-ov" style="display: none; padding: 10px; border-radius: 4px; margin-bottom: 20px;"></div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px; width: 90px;">OV</th>
                <th style="padding: 10px; width: 110px;">Source de Risque</th>
                <th style="padding: 10px;">Description de l'objectif</th>
                <th style="padding: 10px; width: 130px;">Pertinence</th>
                <th style="padding: 10px;">Notes</th>
                <th style="padding: 10px; width: 50px;">Action</th>
            </tr>
        </thead>
        <tbody id="table-body-ov">
            <tr><td colspan="6" style="text-align:center; padding:20px; color:#8b949e;">Chargement des données via API...</td></tr>
        </tbody>
    </table>

    <h4 style="color: #f97316; margin-bottom: 15px;">➕ Ajouter un Objectif Visé</h4>
    <form id="form-add-ov" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Source de Risque *</label>
            <select id="ov-sr" required style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
                <option value="">— Sélectionner —</option>
            </select>
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Pertinence</label>
            <select id="ov-pertinence" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
                <option value="A évaluer">⏳ À évaluer</option>
                <option value="Retenu">✅ Retenu</option>
                <option value="Non retenu">❌ Non retenu</option>
            </select>
        </div>
        <div style="grid-column: 1 / -1;">
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Description de l'objectif *</label>
            <input type="text" id="ov-description" placeholder="Ex: Exfiltration des données clients, Chiffrement ransomware…" required
                style="width:100%; box-sizing:border-box; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div style="grid-column: 1 / -1;">
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Notes (optionnel)</label>
            <input type="text" id="ov-notes" placeholder="Contexte, justification…"
                style="width:100%; box-sizing:border-box; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div style="grid-column: 1 / -1; text-align: right;">
            <button type="submit" class="btn btn-mj" style="padding: 10px 25px; background: #f97316; border: none; color: white;">Ajouter</button>
        </div>
    </form>
</div>

<script>
(function() {
    const API = 'api_objectifs_vises.php';
    const tbody = document.getElementById('table-body-ov');
    const msgBox = document.getElementById('api-message-ov');
    const srSelect = document.getElementById('ov-sr');

    const pertinenceConfig = {
        'A évaluer':  { color: '#8b949e', bg: 'rgba(139,148,158,0.12)', border: '#8b949e', label: '⏳ À évaluer' },
        'Retenu':     { color: '#22c55e', bg: 'rgba(34,197,94,0.12)',   border: '#22c55e', label: '✅ Retenu' },
        'Non retenu': { color: '#ef4444', bg: 'rgba(239,68,68,0.12)',   border: '#ef4444', label: '❌ Non retenu' }
    };

    function showMsg(text, isError = false) {
        msgBox.style.display = 'block';
        msgBox.textContent = text;
        msgBox.style.backgroundColor = isError ? 'rgba(255,68,68,0.2)' : 'rgba(249,115,22,0.1)';
        msgBox.style.color = isError ? '#ff4d4d' : '#f97316';
        msgBox.style.border = `1px solid ${isError ? '#ff4d4d' : '#f97316'}`;
        setTimeout(() => msgBox.style.display = 'none', 4000);
    }

    async function loadOV() {
        try {
            const res  = await fetch(API);
            const json = await res.json();
            if (json.status !== 'success') { showMsg(json.message, true); return; }

            // Populate SR dropdown
            srSelect.innerHTML = '<option value="">— Sélectionner —</option>';
            (json.sources || []).forEach(sr => {
                const srId = 'SR-' + String(sr.id).padStart(3, '0');
                srSelect.innerHTML += `<option value="${sr.id}">${srId} — ${sr.type_source}</option>`;
            });

            tbody.innerHTML = '';
            if (json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding:10px; color:#8b949e;">Aucun Objectif Visé configuré.</td></tr>';
                return;
            }

            json.data.forEach(ov => {
                const ovId  = 'OV-' + String(ov.id).padStart(3, '0');
                const srId  = 'SR-' + String(ov.menace_id).padStart(3, '0');
                const cfg   = pertinenceConfig[ov.pertinence] || pertinenceConfig['A évaluer'];
                let delBtn  = `<span style="color:#8b949e; font-size:0.8rem;" title="Droits admin requis">🔒</span>`;
                if (json.user_role === 'admin') {
                    delBtn = `<button onclick="deleteOV(${ov.id})" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" title="Supprimer">🗑️</button>`;
                }

                const pertinenceSelect = Object.keys(pertinenceConfig).map(p =>
                    `<option value="${p}" ${p === ov.pertinence ? 'selected' : ''}>${pertinenceConfig[p].label}</option>`
                ).join('');

                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #30363d';
                tr.innerHTML = `
                    <td style="padding:10px;">
                        <span style="font-family:monospace; font-size:0.72rem; background:rgba(249,115,22,0.12); color:#f97316; border:1px solid #f97316; padding:2px 7px; border-radius:4px;">${ovId}</span>
                    </td>
                    <td style="padding:10px;">
                        <span style="font-family:monospace; font-size:0.72rem; background:rgba(218,41,28,0.12); color:#da291c; border:1px solid #da291c; padding:2px 7px; border-radius:4px;">${srId}</span>
                        <br><span style="font-size:0.75rem; color:#8b949e;">${ov.sr_nom}</span>
                    </td>
                    <td style="padding:10px; color:#fff; text-align:left;">${ov.description}</td>
                    <td style="padding:10px;">
                        <select onchange="updatePertinence(${ov.id}, this.value)"
                            style="padding:4px 8px; font-size:0.82rem; background:${cfg.bg}; color:${cfg.color}; border:1px solid ${cfg.border}; border-radius:4px; cursor:pointer; width:100%;">
                            ${pertinenceSelect}
                        </select>
                    </td>
                    <td style="padding:10px; color:#8b949e; font-size:0.85rem;">${ov.notes || '—'}</td>
                    <td style="padding:10px;">${delBtn}</td>
                `;
                tbody.appendChild(tr);
            });

            // Re-color selects on render
            tbody.querySelectorAll('select').forEach(sel => applyPertinenceColor(sel));
        } catch(e) { showMsg("Erreur de connexion à l'API.", true); }
    }

    function applyPertinenceColor(sel) {
        const cfg = pertinenceConfig[sel.value] || pertinenceConfig['A évaluer'];
        sel.style.background = cfg.bg;
        sel.style.color = cfg.color;
        sel.style.borderColor = cfg.border;
    }

    window.updatePertinence = async function(id, pertinence) {
        try {
            const res  = await fetch(API, { method: 'PATCH', headers: {'Content-Type':'application/json'}, body: JSON.stringify({id, pertinence}) });
            const json = await res.json();
            if (json.status === 'success') {
                // Re-color the select immediately
                const sel = tbody.querySelector(`select[onchange="updatePertinence(${id}, this.value)"]`);
                if (sel) applyPertinenceColor(sel);
            } else { showMsg(json.message, true); }
        } catch(e) { showMsg("Erreur de mise à jour.", true); }
    };

    document.getElementById('form-add-ov').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = {
            menace_id:   parseInt(document.getElementById('ov-sr').value),
            description: document.getElementById('ov-description').value,
            pertinence:  document.getElementById('ov-pertinence').value,
            notes:       document.getElementById('ov-notes').value
        };
        try {
            const res  = await fetch(API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
            const json = await res.json();
            if (json.status === 'success') { showMsg(json.message); this.reset(); loadOV(); }
            else { showMsg(json.message, true); }
        } catch(e) { showMsg("Erreur lors de l'envoi à l'API.", true); }
    });

    window.deleteOV = async function(id) {
        if (!confirm("Supprimer cet Objectif Visé ?")) return;
        try {
            const res  = await fetch(API, { method: 'DELETE', headers: {'Content-Type':'application/json'}, body: JSON.stringify({id}) });
            const json = await res.json();
            if (json.status === 'success') { showMsg(json.message); loadOV(); }
            else { showMsg(json.message, true); }
        } catch(e) { showMsg("Erreur lors de la suppression.", true); }
    };

    loadOV();
})();
</script>
