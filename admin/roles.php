<?php
// ============================================================
// admin/roles.php
// Modification du rôle d'un utilisateur
// ============================================================

require_once '../includes/session.php';
require_once '../config/database.php';

verifier_role('admin');

$message_succes = '';
$message_erreur = '';

// Récupérer l'ID de l'utilisateur à modifier (passé dans l'URL)
$id_utilisateur = (int) ($_GET['id'] ?? 0);

// ============================================================
// Vérifier que l'utilisateur existe
// ============================================================
$req_util = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$req_util->execute([$id_utilisateur]);
$utilisateur = $req_util->fetch();

if (!$utilisateur) {
    // L'utilisateur n'existe pas, on retourne à la liste
    header('Location: /banque/admin/utilisateurs.php');
    exit();
}

// ============================================================
// ACTION : Modifier le rôle (formulaire POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nouveau_role = $_POST['role'] ?? '';
    $nouveau_nom  = trim($_POST['nom'] ?? '');
    $nouveau_prenom = trim($_POST['prenom'] ?? '');
    $nouvel_email = trim($_POST['email'] ?? '');

    // Validation simple
    if (empty($nouveau_nom) || empty($nouveau_prenom) || empty($nouvel_email)) {
        $message_erreur = "Tous les champs sont obligatoires.";
    } elseif (!in_array($nouveau_role, ['admin', 'employe', 'client'])) {
        $message_erreur = "Rôle invalide.";
    } else {
        $ancien_role = $utilisateur['role'];

        // Mettre à jour les infos de l'utilisateur
        $req_update = $pdo->prepare("
            UPDATE utilisateurs
            SET nom = ?, prenom = ?, email = ?, role = ?
            WHERE id = ?
        ");
        $req_update->execute([$nouveau_nom, $nouveau_prenom, $nouvel_email, $nouveau_role, $id_utilisateur]);

        // Gérer les changements de rôle :
        // Si on passe à "employe" et qu'il n'avait pas de profil employé → créer
        if ($nouveau_role === 'employe' && $ancien_role !== 'employe') {
            $req_check_emp = $pdo->prepare("SELECT id FROM employes WHERE utilisateur_id = ?");
            $req_check_emp->execute([$id_utilisateur]);
            if (!$req_check_emp->fetch()) {
                $req_emp = $pdo->prepare("INSERT INTO employes (utilisateur_id) VALUES (?)");
                $req_emp->execute([$id_utilisateur]);
            }
        }

        $message_succes = "Utilisateur mis à jour avec succès.";

        // Recharger les données de l'utilisateur
        $req_util->execute([$id_utilisateur]);
        $utilisateur = $req_util->fetch();
    }
}

$titre_page = "Modifier l'utilisateur";
include '../includes/header.php';
?>

<div class="page-titre">
    <h1>Modifier un utilisateur</h1>
    <p>Modifier les informations et le rôle de l'utilisateur</p>
</div>

<a href="/banque/admin/utilisateurs.php" class="btn btn-neutre marge-bas" style="display: inline-block;">
    ← Retour à la liste
</a>

<?php if (!empty($message_succes)): ?>
    <div class="alerte alerte-succes"><?php echo htmlspecialchars($message_succes); ?></div>
<?php endif; ?>
<?php if (!empty($message_erreur)): ?>
    <div class="alerte alerte-erreur"><?php echo htmlspecialchars($message_erreur); ?></div>
<?php endif; ?>

<div class="carte">
    <h2>Modifier : <?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></h2>

    <form method="POST" action="/banque/admin/roles.php?id=<?php echo $id_utilisateur; ?>">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom"
                    value="<?php echo htmlspecialchars($utilisateur['nom']); ?>" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom"
                    value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email"
                value="<?php echo htmlspecialchars($utilisateur['email']); ?>" required>
        </div>

        <div class="form-group">
            <label>Rôle</label>
            <select name="role">
                <option value="client"  <?php echo $utilisateur['role'] === 'client'  ? 'selected' : ''; ?>>Client</option>
                <option value="employe" <?php echo $utilisateur['role'] === 'employe' ? 'selected' : ''; ?>>Employé</option>
                <option value="admin"   <?php echo $utilisateur['role'] === 'admin'   ? 'selected' : ''; ?>>Administrateur</option>
            </select>
        </div>

        <div class="alerte alerte-info">
            <strong>Note :</strong> Si vous passez un utilisateur au rôle "Employé", un profil employé sera automatiquement créé.
        </div>

        <div class="flex-droite" style="margin-top: 16px;">
            <a href="/banque/admin/utilisateurs.php" class="btn btn-neutre">Annuler</a>
            <button type="submit" class="btn btn-principal">Enregistrer les modifications</button>
        </div>

    </form>
</div>

<?php include '../includes/footer.php'; ?>
