<?php
// src/admin_valeurs.php
session_start();
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $admin_role === 'lecteur') { 
    die("<div style='color:red; padding:20px;'>Accès refusé.</div>"); 
}
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">💎 Gestion des Valeurs Métier</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Définissez les actifs critiques à protéger.</p>

    <div id="api-message" style="display: none; padding: 10px; border-radius: 4px; margin-bottom: 20px;"></div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px; width: 90px;">Identifiant</th>
                <th style="padding: 10px;">Nom de la Valeur</th>
                <th style="padding: 10px;">Critère de Sécurité</th>
                <th style="padding: 10px;">Description</th>
                <th style="padding: 10px;">Action</th>
            </tr>
        </thead>
        <tbody id="table-body-valeurs">
            <tr><td colspan="5" style="text-align:center; padding:20px; color:#8b949e;">Chargement des données via API...</td></tr>
        </tbody>
    </table>

    <h4 style="color: #3b82f6; margin-bottom: 15px;">➕ Ajouter une Valeur Métier</h4>
    <form id="form-add-valeur" style="display: grid; grid-template-columns: 2fr 1fr 2fr auto; gap: 10px; align-items: end;">
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Nom</label>
            <input type="text" id="input-nom" required style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Critère</label>
            <select id="input-critere" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
                <option>Disponibilité</option><option>Confidentialité</option><option>Intégrité</option><option>Image / Réputation</option><option>Légal / Conformité</option>
            </select>
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Description</label>
            <input type="text" id="input-description" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: #3b82f6; border: none; color: white;">Ajouter</button>
    </form>
</div>

<script>
    var apiEndpoint = 'api_valeurs.php';
    var tableBody = document.getElementById('table-body-valeurs');
    var msgBox = document.getElementById('api-message');

    function showMessage(text, isError = false) {
        msgBox.style.display = 'block';
        msgBox.textContent = text;
        msgBox.style.backgroundColor = isError ? 'rgba(255, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.1)';
        msgBox.style.color = isError ? '#ff4d4d' : '#3b82f6';
        msgBox.style.border = `1px solid ${isError ? '#ff4d4d' : '#3b82f6'}`;
        setTimeout(() => msgBox.style.display = 'none', 4000);
    }

    async function loadValeurs() {
        try {
            const response = await fetch(apiEndpoint);
            const json = await response.json();
            if (json.status !== 'success') { showMessage(json.message, true); return; }
            
            tableBody.innerHTML = '';
            if (json.data.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:10px; color:#8b949e;">Aucune valeur configurée.</td></tr>';
                return;
            }

            json.data.forEach(valeur => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #30363d';
                let deleteBtnHTML = `<span style="color: #8b949e; font-size: 0.8rem;" title="Droits administrateur requis">🔒</span>`;
                if (json.user_role === 'admin') {
                    deleteBtnHTML = `<button onclick="deleteValeur(${valeur.id})" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" title="Supprimer">🗑️</button>`;
                }
                const vmId = 'VM-' + String(valeur.id).padStart(3, '0');
                tr.innerHTML = `
                    <td style="padding: 10px;">
                        <span style="font-family: monospace; font-size: 0.72rem; background: rgba(59,130,246,0.12); color: #3b82f6; border: 1px solid #3b82f6; padding: 2px 7px; border-radius: 4px;">${vmId}</span>
                    </td>
                    <td style="padding: 10px; color: #fff; font-weight: bold; text-align: left;">${valeur.nom}</td>
                    <td style="padding: 10px;"><span style="background: #30363d; color: #c9d1d9; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;">${valeur.critere_impacte}</span></td>
                    <td style="padding: 10px; color: #8b949e; font-size: 0.9rem; text-align: left;">${valeur.description || ''}</td>
                    <td style="padding: 10px;">${deleteBtnHTML}</td>
                `;
                tableBody.appendChild(tr);
            });
        } catch (error) { showMessage("Erreur de connexion à l'API.", true); }
    }

    document.getElementById('form-add-valeur').addEventListener('submit', async function(e) {
        e.preventDefault();
        const data = {
            nom: document.getElementById('input-nom').value,
            critere: document.getElementById('input-critere').value,
            description: document.getElementById('input-description').value
        };
        try {
            const response = await fetch(apiEndpoint, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const json = await response.json();
            if (json.status === 'success') {
                showMessage(json.message); document.getElementById('form-add-valeur').reset(); loadValeurs();
            } else { showMessage(json.message, true); }
        } catch (error) { showMessage("Erreur lors de l'envoi à l'API.", true); }
    });

    async function deleteValeur(id) {
        if (!confirm("Voulez-vous vraiment supprimer cette valeur métier ?")) return;
        try {
            const response = await fetch(apiEndpoint, { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
            const json = await response.json();
            if (json.status === 'success') { showMessage(json.message); loadValeurs(); } 
            else { showMessage(json.message, true); }
        } catch (error) { showMessage("Erreur lors de la suppression.", true); }
    }

    loadValeurs();
</script>
