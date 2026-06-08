<?php
// ============================================================
// employe/comptes.php
// L'employé consulte les comptes d'un de ses clients
// ?client_id=X dans l'URL pour cibler un client précis
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

// Récupérer le client_id passé dans l'URL (optionnel)
$client_id_filtre = isset($_GET['client_id']) ? (int) $_GET['client_id'] : null;

// ============================================================
// ACTION : Ajouter une transaction manuelle
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'transaction') {

    $compte_id      = (int) ($_POST['compte_id'] ?? 0);
    $type_operation = $_POST['type_operation'] ?? '';
    $montant        = floatval($_POST['montant'] ?? 0);
    $description    = trim($_POST['description'] ?? '');

    // Vérifier que le compte appartient bien à un client de cet employé
    $req_check = $pdo->prepare("
        SELECT co.id, co.solde
        FROM comptes co
        JOIN clients cl ON co.client_id = cl.id
        WHERE co.id = ? AND cl.employe_id = ?
    ");
    $req_check->execute([$compte_id, $employe_id]);
    $compte = $req_check->fetch();

    if (!$compte) {
        $message_erreur = "Compte non autorisé.";
    } elseif ($montant <= 0) {
        $message_erreur = "Le montant doit être supérieur à 0.";
    } else {
        // Calculer le nouveau solde selon le type d'opération
        if ($type_operation === 'depot') {
            $nouveau_solde = $compte['solde'] + $montant;
        } elseif ($type_operation === 'retrait' || $type_operation === 'virement') {
            if ($montant > $compte['solde']) {
                $message_erreur = "Solde insuffisant.";
            } else {
                $nouveau_solde = $compte['solde'] - $montant;
            }
        }

        if (empty($message_erreur)) {
            // Mettre à jour le solde du compte
            $req_update = $pdo->prepare("UPDATE comptes SET solde = ? WHERE id = ?");
            $req_update->execute([$nouveau_solde, $compte_id]);

            // Enregistrer la transaction
            $req_trans = $pdo->prepare("
                INSERT INTO transactions (compte_id, type_operation, montant, description)
                VALUES (?, ?, ?, ?)
            ");
            $req_trans->execute([$compte_id, $type_operation, $montant, $description]);

            $message_succes = "Transaction enregistrée avec succès.";
        }
    }
}

// ============================================================
// Récupérer les comptes (filtrés par client si précisé)
// ============================================================
if ($client_id_filtre) {
    // Vérifier que ce client appartient bien à cet employé
    $req_check_client = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND employe_id = ?");
    $req_check_client->execute([$client_id_filtre, $employe_id]);
    if (!$req_check_client->fetch()) {
        header('Location: /banque/employe/clients.php');
        exit();
    }

    $req_comptes = $pdo->prepare("
        SELECT
            co.id AS compte_id,
            co.iban,
            co.solde,
            co.created_at,
            u.nom,
            u.prenom,
            cl.id AS client_id
        FROM comptes co
        JOIN clients cl ON co.client_id = cl.id
        JOIN utilisateurs u ON cl.utilisateur_id = u.id
        WHERE cl.employe_id = ? AND cl.id = ?
        ORDER BY u.nom
    ");
    $req_comptes->execute([$employe_id, $client_id_filtre]);
} else {
    // Tous les comptes de tous les clients de l'employé
    $req_comptes = $pdo->prepare("
        SELECT
            co.id AS compte_id,
            co.iban,
            co.solde,
            co.created_at,
            u.nom,
            u.prenom,
            cl.id AS client_id
        FROM comptes co
        JOIN clients cl ON co.client_id = cl.id
        JOIN utilisateurs u ON cl.utilisateur_id = u.id
        WHERE cl.employe_id = ?
        ORDER BY u.nom
    ");
    $req_comptes->execute([$employe_id]);
}
$liste_comptes = $req_comptes->fetchAll();

$titre_page = "Comptes clients";
include '../includes/header.php';
?>

<div class="page-titre">
    <h1>Comptes bancaires</h1>
    <p>Consulter et gérer les comptes de vos clients</p>
</div>

<a href="/banque/employe/clients.php" class="btn btn-neutre marge-bas" style="display: inline-block;">
    ← Retour aux clients
</a>

<?php if (!empty($message_succes)): ?>
    <div class="alerte alerte-succes"><?php echo htmlspecialchars($message_succes); ?></div>
<?php endif; ?>
<?php if (!empty($message_erreur)): ?>
    <div class="alerte alerte-erreur"><?php echo htmlspecialchars($message_erreur); ?></div>
<?php endif; ?>

<!-- Formulaire d'ajout de transaction -->
<div class="carte">
    <h2>Enregistrer une opération</h2>

    <form method="POST" action="/banque/employe/comptes.php<?php echo $client_id_filtre ? '?client_id='.$client_id_filtre : ''; ?>">
        <input type="hidden" name="action" value="transaction">

        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px;">
            <div class="form-group">
                <label>Compte</label>
                <select name="compte_id" required>
                    <option value="">-- Sélectionner un compte --</option>
                    <?php foreach ($liste_comptes as $c): ?>
                    <option value="<?php echo $c['compte_id']; ?>">
                        <?php echo htmlspecialchars($c['prenom'] . ' ' . $c['nom']); ?>
                        — <?php echo htmlspecialchars(substr($c['iban'], -8)); ?>
                        (<?php echo number_format($c['solde'], 2, ',', ' '); ?> €)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Type</label>
                <select name="type_operation" required>
                    <option value="depot">Dépôt</option>
                    <option value="retrait">Retrait</option>
                    <option value="virement">Virement</option>
                </select>
            </div>

            <div class="form-group">
                <label>Montant (€)</label>
                <input type="number" name="montant" min="0.01" step="0.01" placeholder="0.00" required>
            </div>
        </div>

        <div class="form-group">
            <label>Description (optionnel)</label>
            <input type="text" name="description" placeholder="Ex: Virement salaire, Retrait DAB...">
        </div>

        <div class="flex-droite">
            <button type="submit" class="btn btn-principal">Enregistrer l'opération</button>
        </div>
    </form>
</div>

<!-- Tableau des comptes -->
<div class="tableau-conteneur">
    <div class="tableau-entete">
        <h2>
            <?php if ($client_id_filtre): ?>
                Comptes du client sélectionné
            <?php else: ?>
                Tous mes comptes clients (<?php echo count($liste_comptes); ?>)
            <?php endif; ?>
        </h2>
    </div>

    <?php if (count($liste_comptes) === 0): ?>
        <div style="padding: 32px; text-align: center; color: #718096;">
            Aucun compte trouvé.
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>IBAN</th>
                <th>Solde</th>
                <th>Créé le</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($liste_comptes as $compte): ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($compte['prenom'] . ' ' . $compte['nom']); ?></strong></td>
                <td style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($compte['iban']); ?></td>
                <td>
                    <span class="<?php echo $compte['solde'] >= 0 ? 'solde-positif' : 'solde-negatif'; ?>" style="font-weight: bold; font-size: 16px;">
                        <?php echo number_format($compte['solde'], 2, ',', ' '); ?> €
                    </span>
                </td>
                <td><?php echo date('d/m/Y', strtotime($compte['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
