<?php
// ============================================================
// logout.php
// Déconnexion de l'utilisateur
// ============================================================

require_once 'includes/session.php';

// Détruire toutes les données de session
session_destroy();

// Rediriger vers la page de connexion
header('Location: /banque/login.php');
exit();
?>
