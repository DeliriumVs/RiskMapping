<?php
// src/atelier2.php — Atelier 2 : Sources de Risque & Objectifs Visés
session_start();
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("<div style='color:red;padding:20px;'>Accès refusé.</div>");
}
?>
<style>
/* ── Layout deux colonnes ─────────────────────────────── */
.atl2-wrap  { display:flex; gap:16px; align-items:flex-start; }
.atl2-left  { flex:0 0 42%; min-width:0; }
.atl2-right { flex:1; min-width:0; position:sticky; top:10px; }

.atl2-panel { background:#0d1117; border:1px solid #30363d; border-radius:8px; overflow:hidden; }
.atl2-ph    { display:flex; align-items:center; justify-content:space-between; padding:13px 16px; background:#161b22; border-bottom:1px solid #30363d; }
.atl2-ph h3 { margin:0; font-size:1rem; color:#c9d1d9; }
.atl2-body  { padding:0; }

/* ── SR cards ─────────────────────────────────────────── */
.sr-card       { border-bottom:1px solid #1c2128; padding:12px 16px; cursor:pointer; transition:background 0.15s; }
.sr-card:hover { background:#161b22; }
.sr-card.active { background:rgba(59,130,246,0.08); border-left:3px solid #3b82f6; padding-left:13px; }
.sr-card:last-child { border-bottom:none; }
.sr-card-top   { display:flex; align-items:center; gap:10px; margin-bottom:6px; }
.sr-id         { font-family:monospace; font-size:0.7rem; background:rgba(218,41,28,0.12); color:#da291c; border:1px solid rgba(218,41,28,0.35); padding:2px 7px; border-radius:4px; flex-shrink:0; }
.sr-nom        { color:#c9d1d9; font-size:0.9rem; font-weight:bold; flex:1; }
.sr-del        { background:none; border:none; color:#484f58; cursor:pointer; font-size:0.85rem; padding:2px 5px; flex-shrink:0; }
.sr-del:hover  { color:#da291c; }
.sr-foot       { display:flex; align-items:center; justify-content:space-between; gap:8px; margin-top:8px; }
.ov-cnt        { font-size:0.7rem; padding:2px 8px; border-radius:10px; background:#21262d; color:#8b949e; white-space:nowrap; }
.ov-cnt.has-ov { background:rgba(59,130,246,0.1); color:#3b82f6; border:1px solid rgba(59,130,246,0.25); }

/* ── Note badges ─────────────────────────────────────── */
.sr-notes  { display:flex; gap:6px; flex-wrap:wrap; }
.note-badge { font-size:0.72rem; padding:3px 9px; border-radius:10px; cursor:pointer; user-select:none; font-weight:bold; transition:0.15s; white-space:nowrap; }
.note-badge:hover { filter:brightness(1.25); }
.note-label { font-size:0.68rem; color:#484f58; margin-right:2px; }
.n-null { background:#1c2128; color:#484f58; border:1px solid #30363d; }
.n-1    { background:rgba(139,148,158,0.18); color:#8b949e; border:1px solid #8b949e55; }
.n-2    { background:rgba(245,158,11,0.18);  color:#f59e0b; border:1px solid #f59e0b55; }
.n-3    { background:rgba(218,41,28,0.18);   color:#da291c; border:1px solid #da291c55; }

/* ── OV groupés ─────────────────────────────────────── */
.ov-group      { border-bottom:2px solid #21262d; }
.ov-group:last-child { border-bottom:none; }
.ov-group-hdr  { display:flex; align-items:center; gap:8px; padding:9px 14px; background:#161b22; border-bottom:1px solid #1c2128; cursor:pointer; transition:background 0.12s; }
.ov-group-hdr:hover { background:#1c2128; }
.ov-group-hdr .sr-id { font-size:0.65rem; }
.ov-group-nom  { color:#c9d1d9; font-weight:bold; font-size:0.83rem; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ov-group-cnt  { font-size:0.7rem; padding:1px 7px; border-radius:10px; background:#21262d; color:#8b949e; flex-shrink:0; }
.btn-quick-ov  { background:transparent; border:1px dashed #30363d; color:#8b949e; padding:2px 9px; border-radius:4px; cursor:pointer; font-size:0.72rem; white-space:nowrap; flex-shrink:0; }
.btn-quick-ov:hover { border-color:#3b82f6; color:#3b82f6; }

/* ── OV items ────────────────────────────────────────── */
.ov-item      { border-bottom:1px solid #1c2128; padding:10px 16px; display:flex; align-items:flex-start; gap:10px; }
.ov-item:last-child { border-bottom:none; }
.ov-id        { font-family:monospace; font-size:0.7rem; background:rgba(59,130,246,0.1); color:#3b82f6; border:1px solid rgba(59,130,246,0.3); padding:2px 6px; border-radius:4px; flex-shrink:0; margin-top:2px; }
.ov-body      { flex:1; min-width:0; }
.ov-desc      { color:#c9d1d9; font-size:0.88rem; line-height:1.5; }
.ov-notes-txt { color:#8b949e; font-size:0.78rem; margin-top:3px; }
.ov-pert      { font-size:0.72rem; padding:2px 8px; border-radius:10px; cursor:pointer; font-weight:bold; white-space:nowrap; flex-shrink:0; margin-top:2px; }
.p-evaluer    { background:rgba(245,158,11,0.15);  color:#f59e0b; border:1px solid #f59e0b55; }
.p-retenu     { background:rgba(34,197,94,0.15);   color:#22c55e; border:1px solid #22c55e55; }
.p-non        { background:rgba(139,148,158,0.12); color:#8b949e; border:1px solid #8b949e44; text-decoration:line-through; }
.ov-del       { background:none; border:none; color:#484f58; cursor:pointer; font-size:0.8rem; padding:2px 4px; flex-shrink:0; margin-top:2px; }
.ov-del:hover { color:#da291c; }
.ov-empty     { padding:10px 16px 12px; color:#484f58; font-size:0.8rem; font-style:italic; }

/* ── Formulaires ─────────────────────────────────────── */
.atl2-form    { padding:14px 16px; background:#161b22; border-top:1px dashed #30363d; }
.atl2-form label { display:block; font-size:0.75rem; color:#8b949e; margin-bottom:3px; }
.atl2-form input, .atl2-form select, .atl2-form textarea {
    width:100%; box-sizing:border-box; background:#0d1117; border:1px solid #30363d;
    color:#c9d1d9; padding:7px 9px; border-radius:4px; font-size:0.85rem;
}
.atl2-form textarea { resize:vertical; height:56px; }
.f-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px; }
.f-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; margin-bottom:10px; }
.f-full   { margin-bottom:10px; }
.btn-add    { background:#3b82f6; border:none; color:#fff; padding:7px 16px; border-radius:4px; cursor:pointer; font-size:0.85rem; }
.btn-cancel { background:#30363d; border:none; color:#8b949e; padding:7px 12px; border-radius:4px; cursor:pointer; font-size:0.85rem; }
.btn-toggle { background:rgba(59,130,246,0.08); border:1px solid rgba(59,130,246,0.4); color:#3b82f6; padding:5px 14px; border-radius:4px; cursor:pointer; font-size:0.82rem; white-space:nowrap; }
.btn-toggle:hover { background:rgba(59,130,246,0.18); border-color:#3b82f6; }

.msg-atl2 { display:none; padding:8px 12px; border-radius:4px; margin-bottom:12px; font-size:0.85rem; }
.ov-filter-banner { padding:8px 16px; font-size:0.8rem; color:#8b949e; background:#0d1117; border-bottom:1px solid #1c2128; display:flex; align-items:center; gap:8px; }
.empty-state { padding:20px; text-align:center; color:#484f58; font-size:0.85rem; font-style:italic; }
</style>

<div style="padding:20px 20px 12px; background:#161b22; border-radius:8px 8px 0 0; border:1px solid #30363d; border-bottom:none; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px;">
    <div>
        <div style="color:#fff; font-size:1.2rem; font-weight:bold;">🔗 Atelier 2 — Sources de Risque & Objectifs Visés</div>
        <div style="color:#8b949e; font-size:0.82rem; margin-top:3px;">Cliquer un badge <strong style="color:#c9d1d9;">M·R·A</strong> pour noter une SR · Cliquer <strong style="color:#c9d1d9;">Pertinence ↻</strong> pour qualifier un OV · Cliquer une SR pour filtrer ses OV</div>
    </div>
    <?php if ($admin_role !== 'lecteur'): ?>
    <button id="btn-seed" onclick="seedEBIOS()" style="background:rgba(245,158,11,0.1); border:1px solid #f59e0b; color:#f59e0b; padding:7px 14px; border-radius:6px; cursor:pointer; font-size:0.82rem; white-space:nowrap;" title="Insérer les 8 SR et 17 OV du référentiel officiel EBIOS RM ANSSI">
        🎲 Pré-remplir EBIOS RM
    </button>
    <?php endif; ?>
</div>
<div style="background:#161b22; border:1px solid #30363d; border-top:none; border-radius:0 0 8px 8px; padding:16px;">
    <div class="msg-atl2" id="msg-atl2"></div>

    <div class="atl2-wrap">

        <!-- ══════════════════════════════════════════════
             COLONNE GAUCHE : Sources de Risque
        ═══════════════════════════════════════════════ -->
        <div class="atl2-left">
            <div class="atl2-panel">
                <div class="atl2-ph">
                    <h3>⚠️ Sources de Risque</h3>
                    <?php if ($admin_role !== 'lecteur'): ?>
                    <button class="btn-toggle" id="btn-toggle-sr" onclick="toggleFormSR()">➕ Ajouter</button>
                    <?php endif; ?>
                </div>

                <?php if ($admin_role !== 'lecteur'): ?>
                <div id="form-sr" style="display:none;" class="atl2-form">
                    <div class="f-full">
                        <label>Type de source *</label>
                        <input type="text" id="sr-type" placeholder="Ex: Cybercriminel, Employé malveillant…">
                    </div>
                    <div class="f-full">
                        <label>Description / motivation (texte libre)</label>
                        <input type="text" id="sr-motiv" placeholder="Ex: Appât du gain, espionnage industriel…">
                    </div>
                    <div class="f-grid-3">
                        <div>
                            <label>Motivation</label>
                            <select id="sr-m-note">
                                <option value="">— nc —</option>
                                <option value="1">1 Faible</option>
                                <option value="2">2 Moyen</option>
                                <option value="3">3 Élevé</option>
                            </select>
                        </div>
                        <div>
                            <label>Ressources</label>
                            <select id="sr-r-note">
                                <option value="">— nc —</option>
                                <option value="1">1 Faible</option>
                                <option value="2">2 Moyen</option>
                                <option value="3">3 Élevé</option>
                            </select>
                        </div>
                        <div>
                            <label>Activité <span style="font-size:0.7rem; color:#484f58;">(opt.)</span></label>
                            <select id="sr-a-note">
                                <option value="">— nc —</option>
                                <option value="1">1 Faible</option>
                                <option value="2">2 Moyen</option>
                                <option value="3">3 Élevé</option>
                            </select>
                        </div>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn-add" onclick="createSR()">Créer</button>
                        <button class="btn-cancel" onclick="toggleFormSR()">Annuler</button>
                    </div>
                </div>
                <?php endif; ?>

                <div class="atl2-body" id="sr-list">
                    <div class="empty-state">Chargement…</div>
                </div>
            </div>
        </div>

        <!-- ══════════════════════════════════════════════
             COLONNE DROITE : Objectifs Visés (groupés par SR)
        ═══════════════════════════════════════════════ -->
        <div class="atl2-right">
            <div class="atl2-panel">
                <div class="atl2-ph">
                    <h3>🎯 Objectifs Visés</h3>
                    <div style="display:flex; gap:6px; align-items:center;">
                        <span id="ov-filter-pill" style="display:none; font-size:0.72rem; background:rgba(59,130,246,0.15); color:#3b82f6; border:1px solid rgba(59,130,246,0.35); padding:2px 8px; border-radius:10px; cursor:pointer;" onclick="clearSRFilter()" title="Annuler le filtre">✕ filtre SR</span>
                        <?php if ($admin_role !== 'lecteur'): ?>
                        <button class="btn-toggle" id="btn-toggle-ov" onclick="toggleFormOV()">➕ Ajouter</button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($admin_role !== 'lecteur'): ?>
                <div id="form-ov" style="display:none;" class="atl2-form">
                    <div class="f-grid-2">
                        <div>
                            <label>Source de Risque *</label>
                            <select id="ov-sr-select">
                                <option value="">— choisir —</option>
                            </select>
                        </div>
                        <div>
                            <label>Pertinence initiale</label>
                            <select id="ov-pert">
                                <option value="A évaluer">À évaluer</option>
                                <option value="Retenu">Retenu</option>
                                <option value="Non retenu">Non retenu</option>
                            </select>
                        </div>
                    </div>
                    <div class="f-full">
                        <label>Description de l'objectif visé *</label>
                        <input type="text" id="ov-desc" placeholder="Ex: Chiffrement des données clients pour extorsion…">
                    </div>
                    <div class="f-full">
                        <label>Notes (optionnel)</label>
                        <input type="text" id="ov-notes" placeholder="Ex: Déjà observé dans le secteur…">
                    </div>
                    <div style="display:flex; gap:8px;">
                        <button class="btn-add" onclick="createOV()">Créer</button>
                        <button class="btn-cancel" onclick="toggleFormOV()">Annuler</button>
                    </div>
                </div>
                <?php endif; ?>

                <div class="atl2-body" id="ov-list">
                    <div class="empty-state">Chargement…</div>
                </div>
            </div>
        </div>

    </div><!-- /atl2-wrap -->
</div>

<script>
(function() {
    var API_SR  = 'api_menaces.php';
    var API_OV  = 'api_objectifs_vises.php';
    var IS_ADMIN = <?= $admin_role === 'admin' ? 'true' : 'false' ?>;
    var CAN_EDIT = <?= $admin_role !== 'lecteur' ? 'true' : 'false' ?>;
    var allSRs  = [];
    var allOVs  = [];
    var selectedSRId = null;

    var NOTE_LABELS = { M: 'Motivation', R: 'Ressources', A: 'Activité' };
    var NOTE_FIELDS = { M: 'motivation_note', R: 'ressources_note', A: 'activite_note' };

    function esc(s) {
        return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showMsg(text, ok) {
        var el = document.getElementById('msg-atl2');
        el.textContent = text;
        el.style.background = ok ? 'rgba(34,197,94,0.15)' : 'rgba(218,41,28,0.15)';
        el.style.color      = ok ? '#22c55e' : '#da291c';
        el.style.border     = '1px solid ' + (ok ? 'rgba(34,197,94,0.3)' : 'rgba(218,41,28,0.3)');
        el.style.display    = 'block';
        setTimeout(function() { el.style.display = 'none'; }, 4000);
    }

    function noteBadge(key, val, srId) {
        var n   = val === null || val === undefined ? null : parseInt(val);
        var cls = n === null ? 'n-null' : 'n-' + n;
        var lbl = n === null ? '—' : n;
        var title = NOTE_LABELS[key];
        if (!CAN_EDIT) {
            return '<span class="note-badge ' + cls + '" title="' + title + '">' +
                '<span class="note-label">' + key + ':</span>' + lbl + '</span>';
        }
        return '<span class="note-badge ' + cls + '" title="' + title + ' — cliquer pour changer" ' +
            'onclick="event.stopPropagation(); cycleNote(' + srId + ',\'' + NOTE_FIELDS[key] + '\',' + (n === null ? 'null' : n) + ')">' +
            '<span class="note-label">' + key + ':</span>' + lbl + '</span>';
    }

    function pertBadge(ov) {
        var map = {
            'A évaluer': { cls: 'p-evaluer', next: 'Retenu',     label: 'À évaluer' },
            'Retenu':    { cls: 'p-retenu',  next: 'Non retenu', label: 'Retenu' },
            'Non retenu':{ cls: 'p-non',     next: 'A évaluer',  label: 'Non retenu' }
        };
        var cfg = map[ov.pertinence] || map['A évaluer'];
        if (!CAN_EDIT) return '<span class="ov-pert ' + cfg.cls + '">' + cfg.label + '</span>';
        return '<span class="ov-pert ' + cfg.cls + '" onclick="cyclePert(' + ov.id + ',\'' + esc(cfg.next) + '\')" title="Cliquer pour changer la pertinence">' +
            cfg.label + ' ↻</span>';
    }

    function ovItem(ov) {
        var ovId   = 'OV-' + String(ov.id).padStart(3, '0');
        var delBtn = IS_ADMIN ? '<button class="ov-del" onclick="deleteOV(' + ov.id + ')" title="Supprimer">🗑</button>' : '';
        return '<div class="ov-item">' +
            '<span class="ov-id">' + esc(ovId) + '</span>' +
            '<div class="ov-body">' +
                '<div class="ov-desc">' + esc(ov.description) + '</div>' +
                (ov.notes ? '<div class="ov-notes-txt">' + esc(ov.notes) + '</div>' : '') +
            '</div>' +
            pertBadge(ov) +
            delBtn +
        '</div>';
    }

    // ── CHARGEMENT ──────────────────────────────────────
    async function load() {
        var [srRes, ovRes] = await Promise.all([
            fetch(API_SR).then(function(r) { return r.json(); }),
            fetch(API_OV).then(function(r) { return r.json(); })
        ]);
        if (srRes.status === 'success') { allSRs = srRes.data; } else { showMsg(srRes.message, false); }
        if (ovRes.status === 'success') { allOVs = ovRes.data; } else { showMsg(ovRes.message, false); }
        renderSRs();
        renderOVs();
        rebuildSRSelect();
    }

    // ── RENDU SR ────────────────────────────────────────
    function renderSRs() {
        var el = document.getElementById('sr-list');
        if (allSRs.length === 0) {
            el.innerHTML = '<div class="empty-state">Aucune source de risque.<br>Cliquez « Ajouter » ou utilisez « Pré-remplir EBIOS RM ».</div>';
            return;
        }
        el.innerHTML = allSRs.map(function(sr) {
            var srId   = 'SR-' + String(sr.id).padStart(3, '0');
            var isAct  = sr.id === selectedSRId;
            var badges = noteBadge('M', sr.motivation_note, sr.id) +
                         noteBadge('R', sr.ressources_note, sr.id) +
                         noteBadge('A', sr.activite_note,   sr.id);
            var delBtn = IS_ADMIN ? '<button class="sr-del" onclick="event.stopPropagation(); deleteSR(' + sr.id + ')" title="Supprimer">🗑</button>' : '';

            // Compteur OV
            var srOVs   = allOVs.filter(function(ov) { return ov.menace_id == sr.id; });
            var retenu  = srOVs.filter(function(ov) { return ov.pertinence === 'Retenu'; }).length;
            var cntCls  = srOVs.length > 0 ? 'ov-cnt has-ov' : 'ov-cnt';
            var cntTxt  = srOVs.length + ' OV' + (retenu > 0 ? ' · <span style="color:#22c55e;">' + retenu + ' ✓</span>' : '');

            return '<div class="sr-card' + (isAct ? ' active' : '') + '" id="sr-card-' + sr.id + '" onclick="selectSR(' + sr.id + ')">' +
                '<div class="sr-card-top">' +
                    '<span class="sr-id">' + esc(srId) + '</span>' +
                    '<span class="sr-nom">' + esc(sr.type_source) + '</span>' +
                    delBtn +
                '</div>' +
                (sr.motivation ? '<div style="font-size:0.78rem; color:#8b949e; margin-bottom:6px; padding-left:2px;">' + esc(sr.motivation) + '</div>' : '') +
                '<div class="sr-foot">' +
                    '<div class="sr-notes">' + badges + '</div>' +
                    '<span class="' + cntCls + '">' + cntTxt + '</span>' +
                '</div>' +
            '</div>';
        }).join('');
    }

    // ── RENDU OV (groupés ou filtrés) ───────────────────
    function renderOVs() {
        var el   = document.getElementById('ov-list');
        var pill = document.getElementById('ov-filter-pill');

        if (selectedSRId !== null) {
            // Mode filtre : une seule SR sélectionnée
            pill.style.display = 'inline-block';
            var filtered = allOVs.filter(function(ov) { return ov.menace_id == selectedSRId; });
            if (filtered.length === 0) {
                el.innerHTML = '<div class="ov-empty" style="padding:20px; text-align:center;">Aucun OV pour cette SR.' +
                    (CAN_EDIT ? ' Cliquez « Ajouter » pour en créer un.' : '') + '</div>';
            } else {
                el.innerHTML = filtered.map(ovItem).join('');
            }
        } else {
            // Mode global : grouper par SR
            pill.style.display = 'none';
            if (allSRs.length === 0) {
                el.innerHTML = '<div class="empty-state">Créez d\'abord des Sources de Risque.</div>';
                return;
            }
            var html = '';
            allSRs.forEach(function(sr) {
                var srId   = 'SR-' + String(sr.id).padStart(3, '0');
                var srOVs  = allOVs.filter(function(ov) { return ov.menace_id == sr.id; });
                var retenu = srOVs.filter(function(ov) { return ov.pertinence === 'Retenu'; }).length;
                var cntLbl = srOVs.length + ' OV' + (retenu > 0 ? ' · ' + retenu + ' retenus' : '');
                var quickBtn = CAN_EDIT
                    ? '<button class="btn-quick-ov" onclick="event.stopPropagation(); quickAddOV(' + sr.id + ')" title="Ajouter un OV pour cette SR">+ OV</button>'
                    : '';

                html += '<div class="ov-group">';
                html += '<div class="ov-group-hdr" onclick="selectSR(' + sr.id + ')" title="Filtrer sur cette SR">' +
                    '<span class="sr-id">' + esc(srId) + '</span>' +
                    '<span class="ov-group-nom">' + esc(sr.type_source) + '</span>' +
                    '<span class="ov-group-cnt">' + cntLbl + '</span>' +
                    quickBtn +
                '</div>';

                if (srOVs.length === 0) {
                    html += '<div class="ov-empty">Aucun objectif visé.' +
                        (CAN_EDIT ? ' Cliquez <strong>+ OV</strong> pour en ajouter un.' : '') + '</div>';
                } else {
                    html += srOVs.map(ovItem).join('');
                }
                html += '</div>';
            });
            el.innerHTML = html;
        }
    }

    function rebuildSRSelect(preselectId) {
        var sel = document.getElementById('ov-sr-select');
        if (!sel) return;
        sel.innerHTML = '<option value="">— choisir —</option>';
        allSRs.forEach(function(sr) {
            var opt = document.createElement('option');
            opt.value = sr.id;
            opt.textContent = 'SR-' + String(sr.id).padStart(3,'0') + ' — ' + sr.type_source;
            if (preselectId && sr.id == preselectId) opt.selected = true;
            else if (!preselectId && sr.id === selectedSRId) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    // ── FILTRAGE / NAVIGATION ────────────────────────────
    window.selectSR = function(id) {
        selectedSRId = (selectedSRId === id) ? null : id;
        renderSRs();
        renderOVs();
    };

    window.clearSRFilter = function() {
        selectedSRId = null;
        renderSRs();
        renderOVs();
    };

    // Ouvrir le form OV pré-sélectionné sur une SR
    window.quickAddOV = function(srId) {
        var f = document.getElementById('form-ov');
        if (f) {
            f.style.display = 'block';
            rebuildSRSelect(srId);
            f.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    };

    // ── NOTES SR ────────────────────────────────────────
    window.cycleNote = async function(srId, field, current) {
        var next = (current === null) ? 1 : (current >= 3 ? null : current + 1);
        var res  = await fetch(API_SR, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: srId, field: field, value: next })
        });
        var json = await res.json();
        if (json.status === 'success') {
            var sr = allSRs.find(function(s) { return s.id === srId; });
            if (sr) sr[field] = next;
            renderSRs();
        } else {
            showMsg(json.message, false);
        }
    };

    // ── PERTINENCE OV ────────────────────────────────────
    window.cyclePert = async function(ovId, next) {
        var res  = await fetch(API_OV, {
            method: 'PATCH',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ id: ovId, pertinence: next })
        });
        var json = await res.json();
        if (json.status === 'success') {
            var ov = allOVs.find(function(o) { return o.id === ovId; });
            if (ov) ov.pertinence = (next === 'A évaluer') ? 'A évaluer' : next;
            renderOVs();
            renderSRs(); // met à jour les compteurs
        } else {
            showMsg(json.message, false);
        }
    };

    // ── CRUD SR ─────────────────────────────────────────
    window.createSR = async function() {
        var type = document.getElementById('sr-type').value.trim();
        if (!type) { showMsg('Le type de source est obligatoire.', false); return; }
        var mn = document.getElementById('sr-m-note').value;
        var rn = document.getElementById('sr-r-note').value;
        var an = document.getElementById('sr-a-note').value;
        var payload = {
            type_source:     type,
            motivation:      document.getElementById('sr-motiv').value.trim(),
            motivation_note: mn ? parseInt(mn) : undefined,
            ressources_note: rn ? parseInt(rn) : undefined,
            activite_note:   an ? parseInt(an) : undefined
        };
        var res  = await fetch(API_SR, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        var json = await res.json();
        showMsg(json.message, json.status === 'success');
        if (json.status === 'success') {
            document.getElementById('sr-type').value = '';
            document.getElementById('sr-motiv').value = '';
            document.getElementById('sr-m-note').value = '';
            document.getElementById('sr-r-note').value = '';
            document.getElementById('sr-a-note').value = '';
            load();
        }
    };

    window.deleteSR = async function(id) {
        if (!confirm('Supprimer cette Source de Risque et tous ses Objectifs Visés associés ?')) return;
        var res  = await fetch(API_SR, { method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
        var json = await res.json();
        showMsg(json.message, json.status === 'success');
        if (json.status === 'success') {
            if (selectedSRId === id) selectedSRId = null;
            load();
        }
    };

    // ── CRUD OV ─────────────────────────────────────────
    window.createOV = async function() {
        var menace_id   = parseInt(document.getElementById('ov-sr-select').value);
        var description = document.getElementById('ov-desc').value.trim();
        if (!menace_id || !description) { showMsg('La SR et la description sont obligatoires.', false); return; }
        var payload = {
            menace_id,
            description,
            pertinence: document.getElementById('ov-pert').value,
            notes:      document.getElementById('ov-notes').value.trim()
        };
        var res  = await fetch(API_OV, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload) });
        var json = await res.json();
        showMsg(json.message, json.status === 'success');
        if (json.status === 'success') {
            document.getElementById('ov-desc').value  = '';
            document.getElementById('ov-notes').value = '';
            load();
        }
    };

    window.deleteOV = async function(id) {
        if (!confirm('Supprimer cet Objectif Visé ?')) return;
        var res  = await fetch(API_OV, { method:'DELETE', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id}) });
        var json = await res.json();
        showMsg(json.message, json.status === 'success');
        if (json.status === 'success') load();
    };

    // ── TOGGLE FORMS ─────────────────────────────────────
    window.toggleFormSR = function() {
        var f = document.getElementById('form-sr');
        f.style.display = f.style.display === 'none' ? 'block' : 'none';
    };

    window.toggleFormOV = function() {
        var f = document.getElementById('form-ov');
        if (f.style.display === 'none') {
            f.style.display = 'block';
            rebuildSRSelect();
        } else {
            f.style.display = 'none';
        }
    };

    // ── SEED EBIOS RM ────────────────────────────────────
    window.seedEBIOS = async function() {
        var hasSR   = allSRs.length > 0;
        var replace = false;
        if (hasSR) {
            var choice = confirm(
                'Des Sources de Risque existent déjà (' + allSRs.length + ' SR).\n\n' +
                'OK = REMPLACER toutes les SR et OV par le référentiel EBIOS RM officiel.\n' +
                'Annuler = ajouter seulement les SR manquantes (sans doublon).'
            );
            if (choice === null) return;
            replace = choice;
        } else {
            if (!confirm('Pré-remplir avec les 8 SR et 17 OV officiels du référentiel EBIOS RM (ANSSI) ?')) return;
        }
        var btn = document.getElementById('btn-seed');
        if (btn) { btn.disabled = true; btn.textContent = '⏳ Insertion…'; }
        try {
            var res  = await fetch('api_seed_atelier2.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ replace: replace })
            });
            var json = await res.json();
            showMsg(json.message, json.status === 'success');
            if (json.status === 'success') { selectedSRId = null; load(); }
        } catch(e) {
            showMsg('Erreur réseau.', false);
        } finally {
            if (btn) { btn.disabled = false; btn.textContent = '🎲 Pré-remplir EBIOS RM'; }
        }
    };

    load();
})();
</script>
