<?php
// src/atelier1_cadrage.php
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    header("Location: index.php"); exit;
}
$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
if ($admin_role === 'lecteur') {
    header("Location: registre_risques.php"); exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Atelier 1 — Cadrage & Biens Supports | RiskMapping</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
            border: 1px solid;
            border-bottom: none;
        }
        .section-body {
            border: 1px solid;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .section-vm .section-header  { background: rgba(59,130,246,0.08); border-color: rgba(59,130,246,0.3); }
        .section-vm .section-body    { background: #0d1117; border-color: rgba(59,130,246,0.3); }
        .section-bs .section-header  { background: rgba(167,139,250,0.08); border-color: rgba(167,139,250,0.3); }
        .section-bs .section-body    { background: #0d1117; border-color: rgba(167,139,250,0.3); }

        .cbadge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 10px;
            font-size: 0.72rem;
            font-weight: bold;
            margin-right: 4px;
            font-family: monospace;
        }
        .cb-vm { background: rgba(59,130,246,0.12);  color: #3b82f6; border: 1px solid #3b82f6; }
        .cb-bs { background: rgba(167,139,250,0.12); color: #a78bfa; border: 1px solid #a78bfa; }

        table.bs-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        table.bs-table th { background: #161b22; color: #8b949e; padding: 9px 10px; border: 1px solid #30363d; text-align: left; font-weight: bold; }
        table.bs-table td { padding: 10px; border: 1px solid #30363d; vertical-align: middle; color: #c9d1d9; }
        table.bs-table tr:hover td { background: #161b22; }

        .type-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 4px;
            font-size: 0.78rem;
            background: #21262d;
            color: #c9d1d9;
            white-space: nowrap;
        }
        .form-row {
            display: grid;
            grid-template-columns: 2fr 1fr 2fr;
            gap: 10px;
            margin-bottom: 12px;
        }
        .form-input {
            width: 100%;
            padding: 9px 11px;
            background: #161b22;
            border: 1px solid #30363d;
            color: #fff;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 0.875rem;
        }
        .form-input:focus { outline: none; border-color: #a78bfa; }
        .form-label { display: block; font-size: 0.78rem; color: #8b949e; margin-bottom: 5px; font-weight: bold; text-transform: uppercase; }
        .vm-checkboxes { display: flex; flex-wrap: wrap; gap: 8px 16px; padding: 12px; background: #161b22; border: 1px solid #30363d; border-radius: 4px; margin-top: 10px; min-height: 44px; align-items: center; }
        .vm-check-label { display: inline-flex; align-items: center; gap: 7px; cursor: pointer; color: #c9d1d9; font-size: 0.85rem; }
        .vm-check-label input[type="checkbox"] { accent-color: #a78bfa; width: 15px; height: 15px; cursor: pointer; }

        .btn-add  { background: #a78bfa; color: #000; border: none; padding: 9px 20px; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 0.875rem; transition: 0.2s; }
        .btn-add:hover { background: #c4b5fd; }
        .msg-box { display: none; padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; font-size: 0.875rem; }

        .ebios-context {
            background: rgba(59,130,246,0.05);
            border: 1px solid rgba(59,130,246,0.2);
            border-left: 4px solid #3b82f6;
            border-radius: 6px;
            padding: 14px 18px;
            margin-bottom: 25px;
            font-size: 0.875rem;
            color: #c9d1d9;
            line-height: 1.65;
        }
        .separator {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 10px 0 28px;
            color: #8b949e;
            font-size: 0.8rem;
        }
        .separator::before, .separator::after { content: ''; flex: 1; height: 1px; background: #30363d; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 960px; text-align: left;">

        <!-- En-tête -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px;">
                    <span style="background: #3b82f6; color: #fff; font-size: 0.72rem; font-weight: bold; padding: 3px 10px; border-radius: 20px; text-transform: uppercase;">Atelier 1</span>
                    <h1 style="color: #3b82f6; margin: 0; font-size: 1.6rem;">Cadrage & Socle de Sécurité</h1>
                </div>
                <p style="color: #8b949e; margin: 0; font-size: 0.875rem;">
                    Identifier les <span class="cbadge cb-vm">VM — Valeurs Métier</span> et les <span class="cbadge cb-bs">BS — Biens Supports</span>
                </p>
            </div>
            <a href="choix_atelier.php" style="color: #8b949e; text-decoration: none; font-size: 0.85rem; white-space: nowrap; padding-top: 5px;">← Retour aux ateliers</a>
        </div>

        <!-- Contexte EBIOS RM -->
        <div class="ebios-context">
            <strong style="color: #3b82f6;">Objectif EBIOS RM :</strong>
            Identifier les <strong>Valeurs Métier (VM)</strong> — missions, informations et processus critiques — et les <strong>Biens Supports (BS)</strong> sur lesquels elles reposent : applications, serveurs, réseaux, personnes, locaux.
            Chaque VM doit être associée à au moins un BS. Ces associations servent de base aux scénarios de menace des ateliers suivants.
        </div>

        <!-- ======================================================== -->
        <!-- SECTION 1 : VALEURS MÉTIER                               -->
        <!-- ======================================================== -->
        <div class="section-vm">
            <div class="section-header">
                <span style="font-size: 1.3rem;">💎</span>
                <div>
                    <div style="font-weight: bold; color: #3b82f6; font-size: 1rem;">Valeurs Métier <span class="cbadge cb-vm" style="margin-left:6px;">VM</span></div>
                    <div style="font-size: 0.78rem; color: #8b949e; margin-top: 2px;">Actifs critiques à protéger (processus, données, missions)</div>
                </div>
            </div>
            <div class="section-body">
                <div id="vm-content">
                    <div style="text-align:center; padding: 30px; color: #8b949e;">⏳ Chargement...</div>
                </div>
            </div>
        </div>

        <!-- Séparateur entre les deux sections -->
        <div class="separator">↓ Associez ensuite les Biens Supports qui portent ces Valeurs Métier</div>

        <!-- ======================================================== -->
        <!-- SECTION 2 : BIENS SUPPORTS                               -->
        <!-- ======================================================== -->
        <div class="section-bs">
            <div class="section-header">
                <span style="font-size: 1.3rem;">🖧</span>
                <div>
                    <div style="font-weight: bold; color: #a78bfa; font-size: 1rem;">Biens Supports <span class="cbadge cb-bs" style="margin-left:6px;">BS</span></div>
                    <div style="font-size: 0.78rem; color: #8b949e; margin-top: 2px;">Composants techniques et humains sur lesquels reposent les Valeurs Métier</div>
                </div>
            </div>
            <div class="section-body">

                <div id="bs-msg" class="msg-box"></div>

                <!-- Tableau des BS -->
                <div style="overflow-x: auto; margin-bottom: 25px;">
                    <table class="bs-table">
                        <thead>
                            <tr>
                                <th style="width: 90px;">Identifiant</th>
                                <th style="width: 170px;">Type</th>
                                <th>Nom</th>
                                <th>Description</th>
                                <th>Valeurs Métier liées</th>
                                <th style="width: 60px; text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="bs-table-body">
                            <tr><td colspan="6" style="text-align:center; padding:20px; color:#8b949e;">Chargement...</td></tr>
                        </tbody>
                    </table>
                </div>

                <!-- Formulaire d'ajout -->
                <div style="background: #161b22; border: 1px solid rgba(167,139,250,0.25); border-radius: 6px; padding: 18px;">
                    <h4 style="color: #a78bfa; margin: 0 0 15px 0; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.05em;">➕ Ajouter un Bien Support</h4>

                    <div class="form-row">
                        <div>
                            <label class="form-label">Nom du Bien Support *</label>
                            <input type="text" id="bs-nom" class="form-input" placeholder="Ex : ERP SAP, Active Directory, Salle serveurs">
                        </div>
                        <div>
                            <label class="form-label">Type</label>
                            <select id="bs-type" class="form-input">
                                <option>Logiciel / Application</option>
                                <option>Infrastructure réseau</option>
                                <option>Serveur / Cloud</option>
                                <option>Poste de travail</option>
                                <option>Personne / Équipe</option>
                                <option>Site / Local</option>
                                <option>Autre</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Description (optionnel)</label>
                            <input type="text" id="bs-desc" class="form-input" placeholder="Ex : Gestion de la facturation client">
                        </div>
                    </div>

                    <div>
                        <label class="form-label" style="margin-bottom: 8px;">Valeurs Métier supportées (optionnel)</label>
                        <div class="vm-checkboxes" id="vm-checkboxes">
                            <span style="color: #8b949e; font-size: 0.85rem;">Chargement des valeurs métier...</span>
                        </div>
                    </div>

                    <div style="margin-top: 15px; text-align: right;">
                        <button onclick="addBs()" class="btn-add">+ Ajouter le Bien Support</button>
                    </div>
                </div>

            </div>
        </div>

    </div><!-- /container -->

    <!-- ================================================================ -->
    <!-- SCRIPTS : Section 1 (VM via fetch) + Section 2 (BS natif)        -->
    <!-- ================================================================ -->
    <script>
        // --- SECTION 1 : Charger admin_valeurs.php via fetch ---
        (function() {
            fetch('admin_valeurs.php')
                .then(r => r.text())
                .then(html => {
                    const c = document.getElementById('vm-content');
                    c.innerHTML = html;
                    c.querySelectorAll('script').forEach(s => {
                        const ns = document.createElement('script');
                        Array.from(s.attributes).forEach(a => ns.setAttribute(a.name, a.value));
                        ns.appendChild(document.createTextNode(s.innerHTML));
                        s.parentNode.replaceChild(ns, s);
                    });
                })
                .catch(() => {
                    document.getElementById('vm-content').innerHTML =
                        '<div style="color:var(--accent-red);padding:15px;">Erreur de chargement du module Valeurs Métier.</div>';
                });
        })();

        // --- SECTION 2 : Gestion native des Biens Supports ---
        const apiBs = 'api_biens_supports.php';

        const typeIcons = {
            'Logiciel / Application' : '🖥️',
            'Infrastructure réseau'  : '🌐',
            'Serveur / Cloud'        : '🗄️',
            'Poste de travail'       : '💻',
            'Personne / Équipe'      : '👤',
            'Site / Local'           : '🏢',
            'Autre'                  : '📦'
        };

        function showBsMsg(text, isError = false) {
            const box = document.getElementById('bs-msg');
            box.style.display = 'block';
            box.textContent   = text;
            box.style.backgroundColor = isError ? 'rgba(255,68,68,0.15)'  : 'rgba(167,139,250,0.1)';
            box.style.color           = isError ? '#ff4d4d'               : '#a78bfa';
            box.style.border          = `1px solid ${isError ? '#ff4d4d' : '#a78bfa'}`;
            setTimeout(() => box.style.display = 'none', 4000);
        }

        async function loadBs() {
            try {
                const res  = await fetch(apiBs);
                const json = await res.json();
                if (json.status !== 'success') { showBsMsg(json.message, true); return; }

                renderBsTable(json.data, json.user_role);
                renderVmCheckboxes(json.valeurs_metier);
            } catch(e) {
                showBsMsg("Erreur de connexion à l'API Biens Supports.", true);
            }
        }

        function renderBsTable(data, userRole) {
            const tbody = document.getElementById('bs-table-body');
            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:18px;color:#8b949e;">Aucun Bien Support configuré. Utilisez le formulaire ci-dessous.</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            data.forEach(bs => {
                const bsId   = 'BS-' + String(bs.id).padStart(3, '0');
                const icon   = typeIcons[bs.type_bien] || '📦';
                const vmBadges = bs.vm_ids.length > 0
                    ? bs.vm_ids.map(vid => `<span class="cbadge cb-vm">VM-${String(vid).padStart(3,'0')}</span>`).join(' ')
                    : '<span style="color:#8b949e;font-size:0.8rem;font-style:italic;">—</span>';
                const deleteBtn = userRole === 'admin'
                    ? `<button onclick="deleteBs(${bs.id})" style="background:none;border:none;color:#ff4d4d;cursor:pointer;font-size:1rem;" title="Supprimer">🗑️</button>`
                    : `<span style="color:#8b949e;font-size:0.8rem;" title="Droits administrateur requis">🔒</span>`;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td><span class="cbadge cb-bs" style="font-size:0.78rem;">${bsId}</span></td>
                    <td><span class="type-badge">${icon} ${bs.type_bien}</span></td>
                    <td style="font-weight:bold;color:#fff;">${bs.nom}</td>
                    <td style="color:#8b949e;font-size:0.82rem;">${bs.description || '—'}</td>
                    <td>${vmBadges}</td>
                    <td style="text-align:center;">${deleteBtn}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        function renderVmCheckboxes(vms) {
            const container = document.getElementById('vm-checkboxes');
            if (!vms || vms.length === 0) {
                container.innerHTML = '<span style="color:#8b949e;font-size:0.85rem;">Aucune Valeur Métier configurée — ajoutez-en dans la section ci-dessus.</span>';
                return;
            }
            container.innerHTML = vms.map(vm => {
                const vmId = 'VM-' + String(vm.id).padStart(3, '0');
                return `
                <label class="vm-check-label">
                    <input type="checkbox" name="vm_link" value="${vm.id}">
                    <span class="cbadge cb-vm">${vmId}</span>
                    <span>${vm.nom}</span>
                    <span style="color:#8b949e;font-size:0.78rem;">(${vm.critere_impacte})</span>
                </label>`;
            }).join('');
        }

        async function addBs() {
            const nom         = document.getElementById('bs-nom').value.trim();
            const type_bien   = document.getElementById('bs-type').value;
            const description = document.getElementById('bs-desc').value.trim();
            const vm_ids      = Array.from(document.querySelectorAll('input[name="vm_link"]:checked'))
                                     .map(cb => parseInt(cb.value));

            if (!nom) { showBsMsg('Le nom du Bien Support est obligatoire.', true); return; }

            try {
                const res  = await fetch(apiBs, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({nom, type_bien, description, vm_ids})
                });
                const json = await res.json();
                if (json.status === 'success') {
                    document.getElementById('bs-nom').value  = '';
                    document.getElementById('bs-desc').value = '';
                    document.querySelectorAll('input[name="vm_link"]').forEach(cb => cb.checked = false);
                    showBsMsg(json.message);
                    loadBs();
                } else {
                    showBsMsg(json.message, true);
                }
            } catch(e) {
                showBsMsg("Erreur lors de l'envoi.", true);
            }
        }

        async function deleteBs(id) {
            if (!confirm('Supprimer ce Bien Support ? Les associations avec les Valeurs Métier seront également supprimées.')) return;
            try {
                const res  = await fetch(apiBs, {
                    method: 'DELETE',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({id})
                });
                const json = await res.json();
                if (json.status === 'success') { showBsMsg(json.message); loadBs(); }
                else showBsMsg(json.message, true);
            } catch(e) {
                showBsMsg('Erreur lors de la suppression.', true);
            }
        }

        loadBs();
    </script>
</body>
</html>
