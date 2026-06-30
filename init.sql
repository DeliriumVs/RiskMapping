-- init.sql — RiskMapping Suite
-- Schéma complet : multi-entité / multi-analyse

SET NAMES utf8mb4;

-- ============================================================
-- 1. UTILISATEURS, RÔLES ET AUDIT
-- ============================================================

CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password_hash VARCHAR(255),
    role ENUM('admin', 'animateur', 'lecteur', 'en_attente') DEFAULT 'en_attente',
    motif_demande TEXT NULL,
    is_locked TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. ENTITÉS ET ANALYSES DE RISQUES
-- ============================================================

CREATE TABLE entites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    secteur VARCHAR(100) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE analyses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    entite_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,
    perimetre TEXT NULL,
    date_debut DATE NULL,
    statut ENUM('en_cours', 'finalisee', 'archivee') DEFAULT 'en_cours',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entite_id) REFERENCES entites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. RÉFÉRENTIELS (scopés par analyse)
-- ============================================================

CREATE TABLE equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analyse_id INT NOT NULL,
    nom VARCHAR(100) NOT NULL,
    FOREIGN KEY (analyse_id) REFERENCES analyses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE valeurs_metier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analyse_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    critere_impacte VARCHAR(50) NOT NULL,
    description TEXT,
    FOREIGN KEY (analyse_id) REFERENCES analyses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analyse_id      INT NOT NULL,
    type_source     VARCHAR(100) NOT NULL,
    motivation      VARCHAR(255) NULL,
    motivation_note TINYINT NULL DEFAULT NULL COMMENT '1=Faible 2=Moyen 3=Élevé',
    ressources_note TINYINT NULL DEFAULT NULL COMMENT '1=Faible 2=Moyen 3=Élevé',
    activite_note   TINYINT NULL DEFAULT NULL COMMENT '1=Faible 2=Moyen 3=Élevé (optionnel)',
    FOREIGN KEY (analyse_id) REFERENCES analyses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE biens_supports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analyse_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,
    type_bien ENUM(
        'Logiciel / Application',
        'Infrastructure réseau',
        'Serveur / Cloud',
        'Poste de travail',
        'Personne / Équipe',
        'Site / Local',
        'Autre'
    ) NOT NULL DEFAULT 'Autre',
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analyse_id) REFERENCES analyses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE valeur_bien_support (
    valeur_metier_id INT NOT NULL,
    bien_support_id  INT NOT NULL,
    PRIMARY KEY (valeur_metier_id, bien_support_id),
    FOREIGN KEY (valeur_metier_id) REFERENCES valeurs_metier(id) ON DELETE CASCADE,
    FOREIGN KEY (bien_support_id)  REFERENCES biens_supports(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE objectifs_vises (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    analyse_id INT NOT NULL,
    menace_id  INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    pertinence ENUM('A évaluer', 'Retenu', 'Non retenu') NOT NULL DEFAULT 'A évaluer',
    notes      TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analyse_id) REFERENCES analyses(id)  ON DELETE CASCADE,
    FOREIGN KEY (menace_id)  REFERENCES menaces(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE parties_prenantes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    analyse_id     INT NOT NULL,
    nom            VARCHAR(200) NOT NULL,
    type_pp        ENUM('Interne','Externe','Partenaire','Fournisseur','Client','Prestataire','Autre') NOT NULL DEFAULT 'Externe',
    description    VARCHAR(255) NULL,
    dependance     TINYINT NOT NULL DEFAULT 1 COMMENT '1=Faible 2=Moyenne 3=Forte 4=Critique',
    penetration    TINYINT NOT NULL DEFAULT 1 COMMENT '1=Très limitée 2=Partielle 3=Significative 4=Totale',
    maturite_cyber TINYINT NOT NULL DEFAULT 1 COMMENT '1=Faible 2=Limitée 3=Correcte 4=Avancée',
    confiance      TINYINT NOT NULL DEFAULT 1 COMMENT '1=Inconnue 2=Limitée 3=Établie 4=Forte',
    created_at     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analyse_id) REFERENCES analyses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. SCÉNARIOS DE RISQUES
-- ============================================================

CREATE TABLE scenarios_bruts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    analyse_id INT NOT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT,
    impact_estime INT DEFAULT 0,
    vraisemblance_estimee INT DEFAULT 0,
    niveau_ebios INT DEFAULT 0,
    priorite INT DEFAULT 0,
    strategie_traitement VARCHAR(50) DEFAULT 'À définir',
    justification_traitement TEXT,
    justification_impact TEXT,
    justification_vraisemblance TEXT,
    statut_qualification ENUM('a_qualifier', 'qualifie') NOT NULL DEFAULT 'a_qualifier',
    titre_technique VARCHAR(255) NULL,
    scenario_technique TEXT NULL,
    traitement_updated_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analyse_id) REFERENCES analyses(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. SCÉNARIOS STRATÉGIQUES (ATELIER 3 : SR × PP → OV)
-- ============================================================

CREATE TABLE scenarios_strategiques (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    analyse_id    INT NOT NULL,
    menace_id     INT NOT NULL,
    pp_id         INT NOT NULL,
    ov_id         INT NULL,
    description   TEXT NULL,
    gravite       TINYINT NOT NULL DEFAULT 0,
    vraisemblance TINYINT NOT NULL DEFAULT 0,
    statut        ENUM('a_evaluer','retenu','non_retenu') NOT NULL DEFAULT 'a_evaluer',
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analyse_id) REFERENCES analyses(id)           ON DELETE CASCADE,
    FOREIGN KEY (menace_id)  REFERENCES menaces(id)            ON DELETE CASCADE,
    FOREIGN KEY (pp_id)      REFERENCES parties_prenantes(id)  ON DELETE CASCADE,
    FOREIGN KEY (ov_id)      REFERENCES objectifs_vises(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. ÉVÉNEMENTS REDOUTÉS (ATELIER 1)
-- ============================================================

CREATE TABLE evenements_redoutes (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    analyse_id       INT NOT NULL,
    valeur_metier_id INT NOT NULL,
    categorie        ENUM('Financier','Opérationnel','Juridique','Image','Santé') NOT NULL,
    description      VARCHAR(255) NOT NULL,
    impact           ENUM('Mineur','Significatif','Majeur','Critique') NOT NULL DEFAULT 'Mineur',
    notes            TEXT NULL,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (analyse_id)       REFERENCES analyses(id)       ON DELETE CASCADE,
    FOREIGN KEY (valeur_metier_id) REFERENCES valeurs_metier(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. PLAN D'ACTIONS DE TRAITEMENT (PAC)
-- ============================================================

CREATE TABLE actions_traitement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scenario_id INT,
    titre VARCHAR(255) NOT NULL,
    responsable VARCHAR(100),
    date_cible DATE NULL,
    lien_ticket VARCHAR(255) NULL,
    statut ENUM('a_faire', 'en_cours', 'fait', 'bloque') DEFAULT 'a_faire',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scenario_id) REFERENCES scenarios_bruts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. DONNÉES DE DÉMONSTRATION
-- ============================================================

INSERT INTO entites (nom, secteur, description) VALUES
('Entreprise de démonstration', 'Industrie / PME',
 'Jeu de données EBIOS RM — à remplacer par les données réelles du client');

INSERT INTO analyses (entite_id, nom, perimetre, date_debut) VALUES
(1, 'Analyse EBIOS RM 2025',
 'Système d''information global de l''organisation',
 '2025-01-01');

INSERT INTO equipes (analyse_id, nom) VALUES
(1, 'Direction / CODIR'),
(1, 'DSI / Informatique'),
(1, 'Ressources Humaines'),
(1, 'Production / Opérations'),
(1, 'Finance / Comptabilité'),
(1, 'Marketing / Communication'),
(1, 'Juridique / Conformité');

INSERT INTO valeurs_metier (analyse_id, nom, critere_impacte, description) VALUES
(1, 'Processus de facturation',  'Disponibilité',  'Génération du CA de l''entreprise'),
(1, 'Base de données Clients',   'Confidentialité','Données à caractère personnel (RGPD)'),
(1, 'Image de marque',           'Image',          'Réputation sur le marché et confiance des partenaires'),
(1, 'Code source / R&D',         'Confidentialité','Propriété intellectuelle et avantage concurrentiel');

INSERT INTO menaces (analyse_id, type_source, motivation, motivation_note, ressources_note, activite_note) VALUES
(1, 'Cybercriminel (Ransomware)',    'Appât du gain / Extorsion',        3, 2, NULL),
(1, 'Employé malveillant',           'Vengeance / Sabotage interne',     2, 1, NULL),
(1, 'Concurrent déloyal',            'Espionnage industriel',             2, 3, 1),
(1, 'Hacktiviste',                   'Idéologie / Dégradation d''image', 3, 1, NULL);

-- Parties Prenantes de démonstration (analyse 1)
INSERT INTO parties_prenantes (analyse_id, nom, type_pp, description, dependance, penetration, maturite_cyber, confiance) VALUES
(1, 'Prestataire infogérance (DSI externalisée)', 'Prestataire', 'Accès complet aux serveurs et AD',         4, 4, 2, 2),
(1, 'Éditeur ERP (support VPN)',                  'Fournisseur', 'Accès VPN pour maintenance applicative',    3, 3, 2, 3),
(1, 'Fournisseur Cloud (hébergement)',             'Fournisseur', 'Hébergement IaaS, données en transit',      3, 2, 3, 3),
(1, 'Experts-comptables (cabinet externe)',        'Partenaire',  'Accès en lecture aux données financières',  2, 2, 1, 2),
(1, 'Collaborateurs internes (RH / Finance)',      'Interne',     'Accès privilégié aux systèmes métier',      4, 3, 2, 3);

-- Événements Redoutés (exemples liés aux VM ci-dessus : VM id 1-4, analyse 1)
INSERT INTO evenements_redoutes (analyse_id, valeur_metier_id, categorie, description, impact, notes) VALUES
(1, 1, 'Financier',     'Interruption du processus de facturation (>48h)',  'Critique',     'Perte de CA directe, pénalités contractuelles'),
(1, 1, 'Opérationnel',  'Erreurs massives dans les montants facturés',       'Majeur',       'Risque de litige client'),
(1, 2, 'Juridique',     'Divulgation non autorisée de données clients (RGPD)','Critique',    'Amendes CNIL, perte de confiance'),
(1, 2, 'Image',         'Publication de la base clients sur un forum',        'Majeur',      'Impact médiatique potentiel'),
(1, 3, 'Image',         'Défacement du site web ou campagne de dénigrement',  'Significatif','Atteinte à la réputation'),
(1, 4, 'Financier',     'Vol du code source par un concurrent',               'Critique',    'Perte d''avantage concurrentiel'),
(1, 4, 'Opérationnel',  'Destruction irréversible des dépôts de code',        'Majeur',      '');
