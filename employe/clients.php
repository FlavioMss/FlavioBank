<?php
// ============================================================
// employe/clients.php
// Gestion des clients de l'employé (créer, modifier, supprimer)
// Un employé ne voit QUE ses propres clients
// ============================================================

require_once '../includes/session.php';
require_once '../config/database.php';

verifier_role('employe');

// Récupérer le profil employé
$req_employe = $pdo->prepare("SELECT id FROM employes WHERE utilisateur_id = ?");
$req_employe->execute([$_SESSION['utilisateur_id']]);
$employe = $req_employe->fetch();
$employe_id = $employe['id'];

$message_succes = '';
$message_erreur = '';

// ============================================================
// ACTION : Suppression d'un client
// ============================================================
if (isset($_GET['action']) && $_GET['action'] === 'supprimer' && isset($_GET['id'])) {

    $id_client = (int) $_GET['id'];

    // Vérification de sécurité : ce client appartient bien à cet employé ?
    $req_check = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND employe_id = ?");
    $req_check->execute([$id_client, $employe_id]);

    if ($req_check->fetch()) {
        // On récupère l'utilisateur_id pour supprimer aussi l'utilisateur
        $req_util_id = $pdo->prepare("SELECT utilisateur_id FROM clients WHERE id = ?");
        $req_util_id->execute([$id_client]);
        $util_id = $req_util_id->fetch()['utilisateur_id'];

        // Supprimer l'utilisateur (le client et ses comptes seront supprimés en CASCADE)
        $req_suppr = $pdo->prepare("DELETE FROM utilisateurs WHERE id = ?");
        $req_suppr->execute([$util_id]);

        $message_succes = "Client supprimé avec succès.";
    } else {
        $message_erreur = "Action non autorisée.";
    }
}

// ============================================================
// ACTION : Création d'un nouveau client
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'creer') {

    $nom        = trim($_POST['nom'] ?? '');
    $prenom     = trim($_POST['prenom'] ?? '');
    $email      = trim($_POST['email'] ?? '');
    $password   = $_POST['password'] ?? '';
    $iban       = trim($_POST['iban'] ?? '');
    $solde      = floatval($_POST['solde'] ?? 0);

    if (empty($nom) || empty($prenom) || empty($email) || empty($password) || empty($iban)) {
        $message_erreur = "Tous les champs sont obligatoires.";
    } else {
        // Vérifier email unique
        $req_check_email = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $req_check_email->execute([$email]);

        if ($req_check_email->fetch()) {
            $message_erreur = "Cet email est déjà utilisé.";
        } else {
            // 1. Créer l'utilisateur
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $req_util = $pdo->prepare("
                INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role)
                VALUES (?, ?, ?, ?, 'client')
            ");
            $req_util->execute([$nom, $prenom, $email, $hash]);
            $util_id = $pdo->lastInsertId();

            // 2. Créer le profil client (lié à cet employé)
            $req_client = $pdo->prepare("
                INSERT INTO clients (utilisateur_id, employe_id) VALUES (?, ?)
            ");
            $req_client->execute([$util_id, $employe_id]);
            $client_id = $pdo->lastInsertId();

            // 3. Créer le compte bancaire
            $req_compte = $pdo->prepare("
                INSERT INTO comptes (client_id, iban, solde) VALUES (?, ?, ?)
            ");
            $req_compte->execute([$client_id, $iban, $solde]);

            $message_succes = "Client créé avec succès !";
        }
    }
}

// ============================================================
// Récupérer la liste des clients de cet employé
// ============================================================
$req_clients = $pdo->prepare("
    SELECT
        cl.id AS client_id,
        u.nom,
        u.prenom,
        u.email,
        COUNT(co.id) AS nb_comptes,
        COALESCE(SUM(co.solde), 0) AS solde_total
    FROM clients cl
    JOIN utilisateurs u ON cl.utilisateur_id = u.id
    LEFT JOIN comptes co ON co.client_id = cl.id
    WHERE cl.employe_id = ?
    GROUP BY cl.id, u.nom, u.prenom, u.email
    ORDER BY u.nom
");
$req_clients->execute([$employe_id]);
$liste_clients = $req_clients->fetchAll();

$titre_page = "Mes clients";
include '../includes/header.php';
?>

<div class="page-titre">
    <h1>Gestion des clients</h1>
    <p>Créer et gérer vos clients</p>
</div>

<?php if (!empty($message_succes)): ?>
    <div class="alerte alerte-succes"><?php echo htmlspecialchars($message_succes); ?></div>
<?php endif; ?>
<?php if (!empty($message_erreur)): ?>
    <div class="alerte alerte-erreur"><?php echo htmlspecialchars($message_erreur); ?></div>
<?php endif; ?>

<!-- Formulaire création client -->
<div class="carte">
    <h2>Créer un nouveau client</h2>

    <form method="POST" action="/banque/employe/clients.php">
        <input type="hidden" name="action" value="creer">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label>Nom</label>
                <input type="text" name="nom" placeholder="Dupont" required>
            </div>
            <div class="form-group">
                <label>Prénom</label>
                <input type="text" name="prenom" placeholder="Jean" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" placeholder="jean.dupont@email.fr" required>
            </div>
            <div class="form-group">
                <label>Mot de passe initial</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px;">
            <div class="form-group">
                <label>IBAN (fictif)</label>
                <input type="text" name="iban" placeholder="FR76 3000 6000 0100 0000 0000 000" required>
            </div>
            <div class="form-group">
                <label>Solde initial (€)</label>
                <input type="number" name="solde" value="0" min="0" step="0.01">
            </div>
        </div>

        <div class="flex-droite">
            <button type="submit" class="btn btn-principal">Créer le client</button>
        </div>
    </form>
</div>

<!-- Tableau des clients -->
<div class="tableau-conteneur">
    <div class="tableau-entete">
        <h2>Mes clients (<?php echo count($liste_clients); ?>)</h2>
    </div>

    <?php if (count($liste_clients) === 0): ?>
        <div style="padding: 32px; text-align: center; color: #718096;">
            Vous n'avez pas encore de clients.
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Comptes</th>
                <th>Solde total</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($liste_clients as $client): ?>
            <tr>
                <td><?php echo htmlspecialchars($client['nom']); ?></td>
                <td><?php echo htmlspecialchars($client['prenom']); ?></td>
                <td><?php echo htmlspecialchars($client['email']); ?></td>
                <td><?php echo $client['nb_comptes']; ?> compte(s)</td>
                <td><strong><?php echo number_format($client['solde_total'], 2, ',', ' '); ?> €</strong></td>
                <td>
                    <div class="actions-groupe">
                        <a href="/banque/employe/comptes.php?client_id=<?php echo $client['client_id']; ?>"
                           class="btn btn-neutre" style="font-size: 12px; padding: 5px 12px;">
                            Comptes
                        </a>
                        <a href="/banque/employe/clients.php?action=supprimer&id=<?php echo $client['client_id']; ?>"
                           class="btn btn-danger" style="font-size: 12px; padding: 5px 12px;"
                           onclick="return confirm('Supprimer ce client et tous ses comptes ?')">
                            Supprimer
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
