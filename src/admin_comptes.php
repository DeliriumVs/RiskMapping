<?php
// src/admin_comptes.php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ' || ($_SESSION['admin_role'] ?? '') !== 'admin') { 
    die("<div style='color:red; padding:20px;'>Accès refusé. Privilèges administrateur requis.</div>"); 
}
?>

<div style="padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d;">
    <h2 style="color: #fff; margin-top: 0;">👤 Gestion des Comptes et Approbations</h2>
    <p style="color: #8b949e; margin-bottom: 20px;">Gérez les accès, validez les inscriptions et réinitialisez les mots de passe via API REST.</p>

    <div id="api-message-comptes" style="display: none; padding: 10px; border-radius: 4px; margin-bottom: 20px;"></div>

    <table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">
        <thead>
            <tr style="text-align: left; color: #8b949e; border-bottom: 2px solid #30363d;">
                <th style="padding: 10px;">Email / Identifiant</th>
                <th style="padding: 10px;">Rôle système</th>
                <th style="padding: 10px;">Date d'inscription</th>
                <th style="padding: 10px;">Action</th>
            </tr>
        </thead>
        <tbody id="table-body-comptes">
            <tr><td colspan="4" style="text-align:center; padding:20px; color:#8b949e;">Chargement des données via API...</td></tr>
        </tbody>
    </table>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start;">
        <div>
            <h4 style="color: #3b82f6; margin-bottom: 15px;">➕ Créer manuellement un accès</h4>
            <form id="form-add-compte" style="display: flex; flex-direction: column; gap: 10px; background: #0d1117; padding: 20px; border-radius: 8px; border: 1px solid #30363d;">
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Email / Identifiant</label>
                    <input type="text" id="add-username" required style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Mot de passe de sécurité</label>
                    <input type="password" id="add-password" required pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}" placeholder="Min 12 car., Maj, Min, Chiffre, Spécial" style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Niveau d'habilitation</label>
                    <select id="add-role" style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                        <option value="lecteur">Lecteur</option><option value="animateur">Animateur</option><option value="admin">Administrateur</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: #3b82f6; border: none; color: white; margin-top: 5px;">Provisionner</button>
            </form>
        </div>
        <div>
            <h4 style="color: var(--accent-green); margin-bottom: 15px;">🔑 Réinitialiser un mot de passe</h4>
            <form id="form-reset-pwd" style="display: flex; flex-direction: column; gap: 10px; background: #0d1117; padding: 20px; border-radius: 8px; border: 1px solid #30363d;">
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Sélectionner le compte</label>
                    <select id="reset-id" required style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                        <option value="">Chargement...</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size: 0.8rem; color:#8b949e; margin-bottom:5px;">Nouveau mot de passe</label>
                    <input type="password" id="reset-password" required pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{12,}" placeholder="Min 12 car., Maj, Min, Chiffre, Spécial" style="width:100%; padding:10px; background:#161b22; border:1px solid #30363d; color:#fff; border-radius:4px; box-sizing: border-box;">
                </div>
                <button type="submit" class="btn btn-mj" style="padding: 10px 20px; background: var(--accent-green); border: none; color: #000; margin-top: 5px; font-weight:bold;">Forcer la modification</button>
            </form>
        </div>
    </div>
</div>

<script>
    var apiComptes = 'api_comptes.php';
    var tbody = document.getElementById('table-body-comptes');
    var msgBox = document.getElementById('api-message-comptes');
    var resetSelect = document.getElementById('reset-id');

    function showMsg(text, isError = false) {
        msgBox.style.display = 'block';
        msgBox.textContent = text;
        msgBox.style.backgroundColor = isError ? 'rgba(255, 68, 68, 0.2)' : 'rgba(59, 130, 246, 0.1)';
        msgBox.style.color = isError ? '#ff4d4d' : '#3b82f6';
        msgBox.style.border = `1px solid ${isError ? '#ff4d4d' : '#3b82f6'}`;
        setTimeout(() => msgBox.style.display = 'none', 5000);
    }

    async function loadComptes() {
        try {
            const response = await fetch(apiComptes);
            const json = await response.json();
            if (json.status !== 'success') { showMsg(json.message, true); return; }

            const me = parseInt(json.current_user_id); 
            tbody.innerHTML = '';
            resetSelect.innerHTML = ''; 

            json.data.forEach(u => {
                const isLocked = parseInt(u.is_locked) === 1;
                const isPending = u.role === 'en_attente';
                const isMe = parseInt(u.id) === me; 

                let trStyle = 'border-bottom: 1px solid #30363d;';
                if (isLocked) trStyle += ' background: rgba(255, 68, 68, 0.05);';
                else if (isPending) trStyle += ' background: rgba(255, 165, 0, 0.05);';

                let nameHtml = `<span style="${isLocked ? 'text-decoration: line-through; color: #8b949e;' : ''}">${u.username}</span>`;
                if (isLocked) nameHtml += `<span style="color: #ff4d4d; font-size: 0.8rem; margin-left: 5px;">[VERROUILLÉ]</span>`;
                if (isPending && u.motif_demande) nameHtml += `<div style="font-size: 0.8rem; color: orange; margin-top: 5px; font-style: italic;">Motif: "${u.motif_demande}"</div>`;

                let roleHtml = '';
                if (isMe) {
                    roleHtml = `<span style="background: rgba(218, 41, 28, 0.1); color: #da291c; padding: 3px 8px; border-radius: 4px; font-size: 0.8rem;">Administrateur (Vous)</span>`;
                } else {
                    const selPending = isPending ? 'selected' : '';
                    const selLec = u.role === 'lecteur' ? 'selected' : '';
                    const selAni = u.role === 'animateur' ? 'selected' : '';
                    const selAdm = u.role === 'admin' ? 'selected' : '';
                    const disabled = isLocked ? 'disabled' : '';
                    const colorInput = isPending ? 'orange' : '#30363d';
                    
                    roleHtml = `
                        <div style="display:flex; align-items:center; gap:8px;">
                            <select id="role-${u.id}" style="padding: 4px; background: #0d1117; color: #fff; border: 1px solid ${colorInput}; border-radius: 4px; font-size: 0.85rem;" ${disabled}>
                                ${isPending ? `<option value="en_attente" ${selPending}>⏳ En attente</option>` : ''}
                                <option value="lecteur" ${selLec}>Lecteur</option>
                                <option value="animateur" ${selAni}>Animateur</option>
                                <option value="admin" ${selAdm}>Admin</option>
                            </select>
                            <button onclick="updateRole(${u.id})" style="background: ${isPending?'orange':'#30363d'}; color: ${isPending?'#000':'#fff'}; border: none; padding: 4px 8px; border-radius: 4px; cursor: pointer; font-size: 0.75rem;" ${disabled}>Valider</button>
                        </div>
                    `;
                }

                let actionHtml = '';
                if (isMe) {
                    actionHtml = `<span style="color: #484f58; font-size: 0.8rem;">Impossible</span>`;
                } else {
                    const lockIcon = isLocked ? '🔓' : '🔒';
                    const delTitle = isPending ? 'Rejeter' : 'Supprimer';
                    actionHtml = `
                        <button onclick="toggleLock(${u.id})" style="background:none; border:none; color: ${isLocked?'#00ffcc':'orange'}; cursor:pointer; font-size: 1.2rem; margin-right: 10px;">${lockIcon}</button>
                        <button onclick="deleteAccount(${u.id})" style="background:none; border:none; color:#ff4d4d; cursor:pointer;" title="${delTitle}">🗑️</button>
                    `;
                }

                const dateCreated = new Date(u.created_at).toLocaleDateString('fr-FR');
                const tr = document.createElement('tr');
                tr.style = trStyle;
                tr.innerHTML = `
                    <td style="padding: 10px; color: #fff; font-weight: bold;">${nameHtml}</td>
                    <td style="padding: 10px;">${roleHtml}</td>
                    <td style="padding: 10px; color: #8b949e; font-size: 0.9rem;">${dateCreated}</td>
                    <td style="padding: 10px;">${actionHtml}</td>
                `;
                tbody.appendChild(tr);

                const opt = document.createElement('option');
                opt.value = u.id;
                opt.textContent = `${u.username} (${u.role})`;
                resetSelect.appendChild(opt);
            });
        } catch (error) { showMsg("Erreur réseau.", true); }
    }

    async function callApi(method, payload) {
        try {
            const res = await fetch(apiComptes, { method: method, headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
            const json = await res.json();
            if (json.status === 'success') { showMsg(json.message); loadComptes(); } 
            else { showMsg(json.message, true); }
        } catch(e) { showMsg("Erreur de communication avec le serveur.", true); }
    }

    document.getElementById('form-add-compte').addEventListener('submit', function(e) {
        e.preventDefault();
        callApi('POST', {
            username: document.getElementById('add-username').value,
            password: document.getElementById('add-password').value,
            role_utilisateur: document.getElementById('add-role').value
        }).then(() => this.reset());
    });

    function updateRole(id) {
        const newRole = document.getElementById(`role-${id}`).value;
        callApi('PATCH', { action: 'edit_role', id_user: id, new_role: newRole });
    }

    function toggleLock(id) { callApi('PATCH', { action: 'toggle_lock', id_user: id }); }

    document.getElementById('form-reset-pwd').addEventListener('submit', function(e) {
        e.preventDefault();
        if (!confirm("Forcer la modification du mot de passe pour ce compte ?")) return;
        callApi('PATCH', {
            action: 'reset_password',
            id_user: document.getElementById('reset-id').value,
            new_password: document.getElementById('reset-password').value
        }).then(() => this.reset());
    });

    function deleteAccount(id) {
        if (confirm("Révoquer/Rejeter totalement l'accès de cet utilisateur ?")) {
            callApi('DELETE', { id_user: id });
        }
    }

    loadComptes();
</script>
