<?php
// ============================================================
// employe/dashboard.php
// Tableau de bord de l'employé
// Affiche ses statistiques : nombre de clients, soldes, etc.
// ============================================================

require_once '../includes/session.php';
require_once '../config/database.php';

// Vérification : doit être connecté ET avoir le rôle "employe"
verifier_role('employe');

// ============================================================
// Récupérer le profil employé de l'utilisateur connecté
// On a besoin de l'id dans la table "employes" (pas "utilisateurs")
// ============================================================
$req_employe = $pdo->prepare("SELECT id FROM employes WHERE utilisateur_id = ?");
$req_employe->execute([$_SESSION['utilisateur_id']]);
$employe = $req_employe->fetch();

// Si l'utilisateur est bien marqué "employe" mais n'a pas de profil employé
if (!$employe) {
    die("Erreur : profil employé introuvable. Contactez l'administrateur.");
}

$employe_id = $employe['id'];

// ============================================================
// Statistiques de l'employé : ses clients uniquement
// ============================================================

// Nombre de clients assignés à cet employé
$req_nb_clients = $pdo->prepare("SELECT COUNT(*) AS total FROM clients WHERE employe_id = ?");
$req_nb_clients->execute([$employe_id]);
$nb_clients = $req_nb_clients->fetch()['total'];

// Nombre de comptes de ses clients
$req_nb_comptes = $pdo->prepare("
    SELECT COUNT(co.id) AS total
    FROM comptes co
    JOIN clients cl ON co.client_id = cl.id
    WHERE cl.employe_id = ?
");
$req_nb_comptes->execute([$employe_id]);
$nb_comptes = $req_nb_comptes->fetch()['total'];

// Somme des soldes de ses clients
$req_solde = $pdo->prepare("
    SELECT SUM(co.solde) AS total
    FROM comptes co
    JOIN clients cl ON co.client_id = cl.id
    WHERE cl.employe_id = ?
");
$req_solde->execute([$employe_id]);
$total_solde = $req_solde->fetch()['total'] ?? 0;

// Liste des clients avec leur solde total
$req_clients = $pdo->prepare("
    SELECT
        u.nom,
        u.prenom,
        u.email,
        cl.id AS client_id,
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
$mes_clients = $req_clients->fetchAll();

$titre_page = "Tableau de bord Employé";
include '../includes/header.php';
?>

<div class="page-titre">
    <h1>Bonjour, <?php echo htmlspecialchars($_SESSION['prenom']); ?> !</h1>
    <p>Voici un résumé de votre portefeuille clients</p>
</div>

<!-- Statistiques -->
<div class="stats-grille">
    <div class="stat-carte">
        <div class="stat-label">Mes clients</div>
        <div class="stat-valeur"><?php echo $nb_clients; ?></div>
        <div class="stat-detail">clients assignés</div>
    </div>
    <div class="stat-carte">
        <div class="stat-label">Comptes gérés</div>
        <div class="stat-valeur"><?php echo $nb_comptes; ?></div>
        <div class="stat-detail">comptes bancaires</div>
    </div>
    <div class="stat-carte">
        <div class="stat-label">Actifs gérés</div>
        <div class="stat-valeur"><?php echo number_format($total_solde, 0, ',', ' '); ?> €</div>
        <div class="stat-detail">total des soldes</div>
    </div>
</div>

<!-- Liste de mes clients -->
<div class="tableau-conteneur">
    <div class="tableau-entete">
        <h2>Mes clients</h2>
        <a href="/banque/employe/clients.php" class="btn btn-principal" style="font-size: 13px; padding: 7px 16px;">
            Gérer les clients
        </a>
    </div>

    <?php if (count($mes_clients) === 0): ?>
        <div style="padding: 32px; text-align: center; color: #718096;">
            Vous n'avez pas encore de clients assignés.
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
            <?php foreach ($mes_clients as $client): ?>
            <tr>
                <td><?php echo htmlspecialchars($client['nom']); ?></td>
                <td><?php echo htmlspecialchars($client['prenom']); ?></td>
                <td><?php echo htmlspecialchars($client['email']); ?></td>
                <td><?php echo $client['nb_comptes']; ?> compte(s)</td>
                <td><strong><?php echo number_format($client['solde_total'], 2, ',', ' '); ?> €</strong></td>
                <td>
                    <a href="/banque/employe/comptes.php?client_id=<?php echo $client['client_id']; ?>"
                       class="btn btn-neutre" style="font-size: 12px; padding: 5px 12px;">
                        Voir comptes
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
