<?php
// src/registre_risques.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("Accès refusé.");
}

$admin_role = $_SESSION['admin_role'] ?? 'lecteur'; 
$admin_username = $_SESSION['admin_username'] ?? 'Utilisateur';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RiskMapping - Tableau de Bord</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
    
    <style>
        .container { max-width: 98% !important; }
        .admin-navbar { display: flex; flex-wrap: wrap; gap: 20px; background: #161b22; padding: 15px 20px; border-radius: 8px; border: 1px solid #30363d; margin-bottom: 30px; align-items: center; justify-content: space-between; }
        .nav-group { display: flex; gap: 10px; align-items: center; padding-right: 20px; border-right: 1px solid #30363d; }
        .nav-group:last-child { border-right: none; padding-right: 0; }
        .nav-title { font-size: 0.8rem; color: #8b949e; text-transform: uppercase; font-weight: bold; margin-right: 10px; white-space: nowrap; }
        .nav-btn-ajax { background: #21262d; border: 1px solid #30363d; color: #c9d1d9; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: 0.2s; white-space: nowrap; cursor: pointer; }
        .nav-btn-ajax:hover { background: #30363d; color: #fff; border-color: #8b949e; }
        .nav-btn-ajax.active { background: rgba(59, 130, 246, 0.1); border-color: #3b82f6; color: #3b82f6; font-weight: bold; }
        .nav-btn-real { background: #21262d; border: 1px solid #30363d; color: #c9d1d9; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: 0.2s; white-space: nowrap; }
        .nav-btn-real:hover { background: #30363d; color: #fff; }
        .nav-btn-action { background: #3b82f6; color: #fff; border: none; }
        .nav-btn-action:hover { background: #2563eb; color: #fff; }
        .nav-btn-danger { background: rgba(218, 41, 28, 0.1); color: #da291c; border-color: #da291c; }
        .nav-btn-danger:hover { background: #da291c; color: #fff; }

        .heatmap-wrapper { display: flex; flex-direction: column; align-items: center; margin: 40px 0; padding: 20px; background: #161b22; border-radius: 8px; border: 1px solid #30363d; }
        .heatmap-core { display: flex; align-items: stretch; }
        .heatmap-y-axis { display: flex; flex-direction: column; justify-content: space-around; padding-right: 15px; text-align: right; color: #8b949e; font-size: 0.85rem; font-weight: bold; }
        .heatmap-grid { display: grid; grid-template-columns: repeat(4, 1fr); grid-template-rows: repeat(4, 1fr); width: 600px; height: 400px; gap: 4px; background: #30363d; border: 4px solid #30363d; border-radius: 4px;}
        .heatmap-cell { display: flex; flex-wrap: wrap; align-content: flex-start; gap: 4px; padding: 8px; overflow: hidden; }
        .heatmap-x-axis { display: grid; grid-template-columns: repeat(4, 1fr); width: 600px; padding-top: 15px; text-align: center; color: #8b949e; font-size: 0.85rem; font-weight: bold; margin-left: 90px; }
        
        .bg-critical { background-color: rgba(255, 0, 85, 0.25) !important; }
        .bg-high { background-color: rgba(255, 68, 68, 0.25) !important; }
        .bg-medium { background-color: rgba(255, 165, 0, 0.25) !important; }
        .bg-low { background-color: rgba(0, 255, 204, 0.25) !important; }

        .risk-dot { display: inline-flex; justify-content: center; align-items: center; background: #0d1117; color: #fff; border: 1px solid #8b949e; border-radius: 50%; width: 28px; height: 28px; font-size: 0.75rem; font-weight: bold; cursor: help; box-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .risk-dot:hover { border-color: #fff; transform: scale(1.1); }

        table { width: 100%; border-collapse: collapse; background: #161b22; color: #fff; font-size: 0.85rem;}
        th, td { padding: 8px; border: 1px solid #30363d; vertical-align: top; }
        th { background: #21262d; color: var(--accent-green); position: sticky; top: 0; }
        tr:hover { background: #2c3238; }
        
        .drag-handle { cursor: grab; color: #8b949e; font-size: 1.5rem; text-align: center; width: 30px; }
        .badge-risk { padding: 4px 8px; border-radius: 4px; font-weight: bold; text-align: center; display: inline-block; min-width: 25px; }
        .risk-critical { background: rgba(255, 0, 85, 0.2); color: #ff0055; border: 1px solid #ff0055; text-transform: uppercase; }
        .risk-high { background: rgba(255, 68, 68, 0.2); color: #ff4444; border: 1px solid #ff4444; }
        .risk-medium { background: rgba(255, 165, 0, 0.2); color: orange; border: 1px solid orange; }
        .risk-low { background: rgba(0, 255, 204, 0.2); color: #00ffcc; border: 1px solid #00ffcc; }
        
        .badge-traitement { background: #30363d; color: #fff; padding: 4px 8px; border-radius: 4px; display: inline-block; }
        .trait-A, .trait-À { border-left: 3px solid gray; }
        .trait-Réduire { border-left: 3px solid var(--accent-green); }
        .trait-Transférer { border-left: 3px solid #3b82f6; }
        .trait-Éviter { border-left: 3px solid var(--accent-red); }
        .trait-Accepter { border-left: 3px solid orange; }

        .justification-box { margin-top: 8px; font-size: 0.75rem; color: #8b949e; font-style: italic; padding: 5px; background: rgba(0,0,0,0.2); border-left: 2px solid #30363d; border-radius: 0 4px 4px 0; }
        .justif-date { font-size: 0.65rem; color: #8b949e; margin-bottom: 3px; border-bottom: 1px dashed #30363d; padding-bottom: 3px; text-align: left; }
        
        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3b82f6; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 50px auto; display: none; }
        .print-only { display: none; }

        /* MAGIE DU PDF */
        @media print {
            @page { size: landscape; margin: 15mm; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body, .container { background: #fff !important; color: #000 !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            h1, h2, h3, h4, strong, span, p, div { color: #000 !important; }
            .print-only { display: block !important; }
            .no-print, .admin-navbar, .drag-handle { display: none !important; }
            .heatmap-wrapper { border: none !important; background: transparent !important; margin: 10px 0 !important; padding: 0 !important; page-break-inside: avoid; }
            .heatmap-grid { border: 2px solid #000 !important; background: #000 !important; gap: 2px !important; }
            .heatmap-cell { border: none !important; }
            .bg-critical { background-color: #ff4d4d !important; }
            .bg-high { background-color: #ff9999 !important; }
            .bg-medium { background-color: #ffcc99 !important; }
            .bg-low { background-color: #99ffcc !important; }
            .risk-dot { background: #000 !important; color: #fff !important; border: 1px solid #fff !important; font-weight: bold !important; box-shadow: none !important; }
            table { border-collapse: collapse !important; width: 100% !important; border: 2px solid #000 !important; margin-top: 10px !important; }
            th { background: #e2e8f0 !important; color: #000 !important; border: 1px solid #000 !important; font-weight: bold !important; padding: 10px !important;}
            td { border: 1px solid #000 !important; color: #000 !important; background: #fff !important; padding: 8px !important; page-break-inside: avoid; }
            .badge-risk { border: 1px solid #000 !important; }
            .risk-critical { background-color: #ff4d4d !important; color: #000 !important; }
            .risk-high { background-color: #ff9999 !important; color: #000 !important; }
            .risk-medium { background-color: #ffcc99 !important; color: #000 !important; }
            .risk-low { background-color: #99ffcc !important; color: #000 !important; }
            .badge-traitement { background-color: #f1f5f9 !important; color: #000 !important; border: 1px solid #cbd5e1 !important; font-weight: bold; }
            .trait-A, .trait-À { border-left: 4px solid #64748b !important; }
            .trait-Réduire { border-left: 4px solid #059669 !important; }
            .trait-Transférer { border-left: 4px solid #2563eb !important; }
            .trait-Éviter { border-left: 4px solid #dc2626 !important; }
            .trait-Accepter { border-left: 4px solid #ea580c !important; }
            .justification-box { background: #f8fafc !important; border-left: 3px solid #000 !important; padding: 8px !important; margin-top: 5px !important; color: #333 !important;}
            .justif-date { border-bottom-color: #cbd5e1 !important; color: #64748b !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px;" class="no-print">
            <div>
                <h1 style="color: #3b82f6; margin-bottom: 0;">🧭 RiskMapping</h1>
                <p class="subtitle" style="margin-top: 5px;">Tableau de Bord Expert</p>
            </div>
            <div style="text-align: right;">
                <span style="color: #c9d1d9;">Connecté : <strong><?= htmlspecialchars($admin_username) ?></strong></span><br>
                <span style="font-size: 0.8rem; background: #30363d; color: #fff; padding: 2px 8px; border-radius: 10px; text-transform: uppercase;"><?= htmlspecialchars($admin_role) ?></span>
            </div>
        </div>

        <div class="admin-navbar no-print">
            <div class="nav-group">
                <span class="nav-title">Piloter</span>
                <button class="nav-btn-ajax active" data-target="ajax_load_registre.php">📊 Registre</button>
                <button class="nav-btn-ajax" data-target="ajax_load_historique.php">📂 Historique</button>
            </div>
            
            <?php if ($admin_role === 'admin' || $admin_role === 'animateur'): ?>
            <div class="nav-group">
                <span class="nav-title">Référentiels</span>
                <button class="nav-btn-ajax" data-target="admin_valeurs.php">💎 Valeurs Métier</button>
                <button class="nav-btn-ajax" data-target="admin_menaces.php">🦹 Menaces</button>
                <button class="nav-btn-ajax" data-target="admin_equipes.php">🏢 Équipes</button>
            </div>
            <div class="nav-group">
                <span class="nav-title">Ateliers</span>
                <a href="mj_setup.php" class="nav-btn-real nav-btn-action" style="background: var(--accent-red);">➕ Nouvel Atelier</a>
            </div>
            <?php endif; ?>

            <div class="nav-group" style="margin-left: auto; border-right: none;">
                <?php if ($admin_role === 'admin'): ?>
                    <button class="nav-btn-ajax" data-target="admin_comptes.php" style="border-color: #8b949e; margin-right: 10px;">👤 Comptes</button>
                    <button class="nav-btn-ajax" data-target="admin_audit.php" style="border-color: #8b949e; margin-right: 15px;">📜 Audit</button>
                <?php endif; ?>
                <button onclick="window.print()" class="nav-btn-real nav-btn-danger">📄 PDF</button>
                <a href="export_global_csv.php" class="nav-btn-real" style="border-color: #107c41; color: #107c41;">🧮 CSV</a>
                <a href="logout.php" class="nav-btn-real" style="border-color: #484f58; color: #8b949e;">Déconnexion</a>
            </div>
        </div>

        <div id="loader" class="loader"></div>
        <div id="main-content-area"></div>
    </div>

    <script>
        const contentArea = document.getElementById('main-content-area');
        const loader = document.getElementById('loader');
        const ajaxButtons = document.querySelectorAll('.nav-btn-ajax');

        function loadContent(targetFile) {
            loader.style.display = 'block';
            contentArea.style.opacity = '0.5';

            fetch(targetFile)
                .then(response => response.text())
                .then(html => {
                    contentArea.innerHTML = html;
                    if (targetFile.includes('registre')) { initSortableAndRestoreOrder(); }
                })
                .finally(() => {
                    loader.style.display = 'none';
                    contentArea.style.opacity = '1';
                });
        }

        function initSortableAndRestoreOrder() {
            const tableBody = document.getElementById('sortable-table');
            if (!tableBody) return;

            let savedOrder = localStorage.getItem('riskmapping_order');
            if (savedOrder) {
                let orderArray = savedOrder.split(',');
                orderArray.forEach(id => {
                    let row = tableBody.querySelector(`tr[data-id="${id}"]`);
                    if (row) tableBody.appendChild(row); 
                });
            }

            Sortable.create(tableBody, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'risk-medium',
                onEnd: function () {
                    let newOrder = [];
                    tableBody.querySelectorAll('tr[data-id]').forEach(row => {
                        newOrder.push(row.getAttribute('data-id'));
                    });
                    localStorage.setItem('riskmapping_order', newOrder.join(','));
                }
            });
        }

        function resetOrder() {
            if (confirm("Voulez-vous réinitialiser le tri par défaut EBIOS ?")) {
                localStorage.removeItem('riskmapping_order');
                loadContent('ajax_load_registre.php');
            }
        }

        ajaxButtons.forEach(button => {
            button.addEventListener('click', function() {
                ajaxButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                loadContent(this.getAttribute('data-target'));
            });
        });

        function supprimerRisque(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer définitivement ce scénario ?')) {
                fetch(`ajax_load_registre.php?delete_id=${id}`).then(() => loadContent('ajax_load_registre.php'));
            }
        }
        function supprimerSession(id) {
            if (confirm('Supprimer cette session effacera TOUS les risques qui y sont liés. Continuer ?')) {
                fetch(`ajax_load_historique.php?delete_session_id=${id}`).then(() => loadContent('ajax_load_historique.php'));
            }
        }
        function handleAjaxForm(form, targetFile) {
            const formData = new FormData(form);
            fetch(targetFile, { method: 'POST', body: formData })
            .then(response => response.text())
            .then(html => { contentArea.innerHTML = html; })
            .catch(error => alert("Erreur : " + error));
        }

        loadContent('ajax_load_registre.php');
    </script>
</body>
</html>
