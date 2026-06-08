<?php
// ============================================================
// admin/utilisateurs.php
// Gestion complète des utilisateurs (liste, création, suppression)
// ============================================================

require_once '../includes/session.php';
require_once '../config/database.php';

// Seul l'admin peut accéder à cette page
verifier_role('admin');

$message_succes = '';
$message_erreur = '';

// ============================================================
// ACTION : Suppression d'un utilisateur
// L'URL contient ?action=supprimer&id=X
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {

    $id_a_supprimer = (int) $_GET['id'];

    // Sécurité : on ne peut pas supprimer son propre compte
    if ($id_a_supprimer === (int) $_SESSION['utilisateur_id']) {
        $message_erreur = "Vous ne pouvez pas supprimer votre propre compte.";
    } else {
        // Supprimer l'utilisateur (le CASCADE supprimera aussi employe/client lié)
        $req_suppr = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
        $req_suppr->execute([$id_a_supprimer]);
        $message_succes = "Utilisateur supprimé avec succès.";
    }
}

// ============================================================
// ACTION : Création d'un nouvel utilisateur (formulaire POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'creer') {

    $nom        = trim($_POST['nom'] ?? '');
    $prenom     = trim($_POST['prenom'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $role       = $_POST['role'] ?? 'client';

    // Vérification des champs obligatoires
    if (empty($nom) || empty($prenom) || empty($email) || empty($password)) {
        $message_erreur = "Tous les champs sont obligatoires.";
    } else {
        // Vérifier que l'email n'existe pas déjà
        $req_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $req_check->execute([$email]);

        if ($req_check->fetch()) {
            $message_erreur = "Cet email est déjà utilisé.";
        } else {
            // Hasher le mot de passe avant de le stocker
            $mot_de_passe_hashe = password_hash($password, PASSWORD_DEFAULT);

            // Insérer le nouvel utilisateur
            $req_insert = $pdo->prepare("
                INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role)
                VALUES (?, ?, ?, ?, ?)
            ");
            $req_insert->execute([$nom, $prenom, $email, $mot_de_passe_hashe, $role]);

            $nouvel_id = $pdo->lastInsertId();

            // Si le rôle est "employe", créer aussi le profil dans la table employes
            if ($role === 'employe') {
                $req_emp = $pdo->prepare("INSERT INTO employes (utilisateur_id) VALUES (?)");
                $req_emp->execute([$nouvel_id]);
            }

            // Si le rôle est "client", on devra aussi créer le profil client
            // (mais on laisse ça à l'employé via sa propre interface)

            $message_succes = "Utilisateur créé avec succès.";
        }
    }
}

// ============================================================
// Récupération de la liste de tous les utilisateurs
// ============================================================
$req_liste = $pdo->query("
    SELECT id, nom, prenom, email, role, created_at
    FROM utilisateurs
    ORDER BY created_at DESC
");
$liste_utilisateurs = $req_liste->fetchAll();

$titre_page = "Gestion des utilisateurs";
include '../includes/header.php';
?>

<div class="page-titre">
    <h1>Gestion des utilisateurs</h1>
    <p>Créer, modifier et supprimer les comptes utilisateurs</p>
</div>

<!-- Messages de confirmation / erreur -->
<?php if (!empty($message_succes)): ?>
    <div class="alerte alerte-succes"><?php echo htmlspecialchars($message_succes); ?></div>
<?php endif; ?>
<?php if (!empty($message_erreur)): ?>
    <div class="alerte alerte-erreur"><?php echo htmlspecialchars($message_erreur); ?></div>
<?php endif; ?>

<!-- Formulaire de création d'un utilisateur -->
<div class="carte">
    <h2>Créer un nouvel utilisateur</h2>

    <form method="POST" action="/banque/admin/utilisateurs.php">
        <!-- Champ caché pour indiquer l'action -->
        <input type="hidden" name="action" value="creer">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" placeholder="Dupont" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" placeholder="Marie" required>
            </div>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="utilisateur@banque.fr" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <div class="form-group">
                <label>Rôle</label>
                <select name="role">
                    <option value="client">Client</option>
                    <option value="employe">Employé</option>
                    <option value="admin">Administrateur</option>
                </select>
            </div>
        </div>

        <div class="flex-droite">
            <button type="submit" class="btn btn-principal">Créer l'utilisateur</button>
        </div>
    </form>
</div>

<!-- Tableau de la liste des utilisateurs -->
<div class="tableau-conteneur">
    <div class="tableau-entete">
        <h2>Liste des utilisateurs (<?php echo count($liste_utilisateurs); ?>)</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Créé le</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($liste_utilisateurs as $util): ?>
            <tr>
                <td><?php echo $util['id']; ?></td>
                <td><?php echo htmlspecialchars($util['nom']); ?></td>
                <td><?php echo htmlspecialchars($util['prenom']); ?></td>
                <td><?php echo htmlspecialchars($util['email']); ?></td>
                <td>
                    <span class="badge badge-<?php echo $util['role']; ?>">
                        <?php echo ucfirst($util['role']); ?>
                    </span>
                </td>
                <td><?php echo date('d/m/Y', strtotime($util['created_at'])); ?></td>
                <td>
                    <div class="actions-groupe">
                        <!-- Lien vers la page de modification -->
                        <a href="/banque/admin/roles.php?id=<?php echo $util['id']; ?>"
                           class="btn btn-neutre" style="padding: 5px 12px; font-size: 12px;">
                            Modifier rôle
                        </a>
                        <!-- Bouton de suppression (avec confirmation) -->
                        <?php if ($util['id'] != $_SESSION['utilisateur_id']): ?>
                        <a href="/banque/admin/utilisateurs.php?action=supprimer&id=<?php echo $util['id']; ?>"
                           class="btn btn-danger" style="padding: 5px 12px; font-size: 12px;"
                           onclick="return confirm('Supprimer cet utilisateur ?')">
                            Supprimer
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
