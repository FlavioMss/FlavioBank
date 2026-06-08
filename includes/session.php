<?php
// ============================================================
// includes/session.php
// Gestion des sessions et des vérifications de connexion
// Ce fichier est inclus dans toutes les pages protégées
// ============================================================

// Démarrer la session (obligatoire avant d'utiliser $_SESSION)
session_start();

// ============================================================
// FONCTION : verifier_connexion()
// Vérifie que l'utilisateur est bien connecté
// Si ce n'est pas le cas, redirige vers la page de login
// ============================================================
function verifier_connexion() {
    if (!isset($_SESSION['utilisateur_id'])) {
        // L'utilisateur n'est pas connecté, on le redirige
        header('Location: /banque/login.php');
        exit();
    }
}

// ============================================================
// FONCTION : verifier_role($role_requis)
// Vérifie que l'utilisateur a bien le rôle attendu
// $role_requis : 'admin', 'employe' ou 'client'
// ============================================================
function verifier_role($role_requis) {
    // D'abord on vérifie qu'il est connecté
    verifier_connexion();

    // Ensuite on vérifie son rôle
    if ($_SESSION['role'] !== $role_requis) {
        // Mauvais rôle : on l'envoie sur sa propre page d'accueil
        if ($_SESSION['role'] === 'admin') {
            header('Location: /banque/admin/dashboard.php');
        } elseif ($_SESSION['role'] === 'employe') {
            header('Location: /banque/employe/dashboard.php');
        } else {
            header('Location: /banque/client/dashboard.php');
        }
        exit();
    }
}

// ============================================================
// FONCTION : est_connecte()
// Retourne true si l'utilisateur est connecté, false sinon
// Utile pour afficher/masquer des éléments dans l'interface
// ============================================================
function est_connecte() {
    return isset($_SESSION['utilisateur_id']);
}
?>
