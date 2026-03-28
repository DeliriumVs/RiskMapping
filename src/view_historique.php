<?php
// src/view_historique.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') { 
    die("<div style='color:red; padding:20px;'>Accès refusé.</div>"); 
}
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">📂 Historique des Ateliers</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Retrouvez ici la liste de toutes les sessions passées et leur statut.</p>

    <div id="api-message-histo" style="display: none; padding: 10px; border-radius: 4px; margin-bottom: 20px;"></div>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr>
                <th style="text-align: left; padding: 10px; border-bottom: 2px solid #30363d; color: #8b949e;">Date</th>
                <th style="text-align: left; padding: 10px; border-bottom: 2px solid #30363d; color: #8b949e;">Nom de la session</th>
                <th style="text-align: left; padding: 10px; border-bottom: 2px solid #30363d; color: #8b949e;">Code</th>
                <th style="text-align: center; padding: 10px; border-bottom: 2px solid #30363d; color: #8b949e;">Risques Evalués</th>
                <th style="text-align: center; padding: 10px; border-bottom: 2px solid #30363d; color: #8b949e;">Statut</th>
                <th style="text-align: right; padding: 10px; border-bottom: 2px solid #30363d; color: #8b949e;">Actions</th>
            </tr>
        </thead>
        <tbody id="table-body-histo">
            <tr><td colspan="6" style="text-align:center; padding:20px; color:#8b949e;">Chargement de l'historique via API...</td></tr>
        </tbody>
    </table>
</div>

<script>
    var apiHisto = 'api_historique.php';
    var tbodyHisto = document.getElementById('table-body-histo');
    var msgBoxHisto = document.getElementById('api-message-histo');

    function showMsgHisto(text, isError = false) {
        msgBoxHisto.style.display = 'block';
        msgBoxHisto.textContent = text;
        msgBoxHisto.style.backgroundColor = isError ? 'rgba(255, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.1)';
        msgBoxHisto.style.color = isError ? '#ff4d4d' : '#3b82f6';
        msgBoxHisto.style.border = `1px solid ${isError ? '#ff4d4d' : '#3b82f6'}`;
        setTimeout(() => msgBoxHisto.style.display = 'none', 5000);
    }

    async function loadHistorique() {
        try {
            const response = await fetch(apiHisto);
            const json = await response.json();

            if (json.status !== 'success') { showMsgHisto(json.message, true); return; }

            tbodyHisto.innerHTML = '';
            if (json.data.length === 0) {
                tbodyHisto.innerHTML = '<tr><td colspan="6" style="padding: 20px; text-align: center; color: #8b949e;">Aucun atelier dans l\'historique.</td></tr>';
                return;
            }

            json.data.forEach(sess => {
                const tr = document.createElement('tr');
                const dateCreation = new Date(sess.created_at).toLocaleDateString('fr-FR');
                
                let badgeStatut = '';
                if (sess.statut === 'termine') {
                    badgeStatut = `<span style="background: rgba(0, 255, 204, 0.1); color: #00ffcc; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">Terminé</span>`;
                } else {
                    badgeStatut = `<span style="background: rgba(255, 165, 0, 0.1); color: orange; padding: 3px 8px; border-radius: 10px; font-size: 0.8rem;">En cours</span>`;
                }

                let actionHTML = `<span style="color: #8b949e; font-size: 0.8rem;" title="Droits administrateur requis">🔒</span>`;
                if (json.user_role === 'admin') {
                    actionHTML = `<button onclick="deleteSessionRest(${sess.id})" class="btn" style="background: rgba(218, 41, 28, 0.1); color: #da291c; border: 1px solid #da291c; padding: 5px 10px; font-size: 0.8rem; cursor:pointer;">🗑️ Suppr.</button>`;
                }

                tr.innerHTML = `
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; color: #c9d1d9;">${dateCreation}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d;">
                        <strong style="color: #fff;">${sess.nom_session}</strong><br>
                        <span style="font-size: 0.8rem; color: #8b949e;">${sess.nb_joueurs} participants</span>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; color: var(--accent-green); font-family: monospace; font-size: 1.1rem;">${sess.code_session}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; text-align: center;">
                        <span style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; padding: 3px 8px; border-radius: 4px; font-weight: bold;">${sess.nb_risques}</span>
                    </td>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; text-align: center;">${badgeStatut}</td>
                    <td style="padding: 10px; border-bottom: 1px solid #30363d; text-align: right;">${actionHTML}</td>
                `;
                tbodyHisto.appendChild(tr);
            });
        } catch (error) { showMsgHisto("Erreur de communication avec l'API.", true); }
    }

    async function deleteSessionRest(id) {
        if (!confirm('Supprimer cette session effacera TOUS les risques qui y sont liés. Cette action est irréversible. Continuer ?')) return;
        try {
            const response = await fetch(apiHisto, { method: 'DELETE', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ id_session: id }) });
            const json = await response.json();
            if (json.status === 'success') { showMsgHisto(json.message); loadHistorique(); } 
            else { showMsgHisto(json.message, true); }
        } catch (error) { showMsgHisto("Erreur lors de la suppression.", true); }
    }

    loadHistorique();
</script>
