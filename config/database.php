<?php
// ============================================================
// config/database.php
// Connexion à la base de données MySQL via PDO
// Modifier les infos ici si votre XAMPP est configuré différemment
// ============================================================

// Paramètres de connexion à la base de données
$db_host     = 'localhost';   // adresse du serveur MySQL (XAMPP = localhost)
$db_name     = 'banque_db';   // nom de la base de données
$db_user     = 'root';        // nom d'utilisateur MySQL (XAMPP = root par défaut)
$db_password = '';            // mot de passe MySQL (XAMPP = vide par défaut)

// Tentative de connexion avec PDO
try {
    // Création de la connexion PDO
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password
    );

    // On demande à PDO de lancer des exceptions en cas d'erreur SQL
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Les résultats seront retournés sous forme de tableaux associatifs
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $erreur) {
    // En cas d'échec : afficher le message d'erreur et arrêter le script
    die("Erreur de connexion à la base de données : " . $erreur->getMessage());
}
?>
