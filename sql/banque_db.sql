-- ============================================================
-- SCRIPT SQL - BANQUE_DB
-- Projet scolaire de site bancaire
-- À importer dans phpMyAdmin (XAMPP)
-- ============================================================

-- Créer et sélectionner la base de données
CREATE DATABASE IF NOT EXISTS banque_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE banque_db;

-- ============================================================
-- TABLE : utilisateurs
-- Contient tous les comptes (admin, employé, client)
-- Le rôle est stocké directement ici pour rester simple
-- ============================================================
CREATE TABLE utilisateurs (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(100) NOT NULL,
    prenom      VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,   -- stocké avec password_hash()
    role        ENUM('admin', 'employe', 'client') NOT NULL DEFAULT 'client',
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- TABLE : employes
-- Lien entre un utilisateur et son profil employé
-- Relation : utilisateurs.id <-> employes.utilisateur_id
-- ============================================================
CREATE TABLE employes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE : clients
-- Lien entre un utilisateur et son profil client
-- Chaque client est associé à un employé via employe_id
-- ============================================================
CREATE TABLE clients (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT NOT NULL,
    employe_id      INT NOT NULL,         -- l'employé responsable de ce client
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (employe_id)     REFERENCES employes(id) ON DELETE RESTRICT
);

-- ============================================================
-- TABLE : comptes
-- Compte bancaire d'un client (IBAN fictif, solde)
-- Un client peut avoir plusieurs comptes
-- ============================================================
CREATE TABLE comptes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    client_id   INT NOT NULL,
    iban        VARCHAR(34) NOT NULL UNIQUE,
    solde       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- ============================================================
-- TABLE : transactions
-- Historique de toutes les opérations bancaires
-- type_operation : 'depot', 'retrait', 'virement'
-- ============================================================
CREATE TABLE transactions (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    compte_id       INT NOT NULL,
    type_operation  ENUM('depot', 'retrait', 'virement') NOT NULL,
    montant         DECIMAL(15,2) NOT NULL,
    description     VARCHAR(255) DEFAULT '',
    date_operation  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (compte_id) REFERENCES comptes(id) ON DELETE CASCADE
);

-- ============================================================
-- DONNÉES DE DÉMONSTRATION
-- Mots de passe : tous sont "password123"
-- (hashés avec password_hash('password123', PASSWORD_DEFAULT))
-- ============================================================

-- Insertion des utilisateurs de test
-- Le hash ci-dessous correspond à : password123
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Dupont',   'Marie',    'admin@banque.fr',    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Martin',   'Jean',     'employe1@banque.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employe'),
('Bernard',  'Sophie',   'employe2@banque.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'employe'),
('Lefebvre', 'Pierre',   'client1@banque.fr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client'),
('Moreau',   'Claire',   'client2@banque.fr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client'),
('Simon',    'Thomas',   'client3@banque.fr',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client');

-- Création des profils employés (liés aux utilisateurs id=2 et id=3)
INSERT INTO employes (utilisateur_id) VALUES (2), (3);

-- Création des profils clients
-- client1 (utilisateur id=4) -> employé id=1
-- client2 (utilisateur id=5) -> employé id=1
-- client3 (utilisateur id=6) -> employé id=2
INSERT INTO clients (utilisateur_id, employe_id) VALUES
(4, 1),
(5, 1),
(6, 2);

-- Création des comptes bancaires avec IBAN fictifs
INSERT INTO comptes (client_id, iban, solde) VALUES
(1, 'FR76 3000 6000 0112 3456 7890 185', 5420.50),
(1, 'FR76 3000 6000 0198 7654 3210 156', 1200.00),
(2, 'FR76 3000 6000 0145 6789 0123 478', 8750.25),
(3, 'FR76 3000 6000 0167 8901 2345 623', 320.75);

-- Historique de transactions pour les comptes
INSERT INTO transactions (compte_id, type_operation, montant, description) VALUES
(1, 'depot',   2000.00, 'Virement salaire novembre'),
(1, 'retrait',  150.00, 'Retrait DAB'),
(1, 'depot',    500.00, 'Remboursement ami'),
(1, 'retrait',   89.50, 'Achat en ligne'),
(2, 'depot',   1200.00, 'Épargne mensuelle'),
(3, 'depot',   3000.00, 'Virement salaire novembre'),
(3, 'retrait',  200.00, 'Retrait DAB'),
(3, 'virement', 500.00, 'Virement vers compte épargne'),
(4, 'depot',    320.75, 'Remboursement'),
(4, 'retrait',   50.00, 'Retrait DAB');
