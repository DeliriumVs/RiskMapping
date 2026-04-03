<?php
// src/atelier3_menaces.php
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
    <title>Atelier 3 — Scénarios Stratégiques | RiskMapping</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .ebios-context {
            background: rgba(218,41,28,0.05);
            border: 1px solid rgba(218,41,28,0.2);
            border-left: 4px solid #da291c;
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
        .cb-sr  { background: rgba(218,41,28,0.12);   color: #da291c; border: 1px solid #da291c; }
        .cb-ov  { background: rgba(255,165,0,0.12);   color: orange;  border: 1px solid orange; }
        .cb-vm  { background: rgba(59,130,246,0.12);  color: #3b82f6; border: 1px solid #3b82f6; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 900px; text-align: left;">

        <!-- En-tête -->
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 5px;">
                    <span style="background: #da291c; color: #fff; font-size: 0.75rem; font-weight: bold; padding: 3px 10px; border-radius: 20px;">ATELIER 3</span>
                    <h1 style="color: #da291c; margin: 0;">Scénarios Stratégiques</h1>
                </div>
                <p style="color: #8b949e; margin: 0; font-size: 0.875rem;">
                    Définir les
                    <span class="cbadge cb-sr">SR — Sources de Risque</span> et leurs
                    <span class="cbadge cb-ov">OV — Objectifs Visés</span>
                </p>
            </div>
            <a href="choix_atelier.php" style="color: #8b949e; text-decoration: none; font-size: 0.85rem; white-space: nowrap; margin-top: 5px;">← Retour aux ateliers</a>
        </div>

        <!-- Contexte EBIOS RM -->
        <div class="ebios-context">
            <strong style="color: #da291c;">Objectif EBIOS RM :</strong>
            Cet atelier vise à identifier les <strong>Sources de Risque (SR)</strong> susceptibles de menacer l'organisation : leur type, leur motivation et leur niveau de capacité. Ces sources sont ensuite associées à leurs <strong>Objectifs Visés (OV)</strong> pour former des couples <strong>SR/OV</strong>, base des scénarios stratégiques.
            <br><br>
            <strong style="color: orange;">Note :</strong> La construction formelle des couples SR/OV et des scénarios stratégiques graphiques est en cours de développement (Atelier 2). Ce module gère actuellement le référentiel des sources de menace.
        </div>

        <!-- Contenu chargé dynamiquement -->
        <div id="atelier-content">
            <div style="text-align: center; padding: 40px; color: #8b949e;">⏳ Chargement du référentiel...</div>
        </div>

    </div>

    <script>
        function loadAtelier() {
            fetch('admin_menaces.php')
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
