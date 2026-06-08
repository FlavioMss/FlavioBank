<?php
// ============================================================
// login.php
// Page de connexion pour tous les utilisateurs
// ============================================================

require_once 'includes/session.php';
require_once 'config/database.php';

// Si l'utilisateur est déjà connecté, on le redirige
if (est_connecte()) {
    header('Location: /banque/index.php');
    exit();
}

// Message d'erreur à afficher (s'il y en a un)
$message_erreur = '';

// ============================================================
// Traitement du formulaire de connexion (quand l'utilisateur clique "Se connecter")
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Récupérer les valeurs saisies dans le formulaire
    $email_saisi    = trim($_POST['email'] ?? '');
    $password_saisi = $_POST['password'] ?? '';

    // Vérifier que les champs ne sont pas vides
    if (empty($email_saisi) || empty($password_saisi)) {
        $message_erreur = "Veuillez remplir tous les champs.";
    } else {
        // Chercher l'utilisateur dans la base de données par son email
        // On utilise une requête préparée pour éviter les injections SQL
        $requete = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $requete->execute([$email_saisi]);
        $utilisateur = $requete->fetch();

        // Vérifier si l'utilisateur existe et si le mot de passe est correct
        if ($utilisateur && password_verify($password_saisi, $utilisateur['mot_de_passe'])) {
            // Connexion réussie ! On stocke les infos en session
            $_SESSION['utilisateur_id'] = $utilisateur['id'];
            $_SESSION['nom']            = $utilisateur['nom'];
            $_SESSION['prenom']         = $utilisateur['prenom'];
            $_SESSION['email']          = $utilisateur['email'];
            $_SESSION['role']           = $utilisateur['role'];

            // Redirection selon le rôle
            if ($utilisateur['role'] === 'admin') {
                header('Location: /banque/admin/dashboard.php');
            } elseif ($utilisateur['role'] === 'employe') {
                header('Location: /banque/employe/dashboard.php');
            } else {
                header('Location: /banque/client/dashboard.php');
            }
            exit();

        } else {
            // Email ou mot de passe incorrect
            $message_erreur = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Banque Nationale</title>
    <link rel="stylesheet" href="/banque/css/style.css">
</head>
<body>

<div class="login-page">
    <div class="login-box">

        <!-- Logo -->
        <div class="login-logo">
            <span class="logo-icon">⬡</span>
            <h1>Banque Nationale</h1>
            <p>Accès sécurisé à votre espace client</p>
        </div>

        <!-- Afficher le message d'erreur si besoin -->
        <?php if (!empty($message_erreur)): ?>
            <div class="alerte alerte-erreur">
                <?php echo htmlspecialchars($message_erreur); ?>
            </div>
        <?php endif; ?>

        <!-- Formulaire de connexion -->
        <form method="POST" action="/banque/login.php">

            <div class="form-group">
                <label for="email">Adresse email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="votre@email.fr"
                    value="<?php echo htmlspecialchars($email_saisi ?? ''); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="••••••••"
                    required
                >
            </div>

            <button type="submit" class="btn btn-principal btn-bloc">
                Se connecter
            </button>

        </form>

        <!-- Comptes de démonstration -->
        <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 12px; color: #718096; text-align: center; margin-bottom: 12px; text-transform: uppercase; letter-spacing: 0.5px;">Comptes de démonstration</p>
            <table style="width: 100%; font-size: 12px; color: #4a5568;">
                <tr>
                    <td style="padding: 4px 0;"><strong>Admin</strong></td>
                    <td>admin@banque.fr</td>
                    <td>password123</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;"><strong>Employé</strong></td>
                    <td>employe1@banque.fr</td>
                    <td>password123</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0;"><strong>Client</strong></td>
                    <td>client1@banque.fr</td>
                    <td>password123</td>
                </tr>
            </table>
        </div>

    </div>
</div>

</body>
</html>
