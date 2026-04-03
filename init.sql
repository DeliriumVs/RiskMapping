-- init.sql

SET NAMES utf8mb4;

-- =========================================================
-- 1. UTILISATEURS, RÔLES ET AUDIT
-- =========================================================

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

-- =========================================================
-- 2. STRUCTURE DES SESSIONS ET PARTICIPANTS
-- =========================================================

CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_session VARCHAR(255),
    code_session VARCHAR(10),
    max_reacteurs_par_scenario INT DEFAULT 3,
    statut ENUM('configuration', 'saisie', 'discussion', 'termine') DEFAULT 'configuration',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT,
    pseudo VARCHAR(50),
    role VARCHAR(50),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 3. CATALOGUES ET RÉFÉRENTIELS (EBIOS RM)
-- =========================================================

CREATE TABLE equipes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) UNIQUE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE valeurs_metier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    critere_impacte VARCHAR(50) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE menaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_source VARCHAR(100) NOT NULL,
    motivation VARCHAR(150),
    niveau_capacite VARCHAR(50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 4. TABLES MÉTIERS (IDÉATION & VOTES)
-- =========================================================

CREATE TABLE scenarios_bruts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT,
    auteur_id INT,
    titre VARCHAR(255),
    description TEXT,
    statut ENUM('en_attente', 'discussion', 'vote', 'resultat', 'traite') DEFAULT 'en_attente',
    impact_estime INT DEFAULT 0,
    vraisemblance_estimee INT DEFAULT 0,
    niveau_ebios INT DEFAULT 0,
    priorite INT DEFAULT 0,
    strategie_traitement VARCHAR(50) DEFAULT 'À définir',
    justification_traitement TEXT,
    justification_impact TEXT,
    justification_vraisemblance TEXT,
    commentaire_global TEXT NULL,
    statut_qualification ENUM('a_qualifier', 'qualifie') NOT NULL DEFAULT 'a_qualifier',
    scenario_technique TEXT NULL,
    traitement_updated_at TIMESTAMP NULL,
    timer_end_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id),
    FOREIGN KEY (auteur_id) REFERENCES participants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE scenario_valeurs_metier (
    scenario_id INT,
    valeur_metier_id INT,
    PRIMARY KEY (scenario_id, valeur_metier_id),
    FOREIGN KEY (scenario_id) REFERENCES scenarios_bruts(id) ON DELETE CASCADE,
    FOREIGN KEY (valeur_metier_id) REFERENCES valeurs_metier(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE scenario_menaces (
    scenario_id INT,
    menace_id INT,
    PRIMARY KEY (scenario_id, menace_id),
    FOREIGN KEY (scenario_id) REFERENCES scenarios_bruts(id) ON DELETE CASCADE,
    FOREIGN KEY (menace_id) REFERENCES menaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE contributions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scenario_id INT,
    participant_id INT,
    notes_mj TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (scenario_id) REFERENCES scenarios_bruts(id),
    FOREIGN KEY (participant_id) REFERENCES participants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE votes_poker (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scenario_id INT,
    participant_id INT,
    impact_vote INT NOT NULL,
    vraisemblance_vote INT NOT NULL,
    UNIQUE KEY(scenario_id, participant_id),
    FOREIGN KEY (scenario_id) REFERENCES scenarios_bruts(id),
    FOREIGN KEY (participant_id) REFERENCES participants(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 5. BIENS SUPPORTS (ATELIER 1)
-- =========================================================

CREATE TABLE biens_supports (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE valeur_bien_support (
    valeur_metier_id INT NOT NULL,
    bien_support_id  INT NOT NULL,
    PRIMARY KEY (valeur_metier_id, bien_support_id),
    FOREIGN KEY (valeur_metier_id) REFERENCES valeurs_metier(id)   ON DELETE CASCADE,
    FOREIGN KEY (bien_support_id)  REFERENCES biens_supports(id)   ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 6. INSERTION DES DONNÉES PAR DÉFAUT
-- =========================================================

INSERT INTO equipes (nom) VALUES 
('Direction / CODIR'), ('DSI / Informatique'), ('Ressources Humaines'), 
('Production / Opérations'), ('Finance / Comptabilité'), 
('Marketing / Communication'), ('Juridique / Conformité');

INSERT INTO valeurs_metier (nom, critere_impacte, description) VALUES 
('Processus de facturation', 'Disponibilité', 'Génération du CA de l''entreprise'),
('Base de données Clients', 'Confidentialité', 'Données à caractère personnel (RGPD)'),
('Image de marque', 'Image', 'Réputation sur le marché et confiance des partenaires'),
('Code source / R&D', 'Confidentialité', 'Propriété intellectuelle et avantage concurrentiel');

INSERT INTO menaces (type_source, motivation, niveau_capacite) VALUES 
('Cybercriminel (Ransomware)', 'Appât du gain / Extorsion', 'Élevée'),
('Employé malveillant', 'Vengeance / Sabotage', 'Standard'),
('Concurrent déloyal', 'Espionnage industriel', 'Élevée'),
('Hacktiviste', 'Idéologie / Dégradation d''image', 'Modérée');

-- =========================================================
-- 7. OBJECTIFS VISÉS — COUPLES SR/OV (ATELIER 2)
-- =========================================================

CREATE TABLE objectifs_vises (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    menace_id  INT NOT NULL,
    description VARCHAR(255) NOT NULL,
    pertinence ENUM('A évaluer', 'Retenu', 'Non retenu') NOT NULL DEFAULT 'A évaluer',
    notes      TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (menace_id) REFERENCES menaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================================
-- 8. SUIVI DES ACTIONS DE TRAITEMENT (PACS)
-- =========================================================
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
