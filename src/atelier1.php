<?php
// src/atelier1.php — Atelier 1 : Cadrage / Valeurs Métier & Événements Redoutés
session_start();
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("<div style='color:red;padding:20px;'>Accès refusé.</div>");
}
?>
<style>
.atl-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.atl-title { color:#fff; font-size:1.3rem; font-weight:bold; margin:0; }
.atl-subtitle { color:#8b949e; font-size:0.85rem; margin-top:4px; }

/* VM cards */
.vm-card { background:#0d1117; border:1px solid #30363d; border-radius:8px; margin-bottom:16px; overflow:hidden; }
.vm-card-header { display:flex; align-items:center; gap:12px; padding:14px 16px; cursor:pointer; transition:background 0.15s; }
.vm-card-header:hover { background:#161b22; }
.vm-badge { font-family:monospace; font-size:0.72rem; background:rgba(59,130,246,0.12); color:#3b82f6; border:1px solid #3b82f6; padding:3px 8px; border-radius:4px; white-space:nowrap; flex-shrink:0; }
.vm-nom { color:#c9d1d9; font-weight:bold; font-size:0.95rem; flex:1; }
.vm-critere { font-size:0.78rem; background:#21262d; color:#8b949e; padding:2px 8px; border-radius:10px; }
.vm-er-count { font-size:0.78rem; color:#8b949e; white-space:nowrap; }
.vm-chevron { color:#8b949e; transition:transform 0.2s; font-size:0.85rem; }
.vm-chevron.open { transform: rotate(90deg); }

.vm-body { display:none; border-top:1px solid #21262d; }
.vm-body.open { display:block; }

/* ER list */
.er-list { padding:0; }
.er-row { display:flex; align-items:center; gap:10px; padding:10px 16px; border-bottom:1px solid #1c2128; flex-wrap:wrap; }
.er-row:last-child { border-bottom:none; }
.er-num { font-family:monospace; font-size:0.7rem; color:#484f58; width:50px; flex-shrink:0; }
.er-cat { font-size:0.72rem; padding:2px 7px; border-radius:10px; font-weight:bold; white-space:nowrap; flex-shrink:0; }
.cat-Financier    { background:rgba(34,197,94,0.12);  color:#22c55e; border:1px solid rgba(34,197,94,0.3); }
.cat-Opérationnel { background:rgba(59,130,246,0.12); color:#3b82f6; border:1px solid rgba(59,130,246,0.3); }
.cat-Juridique    { background:rgba(168,85,247,0.12); color:#a855f7; border:1px solid rgba(168,85,247,0.3); }
.cat-Image        { background:rgba(245,158,11,0.12); color:#f59e0b; border:1px solid rgba(245,158,11,0.3); }
.cat-Santé        { background:rgba(236,72,153,0.12); color:#ec4899; border:1px solid rgba(236,72,153,0.3); }

.er-desc { color:#c9d1d9; font-size:0.9rem; flex:1; min-width:150px; }
.er-impact { font-size:0.75rem; padding:2px 8px; border-radius:10px; font-weight:bold; white-space:nowrap; cursor:pointer; flex-shrink:0; }
.imp-Mineur       { background:rgba(139,148,158,0.15); color:#8b949e; border:1px solid #30363d; }
.imp-Significatif { background:rgba(245,158,11,0.15);  color:#f59e0b; border:1px solid rgba(245,158,11,0.4); }
.imp-Majeur       { background:rgba(218,41,28,0.15);   color:#da291c; border:1px solid rgba(218,41,28,0.4); }
.imp-Critique     { background:rgba(139,0,0,0.25);     color:#ff4444; border:1px solid #ff4444; font-weight:900; }

.er-del { background:none; border:none; color:#484f58; cursor:pointer; font-size:0.85rem; padding:2px 6px; flex-shrink:0; }
.er-del:hover { color:#da291c; }

/* Add ER form */
.er-add-form { background:#161b22; padding:14px 16px; border-top:1px dashed #30363d; display:none; }
.er-add-form.open { display:block; }
.er-form-grid { display:grid; grid-template-columns:1fr 1fr 2fr 1fr auto; gap:8px; align-items:end; }
.er-form-grid select, .er-form-grid input {
    background:#0d1117; border:1px solid #30363d; color:#c9d1d9;
    padding:7px 10px; border-radius:4px; font-size:0.85rem; width:100%;
}
.btn-er-add { background:#3b82f6; border:none; color:#fff; padding:7px 14px; border-radius:4px; cursor:pointer; font-size:0.85rem; white-space:nowrap; }
.btn-er-toggle { background:transparent; border:1px dashed #30363d; color:#8b949e; padding:5px 12px; border-radius:4px; cursor:pointer; font-size:0.8rem; margin:10px 16px; transition:0.15s; }
.btn-er-toggle:hover { border-color:#3b82f6; color:#3b82f6; }

/* Add VM section */
.add-vm-section { background:#0d1117; border:1px dashed #30363d; border-radius:8px; padding:16px; margin-top:8px; }

/* BS section */
.bs-section { margin-top:32px; }
.bs-table { width:100%; border-collapse:collapse; }
.bs-table th { padding:8px 10px; text-align:left; color:#8b949e; font-size:0.8rem; border-bottom:1px solid #30363d; }
.bs-table td { padding:8px 10px; border-bottom:1px solid #1c2128; color:#c9d1d9; font-size:0.85rem; }
.bs-badge { font-family:monospace; font-size:0.7rem; background:rgba(14,165,233,0.12); color:#0ea5e9; border:1px solid #0ea5e9; padding:2px 6px; border-radius:4px; }

.msg-atl { padding:8px 12px; border-radius:4px; margin-bottom:12px; font-size:0.85rem; display:none; }
</style>

<div style="padding:20px; background:#161b22; border-radius:8px; border:1px solid #30363d;">

    <div class="atl-header">
        <div>
            <div class="atl-title">📋 Atelier 1 — Cadrage</div>
            <div class="atl-subtitle">Valeurs Métier · Événements Redoutés · Biens Supports</div>
        </div>
        <?php if ($admin_role !== 'lecteur'): ?>
        <button onclick="toggleAddVm()" style="background:rgba(34,197,94,0.1); border:1px solid #22c55e; color:#22c55e; padding:7px 14px; border-radius:6px; cursor:pointer; font-size:0.85rem;">➕ Ajouter une VM</button>
        <?php endif; ?>
    </div>

    <div class="msg-atl" id="msg-atl"></div>

    <!-- Formulaire ajout VM -->
    <?php if ($admin_role !== 'lecteur'): ?>
    <div id="form-add-vm" style="display:none; background:#0d1117; border:1px solid #30363d; border-radius:8px; padding:16px; margin-bottom:16px;">
        <div style="display:grid; grid-template-columns:2fr 1fr 2fr; gap:10px; margin-bottom:10px;">
            <input type="text" id="vm-nom" placeholder="Nom de la Valeur Métier *" style="background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px;">
            <select id="vm-critere" style="background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px;">
                <option>Disponibilité</option>
                <option>Confidentialité</option>
                <option>Intégrité</option>
                <option>Image / Réputation</option>
                <option>Légal / Conformité</option>
            </select>
            <input type="text" id="vm-desc" placeholder="Description (optionnel)" style="background:#161b22; border:1px solid #30363d; color:#c9d1d9; padding:8px; border-radius:4px;">
        </div>
        <div style="display:flex; gap:8px;">
            <button onclick="createVM()" style="background:#3b82f6; border:none; color:#fff; padding:8px 16px; border-radius:4px; cursor:pointer;">Créer</button>
            <button onclick="toggleAddVm()" style="background:#30363d; border:none; color:#8b949e; padding:8px 14px; border-radius:4px; cursor:pointer;">Annuler</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- VM cards -->
    <div id="vm-list">
        <div style="text-align:center; color:#8b949e; padding:30px;">Chargement…</div>
    </div>

    <!-- BS section -->
    <div class="bs-section">
        <h4 style="color:#0ea5e9; margin-bottom:12px; border-top:1px solid #30363d; padding-top:20px;">🔷 Biens Supports associés</h4>
        <p style="color:#8b949e; font-size:0.85rem; margin-bottom:12px;">Actifs techniques et organisationnels qui supportent les Valeurs Métier. Gérez-les via le menu Référentiels → Biens Supports.</p>
        <div id="bs-summary">
            <div style="text-align:center; color:#484f58; padding:20px;">Chargement…</div>
        </div>
    </div>
</div>

<script>
(function() {
    var API_VM  = 'api_valeurs.php';
    var API_ER  = 'api_evenements_redoutes.php';
    var API_BS  = 'api_biens_supports.php';
    var IS_ADMIN = <?= $admin_role === 'admin' ? 'true' : 'false' ?>;
    var CAN_EDIT = <?= $admin_role !== 'lecteur' ? 'true' : 'false' ?>;
    var allVMs = [];
    var allERs = {};  // keyed by vm_id

    var CATS    = ['Financier','Opérationnel','Juridique','Image','Santé'];
    var IMPACTS = ['Mineur','Significatif','Majeur','Critique'];

    function showMsg(text, ok) {
        var el = document.getElementById('msg-atl');
        el.textContent = text;
        el.style.background = ok ? 'rgba(34,197,94,0.15)' : 'rgba(218,41,28,0.15)';
        el.style.color      = ok ? '#22c55e' : '#da291c';
        el.style.border     = '1px solid ' + (ok ? 'rgba(34,197,94,0.3)' : 'rgba(218,41,28,0.3)');
        el.style.display    = 'block';
        setTimeout(function() { el.style.display='none'; }, 4000);
    }

    function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function impactNext(current) {
        var idx = IMPACTS.indexOf(current);
        return IMPACTS[(idx + 1) % IMPACTS.length];
    }

    // -------------------------------------------------------
    // CHARGEMENT PRINCIPAL
    // -------------------------------------------------------
    async function load() {
        var [vmRes, erRes] = await Promise.all([
            fetch(API_VM).then(r=>r.json()),
            fetch(API_ER).then(r=>r.json())
        ]);

        if (vmRes.status !== 'success') { showMsg(vmRes.message, false); return; }
        allVMs = vmRes.data;

        // Indexer les ER par vm_id
        allERs = {};
        if (erRes.status === 'success') {
            erRes.data.forEach(function(er) {
                if (!allERs[er.valeur_metier_id]) allERs[er.valeur_metier_id] = [];
                allERs[er.valeur_metier_id].push(er);
            });
        }

        renderVMs();
        loadBS();
    }

    function renderVMs() {
        var el = document.getElementById('vm-list');
        if (allVMs.length === 0) {
            el.innerHTML = '<div style="text-align:center; color:#8b949e; padding:30px; border:1px dashed #30363d; border-radius:8px;">Aucune Valeur Métier. Cliquez « Ajouter une VM » pour commencer.</div>';
            return;
        }

        el.innerHTML = allVMs.map(function(vm) {
            var vmId  = 'VM-' + String(vm.id).padStart(3,'0');
            var ers   = allERs[vm.id] || [];
            var erCount = ers.length;
            var erHtml = renderERs(vm.id, ers);
            var critColors = {
                'Disponibilité':'#22c55e','Confidentialité':'#3b82f6','Intégrité':'#f59e0b',
                'Image / Réputation':'#a855f7','Légal / Conformité':'#ec4899'
            };
            var cc = critColors[vm.critere_impacte] || '#8b949e';

            return '<div class="vm-card" id="vm-card-' + vm.id + '">' +
                '<div class="vm-card-header" onclick="toggleVM(' + vm.id + ')">' +
                    '<span class="vm-badge">' + esc(vmId) + '</span>' +
                    '<span class="vm-nom">' + esc(vm.nom) + '</span>' +
                    '<span class="vm-critere" style="color:' + cc + '; border-color:' + cc + '40; background:' + cc + '1a;">' + esc(vm.critere_impacte) + '</span>' +
                    '<span class="vm-er-count">' + erCount + ' ER</span>' +
                    (IS_ADMIN ? '<button onclick="event.stopPropagation(); deleteVM(' + vm.id + ')" style="background:none; border:none; color:#484f58; cursor:pointer; font-size:0.8rem; padding:2px 6px;" title="Supprimer la VM">🗑️</button>' : '') +
                    '<span class="vm-chevron" id="chevron-' + vm.id + '">▶</span>' +
                '</div>' +
                '<div class="vm-body" id="vm-body-' + vm.id + '">' +
                    '<div class="er-list" id="er-list-' + vm.id + '">' + erHtml + '</div>' +
                    (CAN_EDIT ?
                        '<button class="btn-er-toggle" onclick="toggleAddER(' + vm.id + ')">➕ Ajouter un Événement Redouté</button>' +
                        '<div class="er-add-form" id="er-form-' + vm.id + '">' +
                            '<div class="er-form-grid">' +
                                '<div><label style="font-size:0.75rem; color:#8b949e; display:block; margin-bottom:4px;">Catégorie</label>' +
                                    '<select id="er-cat-' + vm.id + '">' +
                                    CATS.map(function(c) { return '<option>' + c + '</option>'; }).join('') +
                                    '</select></div>' +
                                '<div><label style="font-size:0.75rem; color:#8b949e; display:block; margin-bottom:4px;">Impact</label>' +
                                    '<select id="er-imp-' + vm.id + '">' +
                                    IMPACTS.map(function(i) { return '<option>' + i + '</option>'; }).join('') +
                                    '</select></div>' +
                                '<div><label style="font-size:0.75rem; color:#8b949e; display:block; margin-bottom:4px;">Description *</label>' +
                                    '<input type="text" id="er-desc-' + vm.id + '" placeholder="Ex: Interruption du service de paiement…"></div>' +
                                '<div><label style="font-size:0.75rem; color:#8b949e; display:block; margin-bottom:4px;">Notes</label>' +
                                    '<input type="text" id="er-notes-' + vm.id + '" placeholder="(optionnel)"></div>' +
                                '<div><label style="font-size:0.75rem; color:#8b949e; display:block; margin-bottom:4px;">&nbsp;</label>' +
                                    '<button class="btn-er-add" onclick="addER(' + vm.id + ')">Ajouter</button></div>' +
                            '</div>' +
                        '</div>'
                    : '') +
                '</div>' +
            '</div>';
        }).join('');
    }

    function renderERs(vmId, ers) {
        if (ers.length === 0) {
            return '<div style="padding:12px 16px; color:#484f58; font-size:0.85rem; font-style:italic;">Aucun événement redouté défini.</div>';
        }
        return ers.map(function(er) {
            var erId = 'ER-' + String(er.id).padStart(3,'0');
            return '<div class="er-row">' +
                '<span class="er-num">' + esc(erId) + '</span>' +
                '<span class="er-cat cat-' + er.categorie.split('/')[0].trim() + '">' + esc(er.categorie) + '</span>' +
                '<span class="er-desc">' + esc(er.description) +
                    (er.notes ? '<br><span style="font-size:0.78rem; color:#8b949e;">' + esc(er.notes) + '</span>' : '') +
                '</span>' +
                (CAN_EDIT ?
                    '<span class="er-impact imp-' + er.impact + '" onclick="cycleImpact(' + er.id + ', \'' + er.impact + '\', ' + vmId + ')" title="Cliquer pour changer">' + esc(er.impact) + '</span>'
                :
                    '<span class="er-impact imp-' + er.impact + '">' + esc(er.impact) + '</span>'
                ) +
                (IS_ADMIN ? '<button class="er-del" onclick="deleteER(' + er.id + ', ' + vmId + ')" title="Supprimer">🗑</button>' : '') +
            '</div>';
        }).join('');
    }

    // -------------------------------------------------------
    // INTERACTIONS
    // -------------------------------------------------------
    window.toggleVM = function(id) {
        var body    = document.getElementById('vm-body-' + id);
        var chevron = document.getElementById('chevron-' + id);
        body.classList.toggle('open');
        chevron.classList.toggle('open');
    };

    window.toggleAddVM = function() {
        var f = document.getElementById('form-add-vm');
        f.style.display = f.style.display === 'none' ? 'block' : 'none';
    };

    window.toggleAddER = function(vmId) {
        var f = document.getElementById('er-form-' + vmId);
        f.classList.toggle('open');
    };

    window.createVM = async function() {
        var nom = document.getElementById('vm-nom').value.trim();
        if (!nom) { showMsg('Le nom est obligatoire.', false); return; }
        var res  = await fetch(API_VM, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({nom, critere: document.getElementById('vm-critere').value, description: document.getElementById('vm-desc').value.trim()})
        });
        var json = await res.json();
        showMsg(json.message, json.status === 'success');
        if (json.status === 'success') {
            document.getElementById('form-add-vm').style.display = 'none';
            document.getElementById('vm-nom').value = '';
            document.getElementById('vm-desc').value = '';
            load();
        }
    };

    window.deleteVM = async function(id) {
        if (!confirm('Supprimer cette Valeur Métier et tous ses Événements Redoutés ?')) return;
        var res  = await fetch(API_VM, {method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})});
        var json = await res.json();
        showMsg(json.message, json.status === 'success');
        if (json.status === 'success') load();
    };

    window.addER = async function(vmId) {
        var desc = document.getElementById('er-desc-' + vmId).value.trim();
        if (!desc) { showMsg('La description est obligatoire.', false); return; }
        var res  = await fetch(API_ER, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                valeur_metier_id: vmId,
                categorie: document.getElementById('er-cat-' + vmId).value,
                impact:    document.getElementById('er-imp-' + vmId).value,
                description: desc,
                notes: document.getElementById('er-notes-' + vmId).value.trim()
            })
        });
        var json = await res.json();
        showMsg(json.message, json.status === 'success');
        if (json.status === 'success') {
            document.getElementById('er-desc-' + vmId).value  = '';
            document.getElementById('er-notes-' + vmId).value = '';
            load();
        }
    };

    window.cycleImpact = async function(id, current, vmId) {
        var next = impactNext(current);
        var res  = await fetch(API_ER, {method:'PATCH', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id, impact: next})});
        var json = await res.json();
        if (json.status === 'success') load();
    };

    window.deleteER = async function(id, vmId) {
        if (!confirm('Supprimer cet Événement Redouté ?')) return;
        var res  = await fetch(API_ER, {method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})});
        var json = await res.json();
        showMsg(json.message, json.status === 'success');
        if (json.status === 'success') load();
    };

    // -------------------------------------------------------
    // BIENS SUPPORTS (résumé)
    // -------------------------------------------------------
    async function loadBS() {
        var res  = await fetch(API_BS);
        var json = await res.json();
        var el   = document.getElementById('bs-summary');

        if (json.status !== 'success' || json.data.length === 0) {
            el.innerHTML = '<p style="color:#484f58; font-size:0.85rem;">Aucun Bien Support configuré.</p>';
            return;
        }

        var vmMap = {};
        (json.valeurs_metier || []).forEach(function(vm) { vmMap[vm.id] = vm.nom; });

        el.innerHTML = '<table class="bs-table">' +
            '<thead><tr><th>Identifiant</th><th>Type</th><th>Nom</th><th>Valeurs Métier supportées</th></tr></thead>' +
            '<tbody>' +
            json.data.map(function(bs) {
                var bsId   = 'BS-' + String(bs.id).padStart(3,'0');
                var vmBadges = (bs.vm_ids||[]).map(function(vid) {
                    return '<span class="bs-badge" style="margin-right:4px;">VM-' + String(vid).padStart(3,'0') + '</span>';
                }).join('') || '—';
                return '<tr>' +
                    '<td><span class="bs-badge">' + esc(bsId) + '</span></td>' +
                    '<td>' + esc(bs.type_bien) + '</td>' +
                    '<td style="font-weight:bold;">' + esc(bs.nom) + '</td>' +
                    '<td>' + vmBadges + '</td>' +
                '</tr>';
            }).join('') +
            '</tbody></table>';
    }

    load();
})();
</script>
