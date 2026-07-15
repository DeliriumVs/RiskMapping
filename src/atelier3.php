<?php
// src/atelier3.php — Atelier 3 : Parties Prenantes & Scénarios Stratégiques
session_start();
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("<div style='color:red;padding:20px;'>Accès refusé.</div>");
}
?>
<style>
/* ── Tabs ─────────────────────────────────────────────── */
.a3-tabs   { display:flex; gap:0; border-bottom:2px solid #30363d; margin-bottom:20px; }
.a3-tab    { padding:10px 20px; cursor:pointer; color:#8b949e; font-size:0.9rem; border-bottom:2px solid transparent; margin-bottom:-2px; transition:0.15s; user-select:none; }
.a3-tab:hover  { color:#c9d1d9; }
.a3-tab.active { color:#3b82f6; border-bottom-color:#3b82f6; font-weight:bold; }
.a3-pane   { display:none; }
.a3-pane.active { display:block; }

/* ── PP table ─────────────────────────────────────────── */
.pp-table  { width:100%; border-collapse:collapse; font-size:0.82rem; }
.pp-table th { padding:8px 10px; text-align:left; color:#8b949e; font-size:0.75rem; background:#21262d; border-bottom:2px solid #30363d; white-space:nowrap; }
.pp-table td { padding:8px 10px; border-bottom:1px solid #1c2128; vertical-align:middle; }
.pp-table tr:hover td { background:rgba(255,255,255,0.02); }

.pp-badge  { font-family:monospace; font-size:0.7rem; background:rgba(167,139,250,0.12); color:#a78bfa; border:1px solid rgba(167,139,250,0.35); padding:2px 7px; border-radius:4px; white-space:nowrap; }
.type-badge { font-size:0.72rem; padding:2px 7px; border-radius:10px; white-space:nowrap; }
.t-Int  { background:rgba(34,197,94,0.12); color:#22c55e; border:1px solid rgba(34,197,94,0.3); }
.t-Ext  { background:rgba(59,130,246,0.12); color:#3b82f6; border:1px solid rgba(59,130,246,0.3); }
.t-Par  { background:rgba(245,158,11,0.12); color:#f59e0b; border:1px solid rgba(245,158,11,0.3); }
.t-Fou  { background:rgba(239,68,68,0.12);  color:#ef4444; border:1px solid rgba(239,68,68,0.3); }
.t-Cli  { background:rgba(6,182,212,0.12);  color:#06b6d4; border:1px solid rgba(6,182,212,0.3); }
.t-Pre  { background:rgba(168,85,247,0.12); color:#a855f7; border:1px solid rgba(168,85,247,0.3); }
.t-Aut  { background:rgba(100,116,139,0.12);color:#64748b; border:1px solid rgba(100,116,139,0.3); }

/* Critère select compact */
.crit-sel { background:#0d1117; border:1px solid #30363d; color:#c9d1d9; padding:3px 4px; border-radius:4px; font-size:0.78rem; width:100%; cursor:pointer; }
.crit-sel:hover { border-color:#8b949e; }

/* Badge niveau calculé — progression vert → jaune → orange → rouge */
.lvl-badge { font-size:0.72rem; padding:3px 9px; border-radius:10px; font-weight:bold; white-space:nowrap; display:inline-block; }
.lvl-1 { background:rgba(34,197,94,0.14);  color:#4ade80; border:1px solid rgba(34,197,94,0.4);  }
.lvl-2 { background:rgba(234,179,8,0.15);  color:#facc15; border:1px solid rgba(234,179,8,0.45); }
.lvl-3 { background:rgba(245,158,11,0.15); color:#f59e0b; border:1px solid rgba(245,158,11,0.5); }
.lvl-4 { background:rgba(218,41,28,0.20);  color:#ff4444; border:1px solid rgba(218,41,28,0.55); font-weight:900; }

/* ── Matrice ──────────────────────────────────────────── */
.matrix-section { display:flex; flex-direction:column; align-items:center; }
.matrix-top     { display:flex; align-items:flex-start; gap:32px; justify-content:center; }
.matrix-legend  { display:flex; flex-direction:column; justify-content:center; min-width:200px; padding-top:8px; }
.matrix-area    { flex:0 0 auto; }
.matrix-grid    { display:grid; grid-template-columns:repeat(4,130px); grid-template-rows:repeat(4,100px); gap:2px; background:#30363d; border:2px solid #30363d; border-radius:4px; }
.m-cell  { display:flex; flex-direction:column; align-items:center; justify-content:center; padding:6px; gap:4px; flex-wrap:wrap; position:relative; }
.m-bg-0  { background:#0d1117; }
.m-bg-1  { background:rgba(34,197,94,0.10);   }
.m-bg-2  { background:rgba(234,179,8,0.12);   }
.m-bg-3  { background:rgba(245,158,11,0.18);  }
.m-bg-4  { background:rgba(218,41,28,0.22);   }
.m-pp-dot { font-size:0.72rem; padding:3px 7px; border-radius:4px; font-weight:bold; cursor:default; }

.matrix-y { display:flex; flex-direction:column; justify-content:space-between; height:406px; padding:0 10px 0 0; text-align:right; color:#8b949e; font-size:0.78rem; align-items:flex-end; }
.matrix-x { display:grid; grid-template-columns:repeat(4,130px); text-align:center; color:#8b949e; font-size:0.78rem; padding-top:8px; gap:2px; }
.ml-item  { display:flex; align-items:center; gap:10px; margin-bottom:12px; font-size:0.85rem; color:#c9d1d9; }
.ml-dot   { width:36px; height:18px; border-radius:4px; flex-shrink:0; }
.matrix-bottom { display:flex; gap:24px; justify-content:center; flex-wrap:wrap; margin-top:28px; padding-top:20px; border-top:1px solid #21262d; width:100%; }

/* ── Scénarios Stratégiques ───────────────────────────── */
.ss-card  { background:#0d1117; border:1px solid #30363d; border-radius:6px; padding:14px 16px; margin-bottom:10px; display:flex; align-items:flex-start; gap:12px; }
.ss-card:hover { border-color:#484f58; }
.ss-id    { font-family:monospace; font-size:0.7rem; color:#8b949e; white-space:nowrap; margin-top:2px; }
.ss-body  { flex:1; min-width:0; }
.ss-path  { display:flex; align-items:center; gap:6px; flex-wrap:wrap; margin-bottom:6px; }
.ss-arrow { color:#484f58; font-size:0.9rem; }
.sr-chip  { font-size:0.72rem; padding:2px 8px; border-radius:10px; background:rgba(218,41,28,0.12); color:#da291c; border:1px solid rgba(218,41,28,0.3); }
.pp-chip  { font-size:0.72rem; padding:2px 8px; border-radius:10px; background:rgba(167,139,250,0.12); color:#a78bfa; border:1px solid rgba(167,139,250,0.3); }
.ov-chip  { font-size:0.72rem; padding:2px 8px; border-radius:10px; background:rgba(59,130,246,0.12); color:#60a5fa; border:1px solid rgba(59,130,246,0.3); }
.ss-desc  { color:#8b949e; font-size:0.82rem; margin-top:4px; font-style:italic; }
.ss-right { display:flex; flex-direction:column; align-items:flex-end; gap:6px; flex-shrink:0; }
.ss-stat  { font-size:0.72rem; padding:3px 9px; border-radius:10px; cursor:pointer; font-weight:bold; white-space:nowrap; }
.ss-a { background:rgba(245,158,11,0.15); color:#f59e0b; border:1px solid #f59e0b44; }
.ss-r { background:rgba(34,197,94,0.15);  color:#22c55e; border:1px solid #22c55e44; }
.ss-n { background:rgba(139,148,158,0.12);color:#8b949e; border:1px solid #8b949e44; }
.ss-del { background:none; border:none; color:#484f58; cursor:pointer; font-size:0.8rem; padding:2px 4px; }
.ss-del:hover { color:#da291c; }
.pp-assign-sel { background:#161b22; border:1px solid rgba(167,139,250,0.4); color:#a78bfa; padding:3px 6px; border-radius:4px; font-size:0.72rem; cursor:pointer; }

/* ── Formulaires ──────────────────────────────────────── */
.a3-form  { background:#0d1117; border:1px solid #30363d; border-radius:8px; padding:16px; margin-bottom:16px; display:none; }
.a3-form.open { display:block; }
.fg { display:grid; gap:10px; margin-bottom:12px; }
.fg-2 { grid-template-columns:1fr 1fr; }
.fg-3 { grid-template-columns:1fr 1fr 1fr; }
.fg-4 { grid-template-columns:1fr 1fr 1fr 1fr; }
label.a3 { display:block; font-size:0.75rem; color:#8b949e; margin-bottom:4px; }
.a3-form input, .a3-form select, .a3-form textarea {
    width:100%; box-sizing:border-box; background:#161b22; border:1px solid #30363d;
    color:#c9d1d9; padding:8px; border-radius:4px; font-size:0.85rem;
}
.a3-form textarea { resize:vertical; height:60px; }
.btn-prim { background:#3b82f6; border:none; color:#fff; padding:8px 16px; border-radius:4px; cursor:pointer; font-size:0.85rem; }
.btn-sec  { background:#30363d; border:none; color:#8b949e; padding:8px 14px; border-radius:4px; cursor:pointer; font-size:0.85rem; }
.btn-open { background:transparent; border:1px solid #30363d; color:#8b949e; padding:5px 12px; border-radius:4px; cursor:pointer; font-size:0.8rem; }
.btn-open:hover { border-color:#3b82f6; color:#3b82f6; }

.msg-a3 { display:none; padding:8px 12px; border-radius:4px; margin-bottom:12px; font-size:0.85rem; }
</style>

<div style="padding:20px 20px 12px; background:#161b22; border-radius:8px 8px 0 0; border:1px solid #30363d; border-bottom:none;">
    <div style="color:#fff; font-size:1.2rem; font-weight:bold;">🗺️ Atelier 3 — Parties Prenantes & Scénarios Stratégiques</div>
    <div style="color:#8b949e; font-size:0.82rem; margin-top:3px;">Exposition (Dépendance × Pénétration) · Fiabilité (Maturité × Confiance) → Niveau de menace · Scénarios SR × PP → OV</div>
</div>
<div style="background:#161b22; border:1px solid #30363d; border-top:none; border-radius:0 0 8px 8px; padding:16px 20px 20px;">

    <div class="msg-a3" id="msg-a3"></div>

    <!-- Tabs -->
    <div class="a3-tabs">
        <div class="a3-tab active" onclick="switchTab('pp')">👥 Parties Prenantes</div>
        <div class="a3-tab"       onclick="switchTab('matrix')">📊 Matrice Exposition</div>
        <div class="a3-tab"       onclick="switchTab('ss')">⚡ Scénarios Stratégiques</div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         TAB 1 : Parties Prenantes
    ════════════════════════════════════════════════════════════════ -->
    <div class="a3-pane active" id="pane-pp">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div style="color:#8b949e; font-size:0.82rem;">
                Les critères (1→4) modifiables directement dans le tableau — le niveau de menace est recalculé immédiatement.
            </div>
            <?php if ($admin_role !== 'lecteur'): ?>
            <button class="btn-open" onclick="toggleForm('form-pp')">➕ Ajouter une PP</button>
            <?php endif; ?>
        </div>

        <!-- Formulaire ajout PP -->
        <div class="a3-form" id="form-pp">
            <div class="fg fg-2" style="margin-bottom:10px;">
                <div><label class="a3">Nom *</label><input type="text" id="pp-nom" placeholder="Ex: Prestataire infogérance DSI…"></div>
                <div><label class="a3">Type</label>
                    <select id="pp-type">
                        <option>Externe</option><option>Interne</option><option>Partenaire</option>
                        <option>Fournisseur</option><option>Client</option><option>Prestataire</option><option>Autre</option>
                    </select>
                </div>
                <div style="grid-column:1/-1;"><label class="a3">Description (optionnel)</label><input type="text" id="pp-desc" placeholder="Ex: Accès VPN pour maintenance applicative"></div>
            </div>
            <div class="fg fg-4" style="margin-bottom:12px;">
                <div><label class="a3">Dépendance</label>
                    <select id="pp-dep"><option value="1">1 — Faible</option><option value="2">2 — Moyenne</option><option value="3">3 — Forte</option><option value="4">4 — Critique</option></select>
                </div>
                <div><label class="a3">Pénétration</label>
                    <select id="pp-pen"><option value="1">1 — Très limitée</option><option value="2">2 — Partielle</option><option value="3">3 — Significative</option><option value="4">4 — Totale</option></select>
                </div>
                <div><label class="a3">Maturité Cyber</label>
                    <select id="pp-mat"><option value="1">1 — Faible</option><option value="2">2 — Limitée</option><option value="3">3 — Correcte</option><option value="4">4 — Avancée</option></select>
                </div>
                <div><label class="a3">Confiance</label>
                    <select id="pp-conf"><option value="1">1 — Inconnue</option><option value="2">2 — Limitée</option><option value="3">3 — Établie</option><option value="4">4 — Forte</option></select>
                </div>
            </div>
            <div style="display:flex; gap:8px;">
                <button class="btn-prim" onclick="createPP()">Créer</button>
                <button class="btn-sec" onclick="toggleForm('form-pp')">Annuler</button>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table class="pp-table">
                <thead>
                    <tr>
                        <th>ID</th><th>Nom</th><th>Type</th>
                        <th title="Niveau de dépendance de l'organisation vis-à-vis de cette Partie Prenante">Dépendance</th>
                        <th title="Niveau de pénétration / d'accès de la Partie Prenante dans le système d'information">Pénétration</th>
                        <th title="Résulte de Dépendance × Pénétration">Exposition</th>
                        <th title="Maturité en cybersécurité de la Partie Prenante">Maturité cyber</th>
                        <th title="Niveau de confiance accordé à la Partie Prenante">Confiance</th>
                        <th title="Moyenne de Maturité cyber et Confiance">Fiabilité</th>
                        <th title="Niveau de menace que représente cette PP, combinant Exposition et Fiabilité">Niveau de menace</th>
                        <?php if ($admin_role !== 'lecteur'): ?><th></th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="pp-tbody">
                    <tr><td colspan="11" style="text-align:center;padding:20px;color:#8b949e;">Chargement…</td></tr>
                </tbody>
            </table>
        </div>

        <!-- Légende des critères -->
        <div style="margin-top:16px; display:grid; grid-template-columns:1fr 1fr; gap:16px; font-size:0.78rem; color:#8b949e;">
            <div>
                <strong style="color:#c9d1d9;">Dépendance</strong> : 1 Faible · 2 Moyenne · 3 Forte · 4 Critique<br>
                <strong style="color:#c9d1d9;">Pénétration</strong> : 1 Très limitée · 2 Partielle · 3 Significative · 4 Totale
            </div>
            <div>
                <strong style="color:#c9d1d9;">Maturité Cyber</strong> : 1 Faible · 2 Limitée · 3 Correcte · 4 Avancée<br>
                <strong style="color:#c9d1d9;">Confiance</strong> : 1 Inconnue · 2 Limitée · 3 Établie · 4 Forte
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         TAB 2 : Matrice Exposition
    ════════════════════════════════════════════════════════════════ -->
    <div class="a3-pane" id="pane-matrix">
        <div class="matrix-section">
            <!-- Légende (gauche) + Matrice (droite) -->
            <div class="matrix-top">
                <div class="matrix-legend">
                    <h4 style="color:#c9d1d9; margin:0 0 16px 0; font-size:0.95rem;">Niveau de menace</h4>
                    <div class="ml-item"><div class="ml-dot lvl-4"></div>4 — Critique</div>
                    <div class="ml-item"><div class="ml-dot lvl-3"></div>3 — Important</div>
                    <div class="ml-item"><div class="ml-dot lvl-2"></div>2 — Limité</div>
                    <div class="ml-item"><div class="ml-dot lvl-1"></div>1 — Négligeable</div>
                    <div style="margin-top:20px; color:#484f58; font-size:0.78rem; line-height:1.7; max-width:200px;">
                        La cellule indique le niveau d'exposition brut (Dépendance × Pénétration).<br>
                        La couleur du badge tient compte de la fiabilité de la PP.
                    </div>
                </div>

                <div class="matrix-area">
                    <div style="display:flex; align-items:center;">
                        <div style="height:406px; display:flex; flex-direction:column; align-items:center; justify-content:center; margin-right:10px; flex-shrink:0; gap:6px;">
                            <span style="color:#8b949e; font-size:1rem; line-height:1;">↑</span>
                            <span style="writing-mode:vertical-rl; transform:rotate(180deg); color:#8b949e; font-size:0.8rem; font-weight:bold; white-space:nowrap; letter-spacing:0.04em;">Dépendance</span>
                        </div>
                        <div class="matrix-y">
                            <div>4 Critique</div><div>3 Forte</div><div>2 Moyenne</div><div>1 Faible</div>
                        </div>
                        <div>
                            <div class="matrix-grid" id="matrix-grid"></div>
                            <div class="matrix-x">
                                <div>1<br>Très limitée</div><div>2<br>Partielle</div><div>3<br>Significative</div><div>4<br>Totale</div>
                            </div>
                            <div style="text-align:center; color:#8b949e; font-size:0.8rem; font-weight:bold; margin-top:6px;">Pénétration →</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PP dans cette analyse (dessous) -->
            <div class="matrix-bottom">
                <div>
                    <div style="color:#8b949e; font-size:0.78rem; font-weight:bold; margin-bottom:10px; text-transform:uppercase; letter-spacing:0.05em;">PP dans cette analyse</div>
                    <div id="matrix-legend-pps" style="display:flex; flex-wrap:wrap; gap:8px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         TAB 3 : Scénarios Stratégiques
    ════════════════════════════════════════════════════════════════ -->
    <div class="a3-pane" id="pane-ss">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <p style="margin:0; color:#8b949e; font-size:0.82rem;">
                Un scénario stratégique = une SR qui exploite (optionnellement via une PP) un OV (retenu en Atelier 2).
            </p>
            <?php if ($admin_role !== 'lecteur'): ?>
            <div style="display:flex; gap:8px;">
                <button class="btn-open" onclick="importFromA2()" title="Générer automatiquement un scénario pour chaque OV retenu en Atelier 2">⚡ Générer depuis A2</button>
                <button class="btn-open" onclick="toggleForm('form-ss')">➕ Ajouter un scénario</button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Formulaire ajout SS -->
        <div class="a3-form" id="form-ss">
            <div class="fg fg-3" style="margin-bottom:10px;">
                <div><label class="a3">Source de Risque *</label><select id="ss-sr"><option value="">— choisir —</option></select></div>
                <div><label class="a3">Partie Prenante (optionnel)</label><select id="ss-pp"><option value="">— aucune / à définir —</option></select></div>
                <div><label class="a3">Objectif Visé (retenu)</label><select id="ss-ov"><option value="">— aucun / à définir —</option></select></div>
            </div>
            <div class="fg fg-2" style="margin-bottom:10px;">
                <div><label class="a3">Gravité initiale</label>
                    <select id="ss-grav"><option value="0">— nc —</option><option value="1">1 Critique</option><option value="2">2 Grave</option><option value="3">3 Significative</option><option value="4">4 Mineure</option></select>
                </div>
                <div><label class="a3">Vraisemblance initiale</label>
                    <select id="ss-vrai"><option value="0">— nc —</option><option value="1">1 Très faible</option><option value="2">2 Faible</option><option value="3">3 Élevée</option><option value="4">4 Très élevée</option></select>
                </div>
            </div>
            <div class="fg" style="margin-bottom:10px;">
                <div><label class="a3">Description du scénario (optionnel)</label>
                    <textarea id="ss-desc" placeholder="Ex: Le prestataire infogérance compromis par ransomware réalise un mouvement latéral vers les serveurs de facturation…"></textarea>
                </div>
            </div>
            <div style="display:flex; gap:8px;">
                <button class="btn-prim" onclick="createSS()">Créer</button>
                <button class="btn-sec" onclick="toggleForm('form-ss')">Annuler</button>
            </div>
        </div>

        <div id="ss-list"><div style="text-align:center;color:#8b949e;padding:30px;">Chargement…</div></div>
    </div>

</div>

<script>
(function() {
    var API_PP = 'api_parties_prenantes.php';
    var API_SS = 'api_scenarios_strategiques.php';
    var IS_ADMIN = <?= $admin_role === 'admin' ? 'true' : 'false' ?>;
    var CAN_EDIT = <?= $admin_role !== 'lecteur' ? 'true' : 'false' ?>;
    var allPPs   = [];
    var activeTab = 'pp';

    // ── Matrices JS (miroir de l'API PHP) ─────────────────────
    var EXPO = {
        1:{1:1,2:1,3:2,4:2}, 2:{1:1,2:2,3:2,4:3},
        3:{1:2,2:2,3:3,4:3}, 4:{1:2,2:3,3:3,4:4}
    };
    var MEN = {
        1:{1:2,2:1,3:1,4:1}, 2:{1:3,2:2,3:1,4:1},
        3:{1:4,2:3,3:2,4:1}, 4:{1:4,2:4,3:3,4:2}
    };

    function computePP(pp) {
        var dep=pp.dependance, pen=pp.penetration, mat=pp.maturite_cyber, conf=pp.confiance;
        var expo = EXPO[dep][pen];
        var fiab = Math.max(1, Math.min(4, Math.round((+mat + +conf) / 2)));
        return { expo: expo, fiab: fiab, menace: MEN[expo][fiab] };
    }

    var MEN_LABELS = {1:'Négligeable', 2:'Limité', 3:'Important', 4:'Critique'};
    var EXP_LABELS = {1:'Faible', 2:'Modérée', 3:'Forte', 4:'Critique'};
    var FIA_LABELS = {1:'Faible', 2:'Modérée', 3:'Correcte', 4:'Forte'};

    function esc(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

    function lvlBadge(n, labelMap) {
        return '<span class="lvl-badge lvl-' + n + '">' + (labelMap[n] || n) + '</span>';
    }

    function typeClass(t) {
        var m = {Interne:'t-Int',Externe:'t-Ext',Partenaire:'t-Par',Fournisseur:'t-Fou',Client:'t-Cli',Prestataire:'t-Pre',Autre:'t-Aut'};
        return m[t] || 't-Aut';
    }

    function showMsg(txt, ok) {
        var el = document.getElementById('msg-a3');
        el.textContent = txt;
        el.style.background = ok ? 'rgba(34,197,94,0.15)' : 'rgba(218,41,28,0.15)';
        el.style.color      = ok ? '#22c55e' : '#da291c';
        el.style.border     = '1px solid ' + (ok ? 'rgba(34,197,94,0.3)' : 'rgba(218,41,28,0.3)');
        el.style.display    = 'block';
        setTimeout(function() { el.style.display='none'; }, 4000);
    }

    // ── TAB SWITCH ─────────────────────────────────────────────
    window.switchTab = function(name) {
        activeTab = name;
        document.querySelectorAll('.a3-tab').forEach(function(t,i) {
            t.classList.toggle('active', ['pp','matrix','ss'][i] === name);
        });
        document.querySelectorAll('.a3-pane').forEach(function(p) { p.classList.remove('active'); });
        document.getElementById('pane-'+name).classList.add('active');
        if (name === 'matrix') renderMatrix();
        if (name === 'ss') loadSS();
    };

    window.toggleForm = function(id) {
        var f = document.getElementById(id);
        f.classList.toggle('open');
    };

    // ── PP ──────────────────────────────────────────────────────
    async function loadPP() {
        try {
            var res  = await fetch(API_PP);
            var json = await res.json();
            if (json.status !== 'success') {
                var tb = document.getElementById('pp-tbody');
                if (tb) tb.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;color:#f59e0b;">⚠ ' + (json.message || 'Erreur serveur') + '<br><small style="color:#8b949e;">Si les tables n\'existent pas encore, relancez Docker : docker compose down -v &amp;&amp; docker compose up -d</small></td></tr>';
                return;
            }
            allPPs = json.data;
        } catch(e) {
            var tb = document.getElementById('pp-tbody');
            if (tb) tb.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;color:#da291c;">Erreur de connexion à l\'API Parties Prenantes.</td></tr>';
            return;
        }
        renderPP();
    }

    function renderPP() {
        var tb = document.getElementById('pp-tbody');
        if (allPPs.length === 0) {
            tb.innerHTML = '<tr><td colspan="11" style="text-align:center;padding:20px;color:#8b949e;">Aucune Partie Prenante. Cliquez « Ajouter ».</td></tr>';
            return;
        }

        var DEP_OPT = ['','1 — Faible','2 — Moyenne','3 — Forte','4 — Critique'];
        var PEN_OPT = ['','1 — Très limitée','2 — Partielle','3 — Significative','4 — Totale'];
        var MAT_OPT = ['','1 — Faible','2 — Limitée','3 — Correcte','4 — Avancée'];
        var CON_OPT = ['','1 — Inconnue','2 — Limitée','3 — Établie','4 — Forte'];

        function critSel(ppId, field, val, opts) {
            if (!CAN_EDIT) return '<span style="color:#c9d1d9;">' + val + '</span>';
            var o = '';
            for (var i=1;i<=4;i++) o += '<option value="'+i+'"'+(i==val?' selected':'')+'>'+i+'</option>';
            return '<select class="crit-sel" onchange="patchPP('+ppId+',\''+field+'\',this.value)">'+o+'</select>';
        }

        tb.innerHTML = allPPs.map(function(pp) {
            var c   = computePP(pp);
            var ppId = 'PP-' + String(pp.id).padStart(3,'0');
            var del = IS_ADMIN ? '<button onclick="deletePP('+pp.id+')" style="background:none;border:none;color:#484f58;cursor:pointer;font-size:0.85rem;" title="Supprimer">🗑️</button>' : '';
            return '<tr>' +
                '<td><span class="pp-badge">' + esc(ppId) + '</span></td>' +
                '<td style="color:#fff; font-weight:bold; max-width:180px;">' + esc(pp.nom) +
                    (pp.description ? '<br><span style="font-size:0.75rem;color:#8b949e;font-weight:normal;">' + esc(pp.description) + '</span>' : '') +
                '</td>' +
                '<td><span class="type-badge ' + typeClass(pp.type_pp) + '">' + esc(pp.type_pp) + '</span></td>' +
                '<td>' + critSel(pp.id,'dependance',   pp.dependance,    DEP_OPT) + '</td>' +
                '<td>' + critSel(pp.id,'penetration',  pp.penetration,   PEN_OPT) + '</td>' +
                '<td style="text-align:center;">' + lvlBadge(c.expo, EXP_LABELS) + '</td>' +
                '<td>' + critSel(pp.id,'maturite_cyber',pp.maturite_cyber,MAT_OPT) + '</td>' +
                '<td>' + critSel(pp.id,'confiance',    pp.confiance,     CON_OPT) + '</td>' +
                '<td style="text-align:center;">' + lvlBadge(c.fiab, FIA_LABELS) + '</td>' +
                '<td style="text-align:center;">' + lvlBadge(c.menace, MEN_LABELS) + '</td>' +
                (CAN_EDIT ? '<td>' + del + '</td>' : '') +
            '</tr>';
        }).join('');
    }

    window.patchPP = async function(id, field, value) {
        var res  = await fetch(API_PP, { method:'PATCH', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id,field,value:parseInt(value)}) });
        var json = await res.json();
        if (json.status === 'success' && json.computed) {
            var pp = allPPs.find(function(p) { return p.id == id; });
            if (pp) {
                pp[field] = parseInt(value);
                pp.exposition_calc    = json.computed.exposition_calc;
                pp.fiabilite_calc     = json.computed.fiabilite_calc;
                pp.niveau_menace_calc = json.computed.niveau_menace_calc;
            }
            renderPP();
            if (activeTab === 'matrix') renderMatrix();
        } else if (json.status !== 'success') {
            showMsg(json.message, false);
        }
    };

    window.createPP = async function() {
        var nom = document.getElementById('pp-nom').value.trim();
        if (!nom) { showMsg('Le nom est obligatoire.', false); return; }
        var payload = {
            nom, type_pp: document.getElementById('pp-type').value,
            description:   document.getElementById('pp-desc').value.trim(),
            dependance:    parseInt(document.getElementById('pp-dep').value),
            penetration:   parseInt(document.getElementById('pp-pen').value),
            maturite_cyber:parseInt(document.getElementById('pp-mat').value),
            confiance:     parseInt(document.getElementById('pp-conf').value)
        };
        var res  = await fetch(API_PP, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        var json = await res.json();
        showMsg(json.message, json.status==='success');
        if (json.status==='success') {
            document.getElementById('pp-nom').value=''; document.getElementById('pp-desc').value='';
            document.getElementById('form-pp').classList.remove('open');
            loadPP();
        }
    };

    window.deletePP = async function(id) {
        if (!confirm('Supprimer cette Partie Prenante et ses scénarios stratégiques liés ?')) return;
        var res  = await fetch(API_PP, {method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
        var json = await res.json();
        showMsg(json.message, json.status==='success');
        if (json.status==='success') loadPP();
    };

    // ── MATRICE ─────────────────────────────────────────────────
    function renderMatrix() {
        var grid = document.getElementById('matrix-grid');
        grid.innerHTML = '';
        // Exposition statique (D × P → couleur de fond)
        var EXPO_BG = { 1:'m-bg-1', 2:'m-bg-2', 3:'m-bg-3', 4:'m-bg-4' };
        var cells = {};
        // D=4 en haut → D=1 en bas dans la grille (row 1 = D=4)
        for (var d=4; d>=1; d--) {
            for (var p=1; p<=4; p++) {
                var expo = EXPO[d][p];
                var cell = document.createElement('div');
                cell.className = 'm-cell ' + EXPO_BG[expo];
                cell.id = 'mcell-' + d + '-' + p;
                grid.appendChild(cell);
                cells[''+d+'_'+p] = cell;
            }
        }
        // Placer les PP + liste en bas
        var legend = document.getElementById('matrix-legend-pps');
        legend.innerHTML = '';
        if (allPPs.length === 0) {
            legend.innerHTML = '<span style="color:#484f58;font-size:0.82rem;">Aucune PP créée.</span>';
        }
        allPPs.forEach(function(pp) {
            var c     = computePP(pp);
            var ppId  = 'PP-' + String(pp.id).padStart(3,'0');
            var cell  = cells[''+pp.dependance+'_'+pp.penetration];
            if (cell) {
                var dot = document.createElement('span');
                dot.className = 'm-pp-dot lvl-badge lvl-' + c.menace;
                dot.title = ppId + ' — ' + pp.nom + '\nExposition : ' + EXP_LABELS[c.expo] + ' | Fiabilité : ' + FIA_LABELS[c.fiab] + ' | Menace : ' + MEN_LABELS[c.menace];
                dot.textContent = ppId.replace('PP-','PP');
                cell.appendChild(dot);
            }
            legend.innerHTML += '<div style="display:flex;align-items:center;gap:7px;background:#0d1117;border:1px solid #21262d;border-radius:6px;padding:5px 10px;">' +
                '<span class="lvl-badge lvl-' + c.menace + '" style="min-width:56px; text-align:center; font-size:0.7rem;">' + ppId + '</span>' +
                '<span style="color:#c9d1d9; font-size:0.82rem;">' + esc(pp.nom) + '</span>' +
                '<span style="color:#484f58; font-size:0.72rem; margin-left:4px;">menace ' + MEN_LABELS[c.menace] + '</span>' +
            '</div>';
        });
    }

    // ── SCÉNARIOS STRATÉGIQUES ───────────────────────────────────
    var ssCache = { sources:[], pp_list:[], ov_list:[] };

    async function loadSS() {
        var res  = await fetch(API_SS);
        var json = await res.json();
        if (json.status !== 'success') { showMsg(json.message, false); return; }
        ssCache = json;
        rebuildSSSelects(json);
        renderSS(json.data, json.user_role);
    }

    function rebuildSSSelects(json) {
        var selSR = document.getElementById('ss-sr'); if (!selSR) return;
        selSR.innerHTML = '<option value="">— choisir —</option>';
        (json.sources||[]).forEach(function(sr) {
            selSR.innerHTML += '<option value="'+sr.id+'">SR-'+String(sr.id).padStart(3,'0')+' — '+esc(sr.type_source)+'</option>';
        });
        var selPP = document.getElementById('ss-pp');
        selPP.innerHTML = '<option value="">— aucune / à définir —</option>';
        (json.pp_list||[]).forEach(function(pp) {
            selPP.innerHTML += '<option value="'+pp.id+'">PP-'+String(pp.id).padStart(3,'0')+' — '+esc(pp.nom)+'</option>';
        });
        var selOV = document.getElementById('ss-ov');
        if (selOV) {
            selOV.innerHTML = '<option value="">— aucun / à définir —</option>';
            (json.ov_list||[]).forEach(function(ov) {
                selOV.innerHTML += '<option value="'+ov.id+'">['+esc(ov.sr_nom)+'] '+esc(ov.description)+'</option>';
            });
        }
    }

    function renderSS(data, userRole) {
        var el = document.getElementById('ss-list');
        if (!data || data.length === 0) {
            el.innerHTML = '<div style="text-align:center;color:#8b949e;padding:30px;border:1px dashed #30363d;border-radius:8px;">Aucun scénario stratégique. Cliquez « Générer depuis A2 » ou « Ajouter un scénario ».</div>';
            return;
        }
        var SS_STAT = { a_evaluer:{cls:'ss-a',label:'À évaluer',next:'retenu'}, retenu:{cls:'ss-r',label:'Retenu',next:'non_retenu'}, non_retenu:{cls:'ss-n',label:'Non retenu',next:'a_evaluer'} };
        el.innerHTML = data.map(function(ss) {
            var ssId  = 'SS-' + String(ss.id).padStart(3,'0');
            var srId  = 'SR-' + String(ss.menace_id).padStart(3,'0');
            var stat  = SS_STAT[ss.statut] || SS_STAT['a_evaluer'];
            var ovHtml = ss.ov_id ? '<span class="ss-arrow">→</span><span class="ov-chip">OV-'+String(ss.ov_id).padStart(3,'0')+' '+esc(ss.ov_desc||'')+'</span>' : '';
            var gravHtml = (ss.gravite > 0) ? '<span style="font-size:0.72rem;color:#8b949e;">Grav.&nbsp;<span class="lvl-badge lvl-'+ss.gravite+'" style="font-size:0.68rem;padding:1px 6px;">'+ss.gravite+'</span></span>' : '';
            var vraiHtml = (ss.vraisemblance > 0) ? '<span style="font-size:0.72rem;color:#8b949e;">Vrai.&nbsp;<span class="lvl-badge lvl-'+ss.vraisemblance+'" style="font-size:0.68rem;padding:1px 6px;">'+ss.vraisemblance+'</span></span>' : '';
            var statBtn = CAN_EDIT
                ? '<span class="ss-stat '+stat.cls+'" onclick="cycleSS('+ss.id+',\''+stat.next+'\')" title="Cliquer pour changer">'+stat.label+'</span>'
                : '<span class="ss-stat '+stat.cls+'">'+stat.label+'</span>';
            var delBtn = IS_ADMIN ? '<button class="ss-del" onclick="deleteSS('+ss.id+')" title="Supprimer">🗑</button>' : '';
            var registreEl = '';
            if (ss.registre_id) {
                registreEl = '<span style="font-size:0.7rem;padding:2px 8px;border-radius:10px;background:rgba(34,197,94,0.12);color:#22c55e;border:1px solid rgba(34,197,94,0.3);white-space:nowrap;" title="Entrée R'+String(ss.registre_id).padStart(3,'0')+' dans le Registre">✓ Dans le registre</span>';
            } else if (CAN_EDIT && ss.statut === 'retenu') {
                registreEl = '<button onclick="transferSS('+ss.id+')" style="font-size:0.7rem;padding:2px 9px;border-radius:4px;background:rgba(167,139,250,0.1);color:#a78bfa;border:1px solid rgba(167,139,250,0.35);cursor:pointer;white-space:nowrap;" title="Ajouter ce scénario au Registre des Risques">→ Envoyer au registre</button>';
            }
            // PP : null → dropdown d'assignation si CAN_EDIT, sinon libellé "À définir"
            var ppHtml;
            if (ss.pp_id) {
                var ppId = 'PP-' + String(ss.pp_id).padStart(3,'0');
                ppHtml = '<span class="pp-chip">'+esc(ppId)+' '+esc(ss.pp_nom||'')+'</span>';
            } else if (CAN_EDIT) {
                var ppOpts = '<option value="">— aucune —</option>';
                (ssCache.pp_list||[]).forEach(function(pp) {
                    ppOpts += '<option value="'+pp.id+'">PP-'+String(pp.id).padStart(3,'0')+' — '+esc(pp.nom)+'</option>';
                });
                ppHtml = '<select class="pp-assign-sel" title="Assigner une Partie Prenante" onchange="assignPP('+ss.id+',this.value)">'+ppOpts+'</select>';
            } else {
                ppHtml = '<span class="pp-chip" style="color:#484f58;border-color:#484f58;">À définir</span>';
            }
            return '<div class="ss-card">' +
                '<span class="ss-id">' + esc(ssId) + '</span>' +
                '<div class="ss-body">' +
                    '<div class="ss-path">' +
                        '<span class="sr-chip">'+esc(srId)+' '+esc(ss.sr_nom)+'</span>' +
                        '<span class="ss-arrow">⤳</span>' +
                        ppHtml +
                        ovHtml +
                    '</div>' +
                    (ss.description ? '<div class="ss-desc">' + esc(ss.description) + '</div>' : '') +
                '</div>' +
                '<div class="ss-right">' +
                    statBtn + gravHtml + vraiHtml + registreEl + delBtn +
                '</div>' +
            '</div>';
        }).join('');
    }

    window.createSS = async function() {
        var sr = parseInt(document.getElementById('ss-sr').value);
        if (!sr) { showMsg('La Source de Risque est obligatoire.', false); return; }
        var pp = parseInt(document.getElementById('ss-pp').value) || null;
        var payload = {
            menace_id:    sr,
            pp_id:        pp,
            ov_id:        parseInt(document.getElementById('ss-ov').value) || null,
            gravite:      parseInt(document.getElementById('ss-grav').value) || 0,
            vraisemblance:parseInt(document.getElementById('ss-vrai').value) || 0,
            description:  document.getElementById('ss-desc').value.trim()
        };
        var res  = await fetch(API_SS, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        var json = await res.json();
        showMsg(json.message, json.status==='success');
        if (json.status==='success') {
            document.getElementById('ss-desc').value='';
            document.getElementById('form-ss').classList.remove('open');
            loadSS();
        }
    };

    window.importFromA2 = async function() {
        if (!confirm('Générer automatiquement un Scénario Stratégique pour chaque Objectif Visé « Retenu » de l\'Atelier 2 ?\n\nLes doublons (même SR × OV) seront ignorés. La Partie Prenante pourra être assignée ensuite.')) return;
        var res  = await fetch(API_SS, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'import_from_a2'})});
        var json = await res.json();
        showMsg(json.message, json.status==='success');
        if (json.status==='success') loadSS();
    };

    window.assignPP = async function(ssId, ppId) {
        var payload = { id: ssId, pp_id: ppId ? parseInt(ppId) : null };
        var res  = await fetch(API_SS, {method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
        var json = await res.json();
        if (json.status==='success') loadSS(); else showMsg(json.message, false);
    };

    window.cycleSS = async function(id, next) {
        var res  = await fetch(API_SS, {method:'PATCH',headers:{'Content-Type':'application/json'},body:JSON.stringify({id,statut:next})});
        var json = await res.json();
        if (json.status==='success') loadSS(); else showMsg(json.message,false);
    };

    window.deleteSS = async function(id) {
        if (!confirm('Supprimer ce scénario stratégique ?')) return;
        var res  = await fetch(API_SS, {method:'DELETE',headers:{'Content-Type':'application/json'},body:JSON.stringify({id})});
        var json = await res.json();
        showMsg(json.message, json.status==='success');
        if (json.status==='success') loadSS();
    };

    window.transferSS = async function(id) {
        if (!confirm('Envoyer ce scénario stratégique dans le Registre des Risques ?\n\nIl sera ajouté comme nouvelle entrée, qualifiable et enrichissable depuis le Registre.')) return;
        var res  = await fetch(API_SS, {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action:'transfer',id})});
        var json = await res.json();
        showMsg(json.message, json.status==='success');
        if (json.status==='success') loadSS();
    };

    // ── INIT ────────────────────────────────────────────────────
    loadPP();
})();
</script>
