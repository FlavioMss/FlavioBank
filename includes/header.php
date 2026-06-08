<?php
// ============================================================
// includes/header.php
// En-tête HTML commun à toutes les pages
// Inclure ce fichier en haut de chaque page avec :
// include '../includes/header.php';  (depuis admin/, employe/, client/)
// include 'includes/header.php';    (depuis la racine)
// ============================================================

// $titre_page doit être défini avant d'inclure ce fichier
// Exemple : $titre_page = "Tableau de bord Admin";
if (!isset($titre_page)) {
    $titre_page = "Banque Nationale";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($titre_page); ?> — Banque Nationale</title>

    <!-- Notre feuille de style principale -->
    <link rel="stylesheet" href="/banque/css/style.css">
</head>
<body>

<!-- Barre de navigation principale -->
<header class="navbar">
    <div class="navbar-inner">
        <!-- Logo et nom de la banque -->
        <a href="/banque/" class="navbar-logo">
            <span class="logo-icon">⬡</span>
            <span class="logo-text">Banque Nationale</span>
        </a>

        <!-- Menu de navigation selon le rôle -->
        <?php if (est_connecte()): ?>
        <nav class="navbar-menu">
            <?php include __DIR__ . '/menu.php'; ?>
        </nav>

        <!-- Infos utilisateur connecté + bouton déconnexion -->
        <div class="navbar-user">
            <span class="user-name">
                <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
            </span>
            <span class="user-role role-<?php echo $_SESSION['role']; ?>">
                <?php echo ucfirst($_SESSION['role']); ?>
            </span>
            <a href="/banque/logout.php" class="btn-logout">Déconnexion</a>
        </div>
        <?php endif; ?>
    </div>
</header>

<!-- Contenu principal de la page -->
<main class="main-content">
