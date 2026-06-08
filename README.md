# Projet Bancaire PHP/MySQL — Documentation complète

## Identifiants de démonstration

|Rôle|Email|Mot de passe|
|-|-|-|
|Admin|admin@banque.fr|password123|
|Employé|employe1@banque.fr|password123|
|Employé|employe2@banque.fr|password123|
|Client|client1@banque.fr|password123|
|Client|client2@banque.fr|password123|
|Client|client3@banque.fr|password123|

\---

## Installation sur XAMPP (étape par étape)

### 1\. Installer XAMPP

Télécharger sur https://www.apachefriends.org et installer.

### 2\. Copier le projet

Copier le dossier `banque/` dans :

* **Windows** : `C:\\xampp\\htdocs\\banque\\`
* **Mac** : `/Applications/XAMPP/htdocs/banque/`

### 3\. Démarrer XAMPP

Ouvrir le panneau de contrôle XAMPP et démarrer :

* **Apache** (serveur web)
* **MySQL** (base de données)

### 4\. Créer la base de données

1. Ouvrir un navigateur et aller sur : `http://localhost/phpmyadmin`
2. Cliquer sur "Nouvelle base de données" → entrer `banque\_db` → cliquer "Créer"
3. Cliquer sur `banque\_db` dans le menu gauche
4. Cliquer sur l'onglet "Importer"
5. Cliquer "Choisir un fichier" → sélectionner `banque/sql/banque\_db.sql`
6. Cliquer "Importer" en bas de la page

### 5\. Accéder au projet

Ouvrir un navigateur et aller sur : `http://localhost/banque/`

\---

## Arborescence complète des fichiers

```
banque/
│
├── index.php               → Redirige vers login ou dashboard selon le rôle
├── login.php               → Page de connexion (formulaire)
├── logout.php              → Déconnexion (détruit la session)
│
├── config/
│   └── database.php        → Connexion PDO à MySQL
│
├── includes/
│   ├── session.php         → Fonctions de vérification de session/rôle
│   ├── header.php          → En-tête HTML (navbar, balises head)
│   ├── footer.php          → Pied de page HTML
│   └── menu.php            → Menu de navigation selon le rôle
│
├── admin/
│   ├── dashboard.php       → Statistiques globales de la banque
│   ├── utilisateurs.php    → Liste, création, suppression des utilisateurs
│   └── roles.php           → Modification d'un utilisateur et de son rôle
│
├── employe/
│   ├── dashboard.php       → Vue d'ensemble des clients de l'employé
│   ├── clients.php         → Gestion des clients (créer, supprimer)
│   └── comptes.php         → Consultation et transactions sur les comptes
│
├── client/
│   ├── dashboard.php       → Tableau de bord : comptes + dernières transactions
│   ├── compte.php          → Détails des comptes et IBAN
│   └── historique.php      → Historique complet des transactions
│
├── css/
│   └── style.css           → Toute la mise en forme (variables, composants)
│
└── sql/
    └── banque\_db.sql       → Script SQL complet (tables + données de test)
```

\---

## Explication de chaque table SQL

### `utilisateurs`

Contient tous les comptes de connexion (admin, employé, client).
Le champ `role` détermine les droits de l'utilisateur.

### `employes`

Profil complémentaire pour les employés. Lié à `utilisateurs` via `utilisateur\_id`.
Permet de distinguer l'identité bancaire (employé) du compte de connexion.

### `clients`

Profil complémentaire pour les clients. Contient `employe\_id` pour savoir
quel employé gère ce client. Relation : un client → un employé.

### `comptes`

Compte bancaire d'un client avec son IBAN et son solde.
Un client peut avoir plusieurs comptes.

### `transactions`

Enregistre chaque opération (dépôt, retrait, virement) sur un compte.
Le solde du compte est mis à jour à chaque transaction.

\---

## Relations entre les tables

```
utilisateurs ──→ employes ──→ clients ──→ comptes ──→ transactions
     ↑                           ↑
     └────────────────────────────┘
     (un utilisateur peut être client OU employé)
```

\---

## Comment ajouter un nouveau rôle

1. Modifier la colonne `role` dans `utilisateurs` :

```sql
   ALTER TABLE utilisateurs MODIFY role ENUM('admin', 'employe', 'client', 'superviseur');
   ```

2. Créer un dossier `banque/superviseur/`
3. Créer les pages PHP (dashboard.php, etc.)
4. Dans `includes/session.php`, modifier `verifier\_role()` si nécessaire
5. Dans `includes/menu.php`, ajouter un bloc :

```php
   <?php elseif ($role === 'superviseur'): ?>
       <a href="/banque/superviseur/dashboard.php">Dashboard</a>
   ```

6. Dans `login.php`, ajouter la redirection :

```php
   elseif ($utilisateur\['role'] === 'superviseur') {
       header('Location: /banque/superviseur/dashboard.php');
   }
   ```

\---

## Comment ajouter une nouvelle page

Exemple : ajouter une page `employe/rapports.php`

1. Créer le fichier avec cette structure :

```php
   <?php
   require\_once '../includes/session.php';
   require\_once '../config/database.php';
   verifier\_role('employe');   // protection de la page
   
   $titre\_page = "Mes rapports";
   include '../includes/header.php';
   ?>
   
   <div class="page-titre">
       <h1>Mes rapports</h1>
   </div>
   
   <!-- Contenu ici -->
   
   <?php include '../includes/footer.php'; ?>
   ```

2. Ajouter le lien dans `includes/menu.php` (bloc `elseif employe`)

\---

## Comment ajouter une nouvelle table

Exemple : ajouter une table `documents`

1. Créer la table dans phpMyAdmin ou via SQL :

```sql
   CREATE TABLE documents (
       id          INT AUTO\_INCREMENT PRIMARY KEY,
       client\_id   INT NOT NULL,
       nom\_fichier VARCHAR(255) NOT NULL,
       date\_upload DATETIME DEFAULT CURRENT\_TIMESTAMP,
       FOREIGN KEY (client\_id) REFERENCES clients(id) ON DELETE CASCADE
   );
   ```

2. Dans votre page PHP, récupérer les données :

```php
   $req = $pdo->prepare("SELECT \* FROM documents WHERE client\_id = ?");
   $req->execute(\[$client\_id]);
   $documents = $req->fetchAll();
   ```

\---

## Comment modifier les permissions

Pour que l'employé puisse voir les statistiques globales (actuellement admin uniquement) :

Dans `employe/dashboard.php`, changer :

```php
verifier\_role('employe');
// vers
verifier\_connexion(); // accepte tous les rôles connectés
```

Pour créer une page accessible à admin ET employé :

```php
verifier\_connexion();
if ($\_SESSION\['role'] !== 'admin' \&\& $\_SESSION\['role'] !== 'employe') {
    header('Location: /banque/');
    exit();
}
```

\---

## Sécurité mise en place

* **Mots de passe** : hashés avec `password\_hash()` (bcrypt)
* **Requêtes SQL** : toutes préparées avec PDO (protection contre les injections SQL)
* **Sessions** : vérification du rôle sur chaque page protégée
* **Isolation des données** : un employé ne voit que ses clients, un client ne voit que ses propres comptes
* **XSS** : toutes les sorties HTML sont protégées avec `htmlspecialchars()`

