<?php
// src/admin_biens_supports.php
session_start();
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || $admin_role === 'lecteur') {
    die("<div style='color:red; padding:20px;'>Accès refusé.</div>");
}
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">🔷 Gestion des Biens Supports</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Actifs techniques et organisationnels qui supportent les Valeurs Métier.</p>

    <div id="api-message-bs" style="display: none; padding: 10px; border-radius: 4px; margin-bottom: 20px;"></div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px; width: 90px;">Identifiant</th>
                <th style="padding: 10px; width: 180px;">Type</th>
                <th style="padding: 10px;">Nom</th>
                <th style="padding: 10px;">Valeurs Métier associées</th>
                <th style="padding: 10px;">Action</th>
            </tr>
        </thead>
        <tbody id="table-body-bs">
            <tr><td colspan="5" style="text-align:center; padding:20px; color:#8b949e;">Chargement des données via API...</td></tr>
        </tbody>
    </table>

    <h4 style="color: #3b82f6; margin-bottom: 15px;">➕ Ajouter un Bien Support</h4>
    <form id="form-add-bs" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Nom *</label>
            <input type="text" id="bs-nom" placeholder="Ex: Serveur AD, VPN Ivanti…" required
                style="width:100%; box-sizing:border-box; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div>
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Type</label>
            <select id="bs-type" style="width:100%; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
                <option>Logiciel / Application</option>
                <option>Infrastructure réseau</option>
                <option>Serveur / Cloud</option>
                <option>Poste de travail</option>
                <option>Personne / Équipe</option>
                <option>Site / Local</option>
                <option>Autre</option>
            </select>
        </div>
        <div style="grid-column: 1 / -1;">
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Description (optionnel)</label>
            <input type="text" id="bs-description" placeholder="Ex: Contrôleur de domaine principal, hébergé on-premise"
                style="width:100%; box-sizing:border-box; padding:10px; background:#0d1117; border:1px solid #30363d; color:#fff; border-radius:4px;">
        </div>
        <div style="grid-column: 1 / -1;">
            <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:8px;">Valeurs Métier supportées (optionnel)</label>
            <div id="bs-vm-checkboxes" style="display:flex; flex-wrap:wrap; gap:8px; background:#0d1117; border:1px solid #30363d; border-radius:4px; padding:10px; min-height:42px;">
                <span style="color:#484f58; font-size:0.85rem;">Chargement…</span>
            </div>
        </div>
        <div style="grid-column: 1 / -1; text-align: right;">
            <button type="submit" class="btn btn-mj" style="padding: 10px 25px; background: #3b82f6; border: none; color: white;">Ajouter</button>
        </div>
    </form>
</div>

<script>
(function() {
    const API = 'api_biens_supports.php';
    const tbody = document.getElementById('table-body-bs');
    const msgBox = document.getElementById('api-message-bs');
    const vmCheckboxes = document.getElementById('bs-vm-checkboxes');
    let allVms = [];

    function showMsg(text, isError = false) {
        msgBox.style.display = 'block';
        msgBox.textContent = text;
        msgBox.style.backgroundColor = isError ? 'rgba(255,68,68,0.2)' : 'rgba(59,130,246,0.1)';
        msgBox.style.color = isError ? '#ff4d4d' : '#3b82f6';
        msgBox.style.border = `1px solid ${isError ? '#ff4d4d' : '#3b82f6'}`;
        setTimeout(() => msgBox.style.display = 'none', 4000);
    }

    function typeIcon(t) {
        const icons = {
            'Logiciel / Application': '💿', 'Infrastructure réseau': '🌐',
            'Serveur / Cloud': '☁️', 'Poste de travail': '💻',
            'Personne / Équipe': '👥', 'Site / Local': '🏢', 'Autre': '📦'
        };
        return icons[t] || '📦';
    }

    function vmBadge(id) {
        return `<span style="font-family:monospace; font-size:0.72rem; background:rgba(59,130,246,0.12); color:#3b82f6; border:1px solid #3b82f6; padding:2px 6px; border-radius:4px; margin-right:4px;">VM-${String(id).padStart(3,'0')}</span>`;
    }

    async function loadBS() {
        try {
            const res  = await fetch(API);
            const json = await res.json();
            if (json.status !== 'success') { showMsg(json.message, true); return; }

            allVms = json.valeurs_metier || [];
            renderVmCheckboxes();

            tbody.innerHTML = '';
            if (json.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:10px; color:#8b949e;">Aucun Bien Support configuré.</td></tr>';
                return;
            }

            json.data.forEach(bs => {
                const bsId = 'BS-' + String(bs.id).padStart(3, '0');
                const vmBadges = (bs.vm_ids || []).map(vmBadge).join('') || '<span style="color:#484f58; font-size:0.8rem;">—</span>';
                let delBtn = `<span style="color:#8b949e; font-size:0.8rem;" title="Droits admin requis">🔒</span>`;
                if (json.user_role === 'admin') {
                    delBtn = `<button onclick="deleteBS(${bs.id})" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" title="Supprimer">🗑️</button>`;
                }
                const tr = document.createElement('tr');
                tr.style.borderBottom = '1px solid #30363d';
                tr.innerHTML = `
                    <td style="padding:10px;">
                        <span style="font-family:monospace; font-size:0.72rem; background:rgba(14,165,233,0.12); color:#0ea5e9; border:1px solid #0ea5e9; padding:2px 7px; border-radius:4px;">${bsId}</span>
                    </td>
                    <td style="padding:10px; color:#c9d1d9; font-size:0.85rem;">${typeIcon(bs.type_bien)} ${bs.type_bien}</td>
                    <td style="padding:10px; color:#fff; font-weight:bold;">${bs.nom}${bs.description ? `<br><span style="font-size:0.8rem; color:#8b949e; font-weight:normal;">${bs.description}</span>` : ''}</td>
                    <td style="padding:10px;">${vmBadges}</td>
                    <td style="padding:10px;">${delBtn}</td>
                `;
                tbody.appendChild(tr);
            });
        } catch(e) { showMsg("Erreur de connexion à l'API.", true); }
    }

    function renderVmCheckboxes() {
        vmCheckboxes.innerHTML = allVms.map(vm =>
            `<label style="display:flex; align-items:center; gap:6px; color:#c9d1d9; font-size:0.85rem; cursor:pointer; background:#161b22; border:1px solid #30363d; padding:4px 10px; border-radius:4px;">
                <input type="checkbox" class="bs-vm-cb" value="${vm.id}" style="margin:0;">
                <span style="font-family:monospace; font-size:0.7rem; color:#3b82f6;">VM-${String(vm.id).padStart(3,'0')}</span>
                ${vm.nom}
            </label>`
        ).join('');
    }

    document.getElementById('form-add-bs').addEventListener('submit', async function(e) {
        e.preventDefault();
        const vm_ids = [...document.querySelectorAll('.bs-vm-cb:checked')].map(cb => parseInt(cb.value));
        const data = {
            nom:         document.getElementById('bs-nom').value,
            type_bien:   document.getElementById('bs-type').value,
            description: document.getElementById('bs-description').value,
            vm_ids
        };
        try {
            const res  = await fetch(API, { method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify(data) });
            const json = await res.json();
            if (json.status === 'success') { showMsg(json.message); this.reset(); renderVmCheckboxes(); loadBS(); }
            else { showMsg(json.message, true); }
        } catch(e) { showMsg("Erreur lors de l'envoi à l'API.", true); }
    });

    window.deleteBS = async function(id) {
        if (!confirm("Supprimer ce Bien Support ?")) return;
        try {
            const res  = await fetch(API, { method: 'DELETE', headers: {'Content-Type':'application/json'}, body: JSON.stringify({id}) });
            const json = await res.json();
            if (json.status === 'success') { showMsg(json.message); loadBS(); }
            else { showMsg(json.message, true); }
        } catch(e) { showMsg("Erreur lors de la suppression.", true); }
    };

    loadBS();
})();
</script>
