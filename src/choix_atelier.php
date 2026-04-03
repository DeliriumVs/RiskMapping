<?php
// src/choix_atelier.php
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
    <title>RiskMapping - Ateliers EBIOS RM</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .atelier-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 25px;
        }
        .atelier-card {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 10px;
            padding: 25px;
            transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
            text-align: left;
        }
        .atelier-card.clickable:hover {
            border-color: #3b82f6;
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(59, 130, 246, 0.15);
        }
        .atelier-card.card-4:hover {
            border-color: var(--accent-red);
            box-shadow: 0 6px 25px rgba(255, 68, 68, 0.15);
        }
        .atelier-card.disabled {
            opacity: 0.55;
        }
        .card-bg-number {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 5rem;
            font-weight: 900;
            color: #161b22;
            line-height: 1;
            user-select: none;
        }
        .atelier-title {
            font-size: 1.15rem;
            font-weight: bold;
            color: #fff;
            margin-bottom: 3px;
            position: relative;
        }
        .atelier-subtitle {
            font-size: 0.8rem;
            color: #8b949e;
            margin-bottom: 12px;
            font-style: italic;
        }
        .atelier-desc {
            font-size: 0.88rem;
            color: #c9d1d9;
            line-height: 1.6;
            margin-bottom: 18px;
            min-height: 60px;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: bold;
            margin-bottom: 12px;
        }
        .status-ok       { background: rgba(0,230,184,0.1); color: var(--accent-green); border: 1px solid var(--accent-green); }
        .status-partial  { background: rgba(255,165,0,0.1);  color: orange;              border: 1px solid orange; }
        .status-wip      { background: rgba(139,148,158,0.1); color: #8b949e;            border: 1px solid #8b949e; }

        .concept-badges { margin-bottom: 18px; }
        .cbadge {
            display: inline-block;
            padding: 2px 9px;
            border-radius: 10px;
            font-size: 0.72rem;
            font-weight: bold;
            margin-right: 5px;
            margin-bottom: 4px;
            font-family: monospace;
        }
        .cb-vm  { background: rgba(59,130,246,0.12);  color: #3b82f6; border: 1px solid #3b82f6; }
        .cb-bs  { background: rgba(167,139,250,0.12); color: #a78bfa; border: 1px solid #a78bfa; }
        .cb-sr  { background: rgba(218,41,28,0.12);   color: #da291c; border: 1px solid #da291c; }
        .cb-ov  { background: rgba(255,165,0,0.12);   color: orange;  border: 1px solid orange; }
        .cb-r   { background: rgba(0,230,184,0.12);   color: var(--accent-green); border: 1px solid var(--accent-green); }

        .card-btn {
            display: block;
            text-align: center;
            padding: 11px;
            border-radius: 6px;
            font-weight: bold;
            text-decoration: none;
            font-size: 0.9rem;
            transition: 0.2s;
            border: none;
            cursor: pointer;
            width: 100%;
            box-sizing: border-box;
        }
        .btn-blue    { background: #3b82f6; color: #fff; }
        .btn-blue:hover { background: #2563eb; }
        .btn-red     { background: var(--accent-red); color: #fff; }
        .btn-red:hover { box-shadow: 0 0 15px var(--accent-red); }
        .btn-gray    { background: #21262d; color: #8b949e; cursor: not-allowed; }

        .nomenclature-bar {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 8px;
            padding: 12px 20px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .nom-label { font-size: 0.75rem; color: #8b949e; font-weight: bold; text-transform: uppercase; margin-right: 5px; white-space: nowrap; }
    </style>
</head>
<body>
    <div class="container" style="max-width: 960px; text-align: left;">

        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 20px;">
            <div>
                <h1 style="color: #3b82f6; margin: 0 0 5px 0;">🎯 Ateliers EBIOS RM</h1>
                <p style="color: #8b949e; margin: 0; font-size: 0.9rem;">Sélectionnez le type d'atelier à conduire selon la méthodologie ANSSI</p>
            </div>
            <a href="registre_risques.php" style="color: #8b949e; text-decoration: none; font-size: 0.85rem; white-space: nowrap; margin-top: 5px;">← Retour au registre</a>
        </div>

        <!-- Barre de nomenclature -->
        <div class="nomenclature-bar">
            <span class="nom-label">Nomenclature :</span>
            <span class="cbadge cb-vm">VM — Valeur Métier</span>
            <span class="cbadge cb-bs">BS — Bien Support</span>
            <span class="cbadge cb-sr">SR — Source de Risque</span>
            <span class="cbadge cb-ov">OV — Objectif Visé</span>
            <span class="cbadge cb-r">R — Risque</span>
        </div>

        <!-- Grille des 4 ateliers -->
        <div class="atelier-grid">

            <!-- ATELIER 1 -->
            <div class="atelier-card clickable">
                <div class="card-bg-number">01</div>
                <div class="atelier-title">Cadrage & Socle de Sécurité</div>
                <div class="atelier-subtitle">Atelier 1 — EBIOS RM</div>
                <div><span class="status-badge status-partial">⚙️ Partiel</span></div>
                <div class="atelier-desc">
                    Recenser les <strong>Valeurs Métier (VM)</strong> — missions et processus critiques — à protéger. Identifier les <strong>Biens Supports (BS)</strong> sur lesquels elles reposent (SI, applications, infrastructure).
                </div>
                <div class="concept-badges">
                    <span class="cbadge cb-vm">VM</span>
                    <span class="cbadge cb-bs">BS</span>
                </div>
                <a href="atelier1_cadrage.php" class="card-btn btn-blue">Démarrer l'Atelier 1 →</a>
            </div>

            <!-- ATELIER 2 -->
            <div class="atelier-card disabled">
                <div class="card-bg-number">02</div>
                <div class="atelier-title">Sources de Risque</div>
                <div class="atelier-subtitle">Atelier 2 — EBIOS RM</div>
                <div><span class="status-badge status-wip">🚧 En développement</span></div>
                <div class="atelier-desc">
                    Recenser les <strong>Sources de Risque (SR)</strong> pertinentes et leurs <strong>Objectifs Visés (OV)</strong>. Construire les couples <strong>SR/OV</strong> représentatifs de la menace réelle pesant sur l'organisation.
                </div>
                <div class="concept-badges">
                    <span class="cbadge cb-sr">SR</span>
                    <span class="cbadge cb-ov">OV</span>
                </div>
                <span class="card-btn btn-gray">Bientôt disponible</span>
            </div>

            <!-- ATELIER 3 -->
            <div class="atelier-card clickable">
                <div class="card-bg-number">03</div>
                <div class="atelier-title">Scénarios Stratégiques</div>
                <div class="atelier-subtitle">Atelier 3 — EBIOS RM</div>
                <div><span class="status-badge status-partial">⚙️ Partiel</span></div>
                <div class="atelier-desc">
                    Définir les sources de menaces et construire les <strong>scénarios stratégiques</strong> reliant les couples SR/OV aux Valeurs Métier. Évaluer l'indicateur de menace pour chaque source.
                </div>
                <div class="concept-badges">
                    <span class="cbadge cb-sr">SR</span>
                    <span class="cbadge cb-ov">OV</span>
                    <span class="cbadge cb-vm">VM</span>
                </div>
                <a href="atelier3_menaces.php" class="card-btn btn-blue">Démarrer l'Atelier 3 →</a>
            </div>

            <!-- ATELIER 4 -->
            <div class="atelier-card clickable card-4" style="border-color: rgba(255,68,68,0.3);">
                <div class="card-bg-number" style="color: rgba(255,68,68,0.1);">04</div>
                <div class="atelier-title">Scénarios Opérationnels</div>
                <div class="atelier-subtitle">Atelier 4 — EBIOS RM</div>
                <div><span class="status-badge status-ok">✅ Disponible</span></div>
                <div class="atelier-desc">
                    Atelier collaboratif et participatif. Les acteurs de terrain proposent des scénarios "cauchemars" évalués en groupe via le <strong>Poker du Risque</strong> (vote secret, débat, cotation EBIOS).
                </div>
                <div class="concept-badges">
                    <span class="cbadge cb-r">R</span>
                    <span class="cbadge cb-sr">SR</span>
                    <span class="cbadge cb-vm">VM</span>
                </div>
                <a href="mj_setup.php" class="card-btn btn-red">Démarrer l'Atelier 4 →</a>
            </div>

        </div>

        <!-- Note Atelier 5 -->
        <div style="margin-top: 20px; padding: 14px 20px; background: rgba(0,230,184,0.05); border: 1px solid rgba(0,230,184,0.2); border-radius: 8px; text-align: center;">
            <p style="margin: 0; font-size: 0.875rem; color: #8b949e;">
                💡 <strong style="color: var(--accent-green);">Atelier 5 — Traitement du Risque (PACS)</strong> est directement intégré au
                <a href="registre_risques.php" style="color: var(--accent-green); text-decoration: none; font-weight: bold;">Registre des Risques</a>.
            </p>
        </div>

    </div>
</body>
</html>
