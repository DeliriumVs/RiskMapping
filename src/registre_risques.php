<?php
// src/registre_risques.php
header('Content-Type: text/html; charset=utf-8');
require 'db.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'MJ') {
    die("Accès refusé.");
}

$admin_role = $_SESSION['admin_role'] ?? 'lecteur';
$admin_username = $_SESSION['admin_username'] ?? 'Utilisateur';
$has_active_session = isset($_SESSION['session_id']) && !empty($_SESSION['session_id']);
$session_is_running = false;
if ($has_active_session) {
    $stmt_sess = $pdo->prepare("SELECT statut FROM sessions WHERE id = ?");
    $stmt_sess->execute([$_SESSION['session_id']]);
    $sess_statut = $stmt_sess->fetchColumn();
    $session_is_running = in_array($sess_statut, ['configuration', 'saisie', 'discussion']);
}
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
        .nav-dropdown-menu { display: none; position: absolute; right: 0; top: calc(100% + 6px); background: #161b22; border: 1px solid #30363d; border-radius: 6px; min-width: 190px; z-index: 200; box-shadow: 0 8px 24px rgba(0,0,0,0.6); overflow: hidden; }
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
            .no-print, .admin-navbar, .drag-handle { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 20px;" class="no-print">
            <div>
                <h1 style="color: #3b82f6; margin-bottom: 0;">🧭 RiskMapping</h1>
                <p class="subtitle" style="margin-top: 5px;">Tableau de Bord Expert (API REST)</p>
            </div>
            <div style="text-align: right;">
                <span style="color: #c9d1d9;">Connecté : <strong><?= htmlspecialchars($admin_username) ?></strong></span><br>
                <span style="font-size: 0.8rem; background: #30363d; color: #fff; padding: 2px 8px; border-radius: 10px; text-transform: uppercase;"><?= htmlspecialchars($admin_role) ?></span>
            </div>
        </div>

        <div class="admin-navbar no-print">
            <div class="nav-group">
                <span class="nav-title">Piloter</span>
                <button class="nav-btn-view active" data-target="view_registre.php">📊 Registre</button>
            </div>
            
            <?php if ($admin_role === 'admin' || $admin_role === 'animateur'): ?>
            <div class="nav-group">
                <span class="nav-title">Référentiels</span>
                <button class="nav-btn-view" data-target="admin_valeurs.php">💎 Valeurs Métier</button>
                <button class="nav-btn-view" data-target="admin_biens_supports.php">🔷 Biens Supports</button>
                <button class="nav-btn-view" data-target="admin_menaces.php">🦹 Sources de Risque</button>
                <button class="nav-btn-view" data-target="admin_objectifs_vises.php">🎯 Objectifs Visés</button>
            </div>
            <div class="nav-group">
                <span class="nav-title">Organisation</span>
                <button class="nav-btn-view" data-target="admin_equipes.php">🏢 Équipes</button>
            </div>
            <div class="nav-group">
                <span class="nav-title">Ateliers</span>
                <?php if ($session_is_running): ?>
                    <a href="mj_dashboard.php" class="nav-btn-real" style="background: rgba(34,197,94,0.15); border-color: #22c55e; color: #22c55e; font-weight: bold;">🟢 Atelier en cours</a>
                <?php else: ?>
                    <span class="nav-btn-real" style="opacity: 0.35; cursor: not-allowed;" title="Aucun atelier actif">⬜ Atelier en cours</span>
                <?php endif; ?>
                <button class="nav-btn-view" data-target="view_historique.php">📂 Historique Atl4</button>
                <a href="choix_atelier.php" class="nav-btn-real nav-btn-action" style="background: var(--accent-red);">🎯 Créer un Atelier</a>
            </div>
            <?php endif; ?>

            <div class="nav-group" style="margin-left: auto; border-right: none;">
                <?php if ($admin_role === 'admin'): ?>
                <div class="nav-dropdown" id="settings-dropdown" style="margin-right: 10px;">
                    <button class="nav-btn-real" id="btn-settings" onclick="toggleSettingsDropdown(event)" style="border-color: #8b949e; color: #8b949e;">⚙️ Paramètres ▾</button>
                    <div class="nav-dropdown-menu">
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
                    // 1. On injecte le HTML
                    contentArea.innerHTML = html;
                    
                    // 2. MAGIE SPA : On force l'exécution des scripts fraîchement injectés !
                    const scripts = contentArea.querySelectorAll('script');
                    scripts.forEach(oldScript => {
                        const newScript = document.createElement('script');
                        // Copier les attributs s'il y en a
                        Array.from(oldScript.attributes).forEach(attr => newScript.setAttribute(attr.name, attr.value));
                        // Transférer le code JavaScript
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        // Remplacer l'ancienne balise morte par la nouvelle qui va s'exécuter
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                })
                .catch(error => {
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
                // Ferme le menu Paramètres si ouvert
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

        // Chargement de la vue par défaut
        loadContent('view_registre.php');
    </script>
</body>
</html>
