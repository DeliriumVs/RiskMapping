<?php
// src/admin_menaces.php
session_start();
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $admin_role === 'lecteur') { 
    die("<div style='color:red; padding:20px;'>Accès refusé.</div>"); 
}
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">🦹 Gestion des Sources de Menaces</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Identifiez les attaquants potentiels.</p>

    <div id="api-message-menaces" style="display: none; padding: 10px; border-radius: 4px; margin-bottom: 20px;"></div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px;">Type de Source</th>
                <th style="padding: 10px;">Motivation</th>
                <th style="padding: 10px;">Capacité / Ressources</th>
                <th style="padding: 10px; text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody id="table-body-menaces">
            <tr><td colspan="4" style="text-align:center; padding:20px; color:#8b949e;">Chargement des données via API...</td></tr>
        </tbody>
    </table>

    <h4 style="color: #3b82f6; margin-bottom: 15px;">➕ Ajouter une Source de Menace</h4>
    <form id="form-add-menace" style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 10px; align-items: end;">
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Source</label>
            <input type="text" id="input-source" placeholder="Ex: Cybercriminel" required style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Motivation</label>
            <input type="text" id="input-motivation" placeholder="Ex: Gain financier" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Niveau Capacité</label>
            <select id="input-capacite" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
                <option>Individuelle (Novice)</option>
                <option>Standard (Criminel)</option>
                <option>Élevée (Groupe structuré)</option>
                <option>Très Élevée (Étatique)</option>
            </select>
        </div>
        <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: #3b82f6; border: none; color: white;">Ajouter</button>
    </form>
</div>

<script>
    var apiEndpointMenaces = 'api_menaces.php';
    var tableBodyMenaces = document.getElementById('table-body-menaces');
    var msgBoxMenaces = document.getElementById('api-message-menaces');

    function showMessageMenaces(text, isError = false) {
        msgBoxMenaces.style.display = 'block';
        msgBoxMenaces.textContent = text;
        msgBoxMenaces.style.backgroundColor = isError ? 'rgba(255, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.1)';
        msgBoxMenaces.style.color = isError ? '#ff4d4d' : '#3b82f6';
        msgBoxMenaces.style.border = `1px solid ${isError ? '#ff4d4d' : '#3b82f6'}`;
        setTimeout(() => msgBoxMenaces.style.display = 'none', 4000);
    }

    async function loadMenaces() {
        try {
            const response = await fetch(apiEndpointMenaces);
            const json = await response.json();

            if (json.status !== 'success') { showMessageMenaces(json.message, true); return; }

            tableBodyMenaces.innerHTML = '';
            if (json.data.length === 0) {
                tableBodyMenaces.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:10px; color:#8b949e;">Aucune menace configurée.</td></tr>';
                return;
            }

            json.data.forEach(menace => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #30363d';
                let deleteBtnHTML = `<span style="color: #8b949e; font-size: 0.8rem;" title="Droits administrateur requis">🔒</span>`;
                if (json.user_role === 'admin') {
                    deleteBtnHTML = `<button onclick="deleteMenace(${menace.id})" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" title="Supprimer">🗑️</button>`;
                }
                const srId = 'SR-' + String(menace.id).padStart(3, '0');
                tr.innerHTML = `
                    <td style="padding: 10px; color: #fff; font-weight: bold;">
                        <span style="font-family: monospace; font-size: 0.72rem; background: rgba(218,41,28,0.12); color: #da291c; border: 1px solid #da291c; padding: 2px 7px; border-radius: 4px; margin-right: 8px;">${srId}</span>${menace.type_source}
                    </td>
                    <td style="padding: 10px; color: #c9d1d9;">${menace.motivation || ''}</td>
                    <td style="padding: 10px;"><span style="background: rgba(255,165,0,0.1); color: orange; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;">${menace.niveau_capacite}</span></td>
                    <td style="padding: 10px; text-align: right;">${deleteBtnHTML}</td>
                `;
                tableBodyMenaces.appendChild(tr);
            });
        } catch (error) { showMessageMenaces("Erreur de connexion à l'API.", true); }
    }

    document.getElementById('form-add-menace').addEventListener('submit', async function(e) {
        e.preventDefault(); 
        const data = {
            type_source: document.getElementById('input-source').value,
            motivation: document.getElementById('input-motivation').value,
            niveau_capacite: document.getElementById('input-capacite').value
        };
        try {
            const response = await fetch(apiEndpointMenaces, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const json = await response.json();
            if (json.status === 'success') {
                showMessageMenaces(json.message); document.getElementById('form-add-menace').reset(); loadMenaces(); 
            } else { showMessageMenaces(json.message, true); }
        } catch (error) { showMessageMenaces("Erreur lors de l'envoi à l'API.", true); }
    });

    async function deleteMenace(id) {
        if (!confirm("Voulez-vous vraiment supprimer cette source de menace ?")) return;
        try {
            const response = await fetch(apiEndpointMenaces, { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
            const json = await response.json();
            if (json.status === 'success') { showMessageMenaces(json.message); loadMenaces(); } 
            else { showMessageMenaces(json.message, true); }
        } catch (error) { showMessageMenaces("Erreur lors de la suppression.", true); }
    }

    loadMenaces();
</script>
