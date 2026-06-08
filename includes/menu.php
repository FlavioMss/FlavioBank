<?php
// ============================================================
// includes/menu.php
// Menu de navigation adapté au rôle de l'utilisateur connecté
// Ce fichier est inclus dans header.php
// ============================================================

// On vérifie que le rôle est défini en session
if (!isset($_SESSION['role'])) {
    return; // Pas de menu si pas connecté
}

$role = $_SESSION['role'];
?>

<?php if ($role === 'admin'): ?>
    <!-- Menu ADMIN -->
    <a href="/banque/admin/dashboard.php">Tableau de bord</a>
    <a href="/banque/admin/utilisateurs.php">Utilisateurs</a>
    <a href="/banque/admin/roles.php">Rôles</a>

<?php elseif ($role === 'employe'): ?>
    <!-- Menu EMPLOYÉ -->
    <a href="/banque/employe/dashboard.php">Tableau de bord</a>
    <a href="/banque/employe/clients.php">Mes clients</a>
    <a href="/banque/employe/comptes.php">Comptes</a>

<?php elseif ($role === 'client'): ?>
    <!-- Menu CLIENT -->
    <a href="/banque/client/dashboard.php">Mon compte</a>
    <a href="/banque/client/compte.php">Informations</a>
    <a href="/banque/client/historique.php">Historique</a>

<?php endif; ?>
