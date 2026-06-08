<?php
// ============================================================
// index.php
// Page d'accueil : redirige selon si l'utilisateur est connecté
// ============================================================

require_once 'includes/session.php';

// Si l'utilisateur est déjà connecté, on le redirige vers son tableau de bord
if (est_connecte()) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: /banque/admin/dashboard.php');
    } elseif ($_SESSION['role'] === 'employe') {
        header('Location: /banque/employe/dashboard.php');
    } else {
        header('Location: /banque/client/dashboard.php');
    }
    exit();
}

// Sinon, on redirige vers la page de connexion
header('Location: /banque/login.php');
exit();
?>
