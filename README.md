# 🧭 RiskMapping Suite

RiskMapping est une application web d'animation d'ateliers d'analyse de risques collaboratifs, inspirée de la méthodologie **EBIOS RM** (ANSSI). 

Elle permet de transformer l'identification et la cotation des risques cyber (ou métiers) en une expérience interactive, engageante et parfaitement tracée.

## ✨ Fonctionnalités Principales

### 🎯 Pour les Participants (Mode Atelier)
* **Idéation Guidée :** Création de scénarios de risques ("cauchemars") avec une approche narrative (Événement + Conséquences).
* **Cadrage EBIOS (Optionnel) :** Liaison multi-critères aux Valeurs Métier (Disponibilité, Confidentialité, etc.) et aux Sources de Menaces.
* **Poker du Risque :** Évaluation secrète et en temps réel de la Gravité et de la Vraisemblance depuis un smartphone ou un PC.

### 🛡️ Pour l'Équipe Sécurité (Mode Expert)
* **Tableau de bord dynamique (AJAX) :** Visualisation de la matrice des risques (Heatmap) et gestion du registre sans rechargement de page.
* **Animation en direct :** Gestion du timer de débat, prise de notes à la volée, déclenchement et clôture des votes, calcul automatique de la criticité.
* **Gestion des Référentiels :** Catalogues administrables pour les Valeurs Métier, les Menaces et les Équipes (Architecture de données relationnelle Many-to-Many).
* **Exports Professionnels :** Génération de rapports PDF (avec formatage d'impression spécifique) et extraction CSV pour intégration dans d'autres outils SIEM/GRC.

### 🔒 Sécurité & Conformité (Enterprise-Grade)
* **RBAC (Contrôle d'accès basé sur les rôles) :** * `Admin` : Accès total (Gestion des comptes, audit, référentiels, ateliers).
  * `Animateur` : Création et pilotage d'ateliers, gestion du registre des risques.
  * `Lecteur` : Consultation seule du tableau de bord.
* **Provisioning & Self-Service :** Formulaire de demande d'accès avec mise en attente et validation par un administrateur.
* **Cryptographie :** Hachage des mots de passe via l'algorithme robuste **Argon2id**. Application stricte de la complexité des mots de passe.
* **Piste d'Audit (Audit Trail) :** Journalisation systématique en base de données de toutes les actions sensibles (Connexions, suppressions, élévations de privilèges) avec horodatage, ID utilisateur et adresse IP.

---

## 🛠️ Stack Technique

* **Backend :** PHP 8 (Vanilla, PDO)
* **Base de données :** MySQL 8 (InnoDB, UTF-8 strict : `utf8mb4_unicode_ci`)
* **Frontend :** HTML5, CSS3, JavaScript Vanilla (AJAX/Fetch API)
* **Librairies externes :** SortableJS (via CDN) pour le glisser-déposer du registre.
* **Infrastructure :** Docker & Docker Compose (Conteneurisation complète).

---

## 🚀 Installation & Déploiement

L'application est entièrement conteneurisée pour un déploiement immédiat.

### Prérequis
* Docker & Docker Compose installés sur la machine hôte.

### Démarrage
1. Clonez ce dépôt.
2. Ouvrez un terminal à la racine du projet (où se trouve le fichier `docker-compose.yml`).
3. Lancez la commande suivante :
   ```bash
   docker compose up -d
   ```
4. Accédez à l'application via votre navigateur : `http://localhost` (ou l'IP de votre serveur).

### Premier accès Administrateur
Lors du tout premier lancement, l'application génère automatiquement un compte super-administrateur de secours :
* **Identifiant :** `admin`
* **Mot de passe :** `EBIOSRM`

> ⚠️ **Sécurité :** Il est impératif de vous connecter immédiatement, de créer un nouveau compte Administrateur nominatif avec un mot de passe fort, puis de supprimer ou verrouiller ce compte par défaut.

---

## 📂 Structure du projet

```text
/
├── docker-compose.yml       # Configuration de l'infrastructure
├── Dockerfile               # Recette de l'image PHP/Apache
├── init.sql                 # Schéma de base de données (Déployé au 1er lancement)
└── src/                     # Code source de l'application (Monté en volume)
    ├── db.php               # Connexion BDD et fonction globale d'Audit (log_audit)
    ├── index.php            # Accueil / Choix du parcours
    ├── join.php             # SAS d'entrée pour les participants (PIN)
    ├── admin_login.php      # Portail d'authentification Expert
    ├── admin_register.php   # Formulaire de demande d'accès
    ├── registre_risques.php # Hub central / Tableau de bord (Coque AJAX)
    └── ...                  # Modules métiers et référentiels
```

---

## 🧹 Maintenance

Pour réinitialiser complètement l'application (⚠️ **Attention, cela efface toutes les données, comptes et configurations**) :
```bash
docker compose down -v
docker compose up -d
```
