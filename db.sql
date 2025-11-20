-- =========================================
-- TABLE VILLES
-- =========================================
CREATE TABLE villes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE
);

-- =========================================
-- TABLE CENTRES
-- =========================================
CREATE TABLE centres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    ville_id INT NOT NULL,
    FOREIGN KEY (ville_id) REFERENCES villes(id) ON DELETE CASCADE
);

-- =========================================
-- TABLE UTILISATEURS
-- =========================================
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    telephone VARCHAR(30) DEFAULT NULL,
    role ENUM('admin', 'medecin', 'infirmier', 'patient') NOT NULL,
    mot_de_passe VARCHAR(255) NOT NULL,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    centre_id INT DEFAULT NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    police VARCHAR(50) DEFAULT 'Benton Sans',
    FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL
);

-- =========================================
-- TABLE PATIENTS
-- =========================================
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prenom VARCHAR(100) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    date_naissance DATE DEFAULT NULL,
    sexe ENUM('Homme', 'Femme', 'Autre') DEFAULT 'Autre',
    telephone VARCHAR(30) DEFAULT NULL,
    centre_id INT DEFAULT NULL,
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    date_enregistrement TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (centre_id) REFERENCES centres(id) ON DELETE SET NULL
);

-- =========================================
-- TABLE CONSULTATIONS
-- =========================================
CREATE TABLE consultations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_id INT NOT NULL,
    infirmier_id INT DEFAULT NULL,
    medecin_id INT DEFAULT NULL,
    tension VARCHAR(50) NOT NULL,
    temperature DECIMAL(4,1) NOT NULL,
    symptomes TEXT NOT NULL,
    observations TEXT DEFAULT NULL,
    fichier VARCHAR(255) DEFAULT NULL,
    statut ENUM('envoyée', 'validée', 'archivée') DEFAULT 'envoyée',
    date_consultation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    diagnostic TEXT DEFAULT NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE,
    FOREIGN KEY (infirmier_id) REFERENCES utilisateurs(id) ON DELETE SET NULL,
    FOREIGN KEY (medecin_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
);

-- =========================================
-- TABLE REPONSES
-- =========================================
CREATE TABLE  reponses_medicales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consultation_id INT NOT NULL,
    medecin_id INT NOT NULL,
    reponse TEXT NOT NULL,
    fichier VARCHAR(255) DEFAULT NULL,
    date_reponse TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    diagnostic TEXT DEFAULT NULL,
    ordonnance TEXT DEFAULT NULL,
    fichier_ordonnance VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (consultation_id) REFERENCES consultations(id) ON DELETE CASCADE,
    FOREIGN KEY (medecin_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- =========================================
-- TABLE MESSAGES
-- =========================================
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    message VARCHAR(255) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    vu TINYINT(1) DEFAULT 0,
    lu TINYINT(1) DEFAULT 0,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);


-- =========================================
-- TABLE NOTIFICATIONS
-- =========================================
CREATE TABLE notifications (
    id INT(11) NOT NULL AUTO_INCREMENT,
    utilisateur_id INT(11) NOT NULL,
    message VARCHAR(255) NOT NULL,
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP,
    vu TINYINT(1) NOT NULL DEFAULT 0,
    lu TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    INDEX idx_notif_user (utilisateur_id),
    CONSTRAINT fk_notification_user FOREIGN KEY (utilisateur_id)
        REFERENCES utilisateurs(id)
        ON DELETE CASCADE
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;
