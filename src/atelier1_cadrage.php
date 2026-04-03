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
        .ebios-context {
            background: rgba(59,130,246,0.05);
            border: 1px solid rgba(59,130,246,0.2);
            border-left: 4px solid #3b82f6;
            border-radius: 6px;
            padding: 15px 20px;
            margin-bottom: 25px;
            font-size: 0.875rem;
            color: #c9d1d9;
            line-height: 1.65;
        }
        .cbadge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 10px;
            font-size: 0.72rem;
            font-weight: bold;
            margin-right: 5px;
            font-family: monospace;
        }
        .cb-vm  { background: rgba(59,130,246,0.12);  color: #3b82f6; border: 1px solid #3b82f6; }
        .cb-bs  { background: rgba(167,139,250,0.12); color: #a78bfa; border: 1px solid #a78bfa; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px; text-align: left;">

        <!-- En-tête -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 5px;">
                    <span style="background: #3b82f6; color: #fff; font-size: 0.75rem; font-weight: bold; padding: 3px 10px; border-radius: 20px;">ATELIER 1</span>
                    <h1 style="color: #3b82f6; margin: 0;">Cadrage & Socle de Sécurité</h1>
                </div>
                <p style="color: #8b949e; margin: 0; font-size: 0.875rem;">
                    Identifier les
                    <span class="cbadge cb-vm">VM — Valeurs Métier</span> et les
                    <span class="cbadge cb-bs">BS — Biens Supports</span>
                </p>
            </div>
            <a href="choix_atelier.php" style="color: #8b949e; text-decoration: none; font-size: 0.85rem; white-space: nowrap; margin-top: 5px;">← Retour aux ateliers</a>
        </div>

        <!-- Contexte EBIOS RM -->
        <div class="ebios-context">
            <strong style="color: #3b82f6;">Objectif EBIOS RM :</strong>
            Cet atelier vise à recenser les <strong>Valeurs Métier (VM)</strong> — missions, informations et processus critiques que l'organisation cherche à protéger — et à y associer les <strong>Biens Supports (BS)</strong> sur lesquels elles reposent (systèmes d'information, applications, serveurs, personnes, locaux).
            Ces éléments constituent la base de référence pour tous les ateliers suivants.
            <br><br>
            <strong style="color: #a78bfa;">Note :</strong> La gestion des Biens Supports (BS) liés à chaque valeur métier est en cours de développement.
        </div>

        <!-- Contenu chargé dynamiquement -->
        <div id="atelier-content">
            <div style="text-align: center; padding: 40px; color: #8b949e;">⏳ Chargement du référentiel...</div>
        </div>

    </div>

    <script>
        function loadAtelier() {
            fetch('admin_valeurs.php')
                .then(r => r.text())
                .then(html => {
                    const container = document.getElementById('atelier-content');
                    container.innerHTML = html;
                    container.querySelectorAll('script').forEach(oldScript => {
                        const newScript = document.createElement('script');
                        Array.from(oldScript.attributes).forEach(a => newScript.setAttribute(a.name, a.value));
                        newScript.appendChild(document.createTextNode(oldScript.innerHTML));
                        oldScript.parentNode.replaceChild(newScript, oldScript);
                    });
                })
                .catch(() => {
                    document.getElementById('atelier-content').innerHTML =
                        '<div style="color: var(--accent-red); padding: 20px;">Erreur de chargement du module.</div>';
                });
        }
        loadAtelier();
    </script>
</body>
</html>
