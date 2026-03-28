# 🧭 RiskMapping Suite

**RiskMapping** est une application web d'animation d'ateliers d'analyse de risques collaboratifs, inspirée de la méthodologie **EBIOS RM** (ANSSI). 

Conçue pour les facilitateurs et les équipes cybersécurité, elle permet de transformer l'identification et la cotation des risques en une expérience interactive, fluide et parfaitement tracée.

![Version](https://img.shields.io/badge/version-1.0-blue.svg)
![Architecture](https://img.shields.io/badge/architecture-API_REST-success.svg)
![Security](https://img.shields.io/badge/security-Enterprise_Grade-red.svg)

## ✨ Fonctionnalités Principales

### 🎯 Pour les Participants (Mode Atelier)
* **Idéation Guidée :** Création de scénarios de risques ("cauchemars") avec une approche narrative (Événement redouté + Conséquences).
* **Cadrage EBIOS (Optionnel) :** Liaison multi-critères aux Valeurs Métier (Disponibilité, Confidentialité, etc.) et aux Sources de Menaces via une interface intuitive.
* **Poker du Risque :** Évaluation secrète et en temps réel de la Gravité et de la Vraisemblance depuis un smartphone ou un PC.

### 🛡️ Pour l'Équipe Sécurité (Mode Expert)
* **Single Page Application (SPA) :** Tableau de bord dynamique, navigation et gestion des données sans aucun rechargement de page.
* **Heatmap & Registre :** Visualisation matricielle des risques, tri drag-and-drop, calcul automatique de la criticité et suivi des plans de traitement.
* **Animation en direct :** Gestion du timer de débat, prise de notes à la volée, déclenchement et clôture forcée des votes.
* **Gestion des Référentiels :** Catalogues administrables pour les Valeurs Métier, les Menaces et les Équipes de l'entreprise.
* **Exports Professionnels :** Génération de rapports PDF (formatage d'impression dédié) et extraction CSV.

### 🔒 Sécurité & Conformité (Enterprise-Grade)
* **RBAC (Role-Based Access Control) strict :**
  * `Admin` : Accès total (Comptes, Audit, Référentiels, Ateliers).
  * `Animateur` : Pilotage des ateliers et gestion du registre des risques.
  * `Lecteur` : Consultation seule (Read-only).
* **Provisioning & Self-Service :** Formulaire de demande d'accès avec mise en attente (quarantaine) et validation manuelle par un administrateur.
* **Cryptographie robuste :** Hachage des mots de passe via l'algorithme **Argon2id** (Standard ANSSI/OWASP) et politique de complexité stricte.
* **Piste d'Audit (Audit Trail) :** Journalisation systématique en base de données de toutes les actions sensibles (Connexions, ajouts, suppressions) avec horodatage, ID utilisateur et adresse IP.

---

## 🛠️ Stack Technique & Architecture

L'application repose sur une architecture moderne de type **API-First** (séparation stricte du Front-end et du Back-end) :

* **Backend (API REST) :** PHP 8 (Vanilla, PDO). Les points de terminaison (`/api_*.php`) sécurisent les transactions et distribuent les données au format JSON.
* **Base de données :** MySQL 8 (InnoDB, relationnel Many-to-Many, encodage `utf8mb4_unicode_ci`).
* **Frontend (SPA) :** HTML5, CSS3, JavaScript Vanilla (utilisation de la `Fetch API` moderne pour la consommation de l'API REST).
* **Déploiement :** Docker & Docker Compose (Conteneurisation complète).

---

## 🚀 Installation & Déploiement

L'application est entièrement conteneurisée pour un déploiement immédiat.

### Prérequis
* Docker & Docker Compose installés sur le serveur hôte.

### Démarrage
1. Clonez ce dépôt.
2. Ouvrez un terminal à la racine du projet (où se trouve le fichier `docker-compose.yml`).
3. Lancez le build et le montage des conteneurs :
   ```bash
   docker compose up -d
   ```
4. Accédez à l'application via votre navigateur : `http://localhost` (ou l'IP/Domaine de votre serveur).

### ⚠️ Premier accès Administrateur
Lors du tout premier lancement, l'application génère automatiquement un compte super-administrateur de secours :
* **Identifiant :** `admin`
* **Mot de passe :** `EBIOSRM`

> **Action requise :** Connectez-vous immédiatement, rendez-vous dans l'onglet "Comptes", créez votre propre accès Administrateur nominatif, puis **verrouillez ou supprimez** le compte par défaut.

---

## 📂 Structure du projet

```text
/
├── docker-compose.yml       # Configuration de l'infrastructure
├── Dockerfile               # Recette de l'image PHP/Apache
├── init.sql                 # Schéma de base de données (Déployé au 1er lancement)
└── src/                     # Code source de l'application
    ├── api_*.php            # Contrôleurs Backend (API REST renvoyant du JSON)
    ├── view_*.php           # Vues Frontend (Single Page Application)
    ├── admin_*.php          # Interfaces d'administration et référentiels
    ├── mj_*.php             # Modules d'animation de session (Maitre du Jeu)
    ├── db.php               # Moteur de BDD et fonction globale d'Audit (log_audit)
    └── index.php            # Point d'entrée public
```

---

## 🧹 Maintenance

Pour réinitialiser complètement l'application (⚠️ **Attention, cela efface toutes les bases de données, comptes et configurations d'audit**) :
```bash
docker compose down -v
docker compose up -d
```
