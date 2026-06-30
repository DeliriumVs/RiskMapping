<?php
// src/registre_risques.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    header('Location: admin_login.php');
    exit;
}

// Rediriger vers le sélecteur si aucune analyse active
if (empty($_SESSION['analyse_id'])) {
    header('Location: select_analyse.php');
    exit;
}

$admin_role     = $_SESSION['admin_role']      ?? 'lecteur';
$admin_username = $_SESSION['admin_username']  ?? 'Utilisateur';
$analyse_id     = (int)$_SESSION['analyse_id'];
$analyse_nom    = $_SESSION['analyse_nom']     ?? '—';
$analyse_statut = $_SESSION['analyse_statut']  ?? 'en_cours';
$entite_nom     = $_SESSION['entite_nom']      ?? '—';

$badge_statut_map = ['en_cours' => ['label'=>'En cours','color'=>'#22c55e'], 'finalisee' => ['label'=>'Finalisée','color'=>'#3b82f6'], 'archivee' => ['label'=>'Archivée','color'=>'#8b949e']];
$bs = $badge_statut_map[$analyse_statut] ?? ['label'=>$analyse_statut,'color'=>'#8b949e'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>RiskMapping — <?= htmlspecialchars($analyse_nom) ?></title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

    <style>
        .container { max-width: 98% !important; }

        /* Bannière de contexte */
        .context-banner { display: flex; align-items: center; gap: 12px; background: #161b22; border: 1px solid #2d4a7a; border-radius: 6px; padding: 9px 16px; margin-bottom: 14px; flex-wrap: wrap; }
        .ctx-entity { color: #8b949e; font-size: 0.85rem; }
        .ctx-entity strong { color: #c9d1d9; }
        .ctx-sep { color: #484f58; }
        .ctx-analyse strong { color: #3b82f6; font-size: 0.95rem; }
        .ctx-badge { padding: 2px 8px; border-radius: 10px; font-size: 0.75rem; font-weight: bold; }
        .ctx-change { margin-left: auto; color: #8b949e; font-size: 0.85rem; text-decoration: none; border: 1px solid #30363d; padding: 4px 10px; border-radius: 4px; transition: 0.15s; }
        .ctx-change:hover { color: #fff; border-color: #8b949e; }

        /* Navbar */
        .admin-navbar { display: flex; flex-wrap: wrap; gap: 20px; background: #161b22; padding: 15px 20px; border-radius: 8px; border: 1px solid #30363d; margin-bottom: 30px; align-items: center; justify-content: space-between; }
        .nav-group { display: flex; gap: 10px; align-items: center; padding-right: 20px; border-right: 1px solid #30363d; }
        .nav-group:last-child { border-right: none; padding-right: 0; }
        .nav-title { font-size: 0.8rem; color: #8b949e; text-transform: uppercase; font-weight: bold; margin-right: 10px; white-space: nowrap; }

        .nav-btn-view { background: #21262d; border: 1px solid #30363d; color: #c9d1d9; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: 0.2s; white-space: nowrap; cursor: pointer; }
        .nav-btn-view:hover { background: #30363d; color: #fff; border-color: #8b949e; }
        .nav-btn-view.active { background: rgba(59, 130, 246, 0.1); border-color: #3b82f6; color: #3b82f6; font-weight: bold; }

        .nav-btn-real { background: #21262d; border: 1px solid #30363d; color: #c9d1d9; padding: 8px 12px; border-radius: 6px; text-decoration: none; font-size: 0.9rem; transition: 0.2s; white-space: nowrap; }
        .nav-btn-real:hover { background: #30363d; color: #fff; }
        .nav-btn-action { background: #3b82f6; color: #fff; border: none; }
        .nav-btn-action:hover { background: #2563eb; color: #fff; }
        .nav-btn-danger { background: rgba(218, 41, 28, 0.1); color: #da291c; border-color: #da291c; }
        .nav-btn-danger:hover { background: #da291c; color: #fff; }

        .nav-dropdown { position: relative; }
        .nav-dropdown-menu { display: none; position: absolute; right: 0; top: calc(100% + 6px); background: #161b22; border: 1px solid #30363d; border-radius: 6px; min-width: 210px; z-index: 200; box-shadow: 0 8px 24px rgba(0,0,0,0.6); overflow: hidden; }
        .nav-dropdown.open .nav-dropdown-menu { display: block; }
        .nav-dropdown-menu .nav-btn-view { display: block; width: 100%; text-align: left; border: none; border-radius: 0; padding: 10px 16px; border-bottom: 1px solid #21262d; }
        .nav-dropdown-menu .nav-btn-view:last-child { border-bottom: none; }

        .loader { border: 4px solid #f3f3f3; border-top: 4px solid #3b82f6; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 50px auto; display: none; }

        .print-only { display: none; }
        .drag-handle { cursor: grab; color: #8b949e; font-size: 1.5rem; text-align: center; width: 30px; }

        @media print {
            @page { size: landscape; margin: 15mm; }
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body, .container { background: #fff !important; color: #000 !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .print-only { display: block !important; }
            .no-print, .admin-navbar, .context-banner, .drag-handle { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container">

        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 14px;" class="no-print">
            <div>
                <h1 style="color: #3b82f6; margin-bottom: 0;">🧭 RiskMapping</h1>
                <p class="subtitle" style="margin-top: 5px;">Analyse des Risques EBIOS RM</p>
            </div>
            <div style="text-align: right;">
                <span style="color: #c9d1d9;">Connecté : <strong><?= htmlspecialchars($admin_username) ?></strong></span><br>
                <span style="font-size: 0.8rem; background: #30363d; color: #fff; padding: 2px 8px; border-radius: 10px; text-transform: uppercase;"><?= htmlspecialchars($admin_role) ?></span>
            </div>
        </div>

        <!-- Bannière de contexte -->
        <div class="context-banner no-print">
            <span class="ctx-entity">📁 <strong><?= htmlspecialchars($entite_nom) ?></strong></span>
            <span class="ctx-sep">›</span>
            <span class="ctx-analyse"><strong><?= htmlspecialchars($analyse_nom) ?></strong></span>
            <span class="ctx-badge" style="background: rgba(<?= $bs['color'] === '#22c55e' ? '34,197,94' : ($bs['color'] === '#3b82f6' ? '59,130,246' : '139,148,158') ?>,0.15); color: <?= $bs['color'] ?>; border: 1px solid <?= $bs['color'] ?>40;"><?= $bs['label'] ?></span>
            <a href="select_analyse.php" class="ctx-change">Changer d'analyse ›</a>
        </div>

        <div class="admin-navbar no-print">
            <div class="nav-group">
                <span class="nav-title">Piloter</span>
                <button class="nav-btn-view active" data-target="view_registre.php">📊 Registre</button>
            </div>

            <?php if ($admin_role === 'admin' || $admin_role === 'animateur'): ?>
            <div class="nav-group">
                <span class="nav-title">Ateliers</span>
                <button class="nav-btn-view" data-target="atelier1.php" title="Valeurs Métier · Événements Redoutés · Biens Supports">📋 Atelier 1</button>
                <button class="nav-btn-view" data-target="atelier2.php" title="Sources de Risque · Objectifs Visés · Notation">🔗 Atelier 2</button>
                <button class="nav-btn-view" data-target="atelier3.php" title="Parties Prenantes · Matrice Exposition · Scénarios Stratégiques">🗺️ Atelier 3</button>
            </div>
            <div class="nav-group">
                <span class="nav-title">Organisation</span>
                <button class="nav-btn-view" data-target="admin_equipes.php">🏢 Équipes</button>
            </div>
            <?php endif; ?>

            <div class="nav-group" style="margin-left: auto; border-right: none;">
                <?php if ($admin_role === 'admin'): ?>
                <div class="nav-dropdown" id="settings-dropdown" style="margin-right: 10px;">
                    <button class="nav-btn-real" id="btn-settings" onclick="toggleSettingsDropdown(event)" style="border-color: #8b949e; color: #8b949e;">⚙️ Paramètres ▾</button>
                    <div class="nav-dropdown-menu">
                        <button class="nav-btn-view" data-target="admin_entites.php">📁 Entités & Analyses</button>
                        <button class="nav-btn-view" data-target="admin_comptes.php">👤 Comptes</button>
                        <button class="nav-btn-view" data-target="admin_audit.php">📜 Audit</button>
                        <button class="nav-btn-view" data-target="admin_backup.php">💾 Sauvegardes</button>
                    </div>
                </div>
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
        const viewButtons = document.querySelectorAll('.nav-btn-view');

        function loadContent(targetFile) {
            loader.style.display = 'block';
            contentArea.style.opacity = '0.5';

            fetch(targetFile)
                .then(response => response.text())
                .then(html => {
                    contentArea.innerHTML = html;
                    const scripts = contentArea.querySelectorAll('script');
                    scripts.forEach(oldScript => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                })
                .catch(() => {
                    contentArea.innerHTML = `<div style="color:red; padding:20px;">Erreur de chargement de la vue.</div>`;
                })
                .finally(() => {
                    loader.style.display = 'none';
                    contentArea.style.opacity = '1';
                });
        }

        viewButtons.forEach(button => {
            button.addEventListener('click', function() {
                viewButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                loadContent(this.getAttribute('data-target'));
                const dd = document.getElementById('settings-dropdown');
                if (dd) dd.classList.remove('open');
            });
        });

        function toggleSettingsDropdown(e) {
            e.stopPropagation();
            document.getElementById('settings-dropdown').classList.toggle('open');
        }

        document.addEventListener('click', function(e) {
            const dd = document.getElementById('settings-dropdown');
            if (dd && !dd.contains(e.target)) dd.classList.remove('open');
        });

        loadContent('view_registre.php');
    </script>
</body>
</html>
