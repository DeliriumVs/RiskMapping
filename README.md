# 🧭 RiskMapping Suite

**RiskMapping Suite** est une plateforme open-source de pilotage des risques cyber et d'amélioration continue, structurée autour de la méthodologie **EBIOS RM** (ANSSI).

Pensée pour les RSSI, les qualiticiens et les équipes cybersécurité, elle permet de centraliser le registre des risques, de cartographier l'exposition de l'entreprise, et de piloter le **Plan d'Action Continu de Sécurité (PACS)**.

![Version](https://img.shields.io/badge/version-1.1-blue.svg)
![Methodology](https://img.shields.io/badge/methodology-EBIOS_RM-success.svg)
![Security](https://img.shields.io/badge/security-Enterprise_Grade-red.svg)

## ✨ Fonctionnalités Principales

### 📊 Pilotage et Amélioration Continue (GRC)
* **Registre Dynamique :** Centralisation des risques traités, évaluation de la gravité/vraisemblance et calcul automatique des niveaux de criticité.
* **Cartographie (Heatmap) :** Matrice visuelle de l'exposition aux risques (séparée en quadrants classiques) générée en temps réel.
* **Plan de Traitement (PACS) :** Sous chaque risque, un module complet de gestion de projet permet de décliner la stratégie (Réduire, Transférer...) en actions opérationnelles :
  * Assignation de porteurs (Responsables).
  * Suivi des échéances et statuts (À faire, En cours, Bloqué, Terminé).
  * Intégration fluide avec les outils IT existants via des liens directs vers vos tickets (Jira, ServiceNow, GLPI...).
* **Reporting Exécutif :** Génération de rapports PDF propres et formatés pour l'impression (mode paysage, saut de page, nettoyage visuel), idéaux pour les comités de direction (CODIR).

### 🎯 Ateliers d'Identification (Module Participatif)
La plateforme intègre un module de gamification pour faciliter l'identification initiale des risques avec les métiers :
* **Idéation Guidée :** Création de scénarios ("cauchemars") liés aux valeurs métiers (DICP) et aux sources de menaces de l'entreprise.
* **Poker du Risque :** Évaluation de la gravité en groupe via un système de débat suivi d'un vote secret (Scrum) sur smartphone ou PC.

### 🛡️ Sécurité & Gouvernance (Enterprise-Grade)
* **Piste d'Audit (Audit Trail) :** Traçabilité totale. Toutes les actions sensibles (élévation de privilèges, créations, suppressions, modifications) sont journalisées et horodatées.
* **RBAC Strict :** Séparation des rôles hermétique (`Admin`, `Animateur`, `Lecteur`).
* **Provisioning :** Quarantaine des nouveaux inscrits avec validation manuelle par l'administrateur.
* **Standard ANSSI :** Protection des accès par algorithme de hachage **Argon2id**.

---

## 🛠️ Architecture & Déploiement

RiskMapping Suite est construite pour être robuste, portable et sécurisée (approche *DevSecOps*).
Elle repose sur une architecture **API-First** (Single Page Application consommant une API REST) et est intégralement conteneurisée.

### Prérequis
* Docker & Docker Compose

### Démarrage rapide
1. Clonez ce dépôt.
2. Démarrez l'infrastructure via Docker :
   ```bash
   docker compose up -d
   ```
3. Accédez à l'application via votre navigateur : `http://localhost` (ou l'IP de votre serveur).

### ⚠️ Premier accès
Lors du premier lancement, un compte administrateur de secours est provisionné :
* **Identifiant :** `admin`
* **Mot de passe :** `EBIOSRM`

> **Action requise :** Connectez-vous, rendez-vous dans l'onglet "Comptes", créez votre propre accès Administrateur nominatif, puis **verrouillez ou supprimez** le compte par défaut.

---

## 🧹 Maintenance & Sauvegarde

L'application stocke ses données de manière persistante via un volume Docker.
Pour une remise à zéro complète (⚠️ **Attention : efface le registre, le PACS et l'audit**) :
```bash
docker compose down -v
docker compose up -d
```
