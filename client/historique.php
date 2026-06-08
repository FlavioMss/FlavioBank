<?php
// ============================================================
// client/historique.php
// Historique complet des transactions du client
// Peut être filtré par compte avec ?compte_id=X
// ============================================================

require_once '../includes/session.php';
require_once '../config/database.php';

verifier_role('client');

// Récupérer le profil client
$req_client = $pdo->prepare("SELECT id FROM clients WHERE utilisateur_id = ?");
$req_client->execute([$_SESSION['utilisateur_id']]);
$client = $req_client->fetch();
$client_id = $client['id'];

// Filtre optionnel par compte
$compte_id_filtre = isset($_GET['compte_id']) ? (int) $_GET['compte_id'] : null;

// ============================================================
// Récupérer les comptes du client (pour le filtre)
// ============================================================
$req_comptes = $pdo->prepare("SELECT id, iban FROM comptes WHERE client_id = ?");
$req_comptes->execute([$client_id]);
$mes_comptes = $req_comptes->fetchAll();

// ============================================================
// Récupérer les transactions (toutes ou filtrées par compte)
// ============================================================
if ($compte_id_filtre) {
    // Vérifier que ce compte appartient au client (sécurité !)
    $req_check = $pdo->prepare("SELECT id FROM comptes WHERE id = ? AND client_id = ?");
    $req_check->execute([$compte_id_filtre, $client_id]);
    if (!$req_check->fetch()) {
        header('Location: /banque/client/historique.php');
        exit();
    }

    $req_trans = $pdo->prepare("
        SELECT t.*, c.iban
        FROM transactions t
        JOIN comptes c ON t.compte_id = c.id
        WHERE c.client_id = ? AND t.compte_id = ?
        ORDER BY t.date_operation DESC
    ");
    $req_trans->execute([$client_id, $compte_id_filtre]);
} else {
    // Toutes les transactions de tous les comptes
    $req_trans = $pdo->prepare("
        SELECT t.*, c.iban
        FROM transactions t
        JOIN comptes c ON t.compte_id = c.id
        WHERE c.client_id = ?
        ORDER BY t.date_operation DESC
    ");
    $req_trans->execute([$client_id]);
}
$toutes_transactions = $req_trans->fetchAll();

// Calcul des totaux (pour les statistiques en haut)
$total_depots    = 0;
$total_retraits  = 0;
$total_virements = 0;

foreach ($toutes_transactions as $trans) {
    if ($trans['type_operation'] === 'depot') {
        $total_depots += $trans['montant'];
    } elseif ($trans['type_operation'] === 'retrait') {
        $total_retraits += $trans['montant'];
    } elseif ($trans['type_operation'] === 'virement') {
        $total_virements += $trans['montant'];
    }
}

$titre_page = "Historique des opérations";
include '../includes/header.php';
?>

<div class="page-titre">
    <h1>Historique des opérations</h1>
    <p>Toutes vos transactions bancaires</p>
</div>

<!-- Statistiques rapides -->
<div class="stats-grille">
    <div class="stat-carte">
        <div class="stat-label">Total dépôts</div>
        <div class="stat-valeur" style="color: var(--couleur-succes);">
            +<?php echo number_format($total_depots, 2, ',', ' '); ?> €
        </div>
    </div>
    <div class="stat-carte">
        <div class="stat-label">Total retraits</div>
        <div class="stat-valeur" style="color: var(--couleur-danger);">
            -<?php echo number_format($total_retraits, 2, ',', ' '); ?> €
        </div>
    </div>
    <div class="stat-carte">
        <div class="stat-label">Total virements</div>
        <div class="stat-valeur" style="color: var(--couleur-info);">
            <?php echo number_format($total_virements, 2, ',', ' '); ?> €
        </div>
    </div>
    <div class="stat-carte">
        <div class="stat-label">Nombre d'opérations</div>
        <div class="stat-valeur"><?php echo count($toutes_transactions); ?></div>
    </div>
</div>

<!-- Filtre par compte -->
<div class="carte">
    <h2>Filtrer par compte</h2>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="/banque/client/historique.php"
           class="btn <?php echo !$compte_id_filtre ? 'btn-principal' : 'btn-neutre'; ?>">
            Tous les comptes
        </a>
        <?php foreach ($mes_comptes as $compte): ?>
        <a href="/banque/client/historique.php?compte_id=<?php echo $compte['id']; ?>"
           class="btn <?php echo $compte_id_filtre == $compte['id'] ? 'btn-principal' : 'btn-neutre'; ?>"
           style="font-family: monospace; font-size: 12px;">
            ...<?php echo substr($compte['iban'], -10); ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- Tableau des transactions -->
<div class="tableau-conteneur">
    <div class="tableau-entete">
        <h2>
            <?php echo count($toutes_transactions); ?> opération(s)
            <?php echo $compte_id_filtre ? '(compte filtré)' : '(tous comptes)'; ?>
        </h2>
    </div>

    <?php if (count($toutes_transactions) === 0): ?>
        <div style="padding: 32px; text-align: center; color: #718096;">
            Aucune transaction trouvée.
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Compte</th>
                <th>Type</th>
                <th>Description</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($toutes_transactions as $trans): ?>
            <tr>
                <td><?php echo date('d/m/Y H:i', strtotime($trans['date_operation'])); ?></td>
                <td style="font-family: monospace; font-size: 11px; color: #718096;">
                    ...<?php echo substr($trans['iban'], -10); ?>
                </td>
                <td>
                    <span class="badge badge-<?php echo $trans['type_operation']; ?>">
                        <?php echo ucfirst($trans['type_operation']); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($trans['description'] ?: '—'); ?></td>
                <td style="font-weight: bold; text-align: right;">
                    <?php
                    $couleur = $trans['type_operation'] === 'depot' ? 'var(--couleur-succes)' : 'var(--couleur-danger)';
                    $signe   = $trans['type_operation'] === 'depot' ? '+' : '-';
                    ?>
                    <span style="color: <?php echo $couleur; ?>;">
                        <?php echo $signe . number_format($trans['montant'], 2, ',', ' '); ?> €
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
