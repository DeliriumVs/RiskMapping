<?php
// src/admin_equipes.php
session_start();
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $admin_role === 'lecteur') { 
    die("<div style='color:red; padding:20px;'>Accès refusé.</div>"); 
}
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">🏢 Gestion des Équipes / Services</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Personnalisez la liste des directions proposées lors de l'inscription des participants.</p>

    <div id="api-message-equipes" style="display: none; padding: 10px; border-radius: 4px; margin-bottom: 20px;"></div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px;">Nom de la Direction / Équipe</th>
                <th style="padding: 10px;">Action</th>
            </tr>
        </thead>
        <tbody id="table-body-equipes">
            <tr><td colspan="2" style="text-align:center; padding:20px; color:#8b949e;">Chargement des données via API...</td></tr>
        </tbody>
    </table>

    <h4 style="color: #3b82f6; margin-bottom: 15px;">➕ Ajouter un nouveau service</h4>
    <form id="form-add-equipe" style="display: flex; gap: 10px;">
        <input type="text" id="input-nom-equipe" placeholder="Ex: Direction des Ressources Humaines" required style="flex: 1; padding: 10px; background: #0d1117; color: #fff; border: 1px solid #3b82f6; border-radius: 4px;">
        <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: #3b82f6; border: none; color: white;">Ajouter</button>
    </form>
</div>

<script>
    var apiEndpointEquipes = 'api_equipes.php';
    var tableBodyEquipes = document.getElementById('table-body-equipes');
    var msgBoxEquipes = document.getElementById('api-message-equipes');

    function showMessageEquipes(text, isError = false) {
        msgBoxEquipes.style.display = 'block';
        msgBoxEquipes.textContent = text;
        msgBoxEquipes.style.backgroundColor = isError ? 'rgba(255, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.1)';
        msgBoxEquipes.style.color = isError ? '#ff4d4d' : '#3b82f6';
        msgBoxEquipes.style.border = `1px solid ${isError ? '#ff4d4d' : '#3b82f6'}`;
        setTimeout(() => msgBoxEquipes.style.display = 'none', 4000);
    }

    async function loadEquipes() {
        try {
            const response = await fetch(apiEndpointEquipes);
            const json = await response.json();

            if (json.status !== 'success') { showMessageEquipes(json.message, true); return; }

            tableBodyEquipes.innerHTML = '';
            if (json.data.length === 0) {
                tableBodyEquipes.innerHTML = '<tr><td colspan="2" style="text-align:center; padding:10px; color:#8b949e;">Aucune équipe configurée.</td></tr>';
                return;
            }

            json.data.forEach(equipe => {
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #30363d';
                let deleteBtnHTML = `<span style="color: #8b949e; font-size: 0.8rem;" title="Droits administrateur requis">🔒</span>`;
                if (json.user_role === 'admin') {
                    deleteBtnHTML = `<button onclick="deleteEquipe(${equipe.id})" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" title="Supprimer">🗑️</button>`;
                }
                tr.innerHTML = `
                    <td style="padding: 10px; color: #fff; font-weight: bold;">${equipe.nom}</td>
                    <td style="padding: 10px;">${deleteBtnHTML}</td>
                `;
                tableBodyEquipes.appendChild(tr);
            });
        } catch (error) { showMessageEquipes("Erreur de connexion à l'API.", true); }
    }

    document.getElementById('form-add-equipe').addEventListener('submit', async function(e) {
        e.preventDefault(); 
        const data = { nom_equipe: document.getElementById('input-nom-equipe').value };
        try {
            const response = await fetch(apiEndpointEquipes, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) });
            const json = await response.json();
            if (json.status === 'success') {
                showMessageEquipes(json.message); document.getElementById('form-add-equipe').reset(); loadEquipes(); 
            } else { showMessageEquipes(json.message, true); }
        } catch (error) { showMessageEquipes("Erreur lors de l'envoi à l'API.", true); }
    });

    async function deleteEquipe(id) {
        if (!confirm("Voulez-vous vraiment supprimer ce service de la liste ?")) return;
        try {
            const response = await fetch(apiEndpointEquipes, { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id: id }) });
            const json = await response.json();
            if (json.status === 'success') { showMessageEquipes(json.message); loadEquipes(); } 
            else { showMessageEquipes(json.message, true); }
        } catch (error) { showMessageEquipes("Erreur lors de la suppression.", true); }
    }

    loadEquipes();
</script>
