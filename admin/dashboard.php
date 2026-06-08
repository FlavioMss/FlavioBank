<?php
// ============================================================
// admin/dashboard.php
// Tableau de bord de l'administrateur
// Affiche les statistiques globales de la banque
// ============================================================

require_once '../includes/session.php';
require_once '../config/database.php';

// Vérification : l'utilisateur doit être connecté ET être admin
verifier_role('admin');

// ============================================================
// Récupération des statistiques pour le tableau de bord
// ============================================================

// Nombre total d'utilisateurs
$req_utilisateurs = $pdo->query("SELECT COUNT(*) AS total FROM utilisateurs");
$total_utilisateurs = $req_utilisateurs->fetch()['total'];

// Nombre total de clients
$req_clients = $pdo->query("SELECT COUNT(*) AS total FROM clients");
$total_clients = $req_clients->fetch()['total'];

// Nombre total de comptes bancaires
$req_comptes = $pdo->query("SELECT COUNT(*) AS total FROM comptes");
$total_comptes = $req_comptes->fetch()['total'];

// Somme de tous les soldes (argent total géré par la banque)
$req_solde = $pdo->query("SELECT SUM(solde) AS total FROM comptes");
$total_solde = $req_solde->fetch()['total'] ?? 0;

// Nombre total de transactions
$req_transactions = $pdo->query("SELECT COUNT(*) AS total FROM transactions");
$total_transactions = $req_transactions->fetch()['total'];

// Nombre d'employés
$req_employes = $pdo->query("SELECT COUNT(*) AS total FROM employes");
$total_employes = $req_employes->fetch()['total'];

// Les 5 dernières transactions (pour afficher l'activité récente)
$req_recentes = $pdo->query("
    SELECT
        t.type_operation,
        t.montant,
        t.description,
        t.date_operation,
        c.iban,
        u.nom,
        u.prenom
    FROM transactions t
    JOIN comptes c ON t.compte_id = c.id
    JOIN clients cl ON c.client_id = cl.id
    JOIN utilisateurs u ON cl.utilisateur_id = u.id
    ORDER BY t.date_operation DESC
    LIMIT 5
");
$transactions_recentes = $req_recentes->fetchAll();

// Titre de la page (utilisé par header.php)
$titre_page = "Tableau de bord Admin";

include '../includes/header.php';
?>

<div class="page-titre">
    <h1>Tableau de bord</h1>
    <p>Vue d'ensemble de la Banque Nationale</p>
</div>

<!-- Cartes statistiques -->
<div class="stats-grille">

    <div class="stat-carte">
        <div class="stat-label">Utilisateurs</div>
        <div class="stat-valeur"><?php echo $total_utilisateurs; ?></div>
        <div class="stat-detail"><?php echo $total_employes; ?> employés</div>
    </div>

    <div class="stat-carte">
        <div class="stat-label">Clients</div>
        <div class="stat-valeur"><?php echo $total_clients; ?></div>
        <div class="stat-detail">clients enregistrés</div>
    </div>

    <div class="stat-carte">
        <div class="stat-label">Comptes</div>
        <div class="stat-valeur"><?php echo $total_comptes; ?></div>
        <div class="stat-detail">comptes bancaires</div>
    </div>

    <div class="stat-carte">
        <div class="stat-label">Actifs totaux</div>
        <div class="stat-valeur"><?php echo number_format($total_solde, 2, ',', ' '); ?> €</div>
        <div class="stat-detail">somme des soldes</div>
    </div>

    <div class="stat-carte">
        <div class="stat-label">Transactions</div>
        <div class="stat-valeur"><?php echo $total_transactions; ?></div>
        <div class="stat-detail">opérations enregistrées</div>
    </div>

</div>

<!-- Tableau des transactions récentes -->
<div class="tableau-conteneur">
    <div class="tableau-entete">
        <h2>Transactions récentes</h2>
    </div>

    <table>
        <thead>
            <tr>
                <th>Client</th>
                <th>IBAN</th>
                <th>Type</th>
                <th>Montant</th>
                <th>Description</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions_recentes as $transaction): ?>
            <tr>
                <td><?php echo htmlspecialchars($transaction['prenom'] . ' ' . $transaction['nom']); ?></td>
                <td style="font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($transaction['iban']); ?></td>
                <td>
                    <span class="badge badge-<?php echo $transaction['type_operation']; ?>">
                        <?php echo ucfirst($transaction['type_operation']); ?>
                    </span>
                </td>
                <td style="font-weight: bold;"><?php echo number_format($transaction['montant'], 2, ',', ' '); ?> €</td>
                <td><?php echo htmlspecialchars($transaction['description']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($transaction['date_operation'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include '../includes/footer.php'; ?>
