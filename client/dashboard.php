<?php
// ============================================================
// client/dashboard.php
// Tableau de bord du client connecté
// Affiche ses comptes et le solde total
// ============================================================

require_once '../includes/session.php';
require_once '../config/database.php';

verifier_role('client');

// ============================================================
// Récupérer le profil client de l'utilisateur connecté
// ============================================================
$req_client = $pdo->prepare("SELECT id FROM clients WHERE utilisateur_id = ?");
$req_client->execute([$_SESSION['utilisateur_id']]);
$client = $req_client->fetch();

if (!$client) {
    die("Erreur : profil client introuvable. Contactez votre conseiller.");
}

$client_id = $client['id'];

// ============================================================
// Récupérer tous les comptes du client
// ============================================================
$req_comptes = $pdo->prepare("
    SELECT id, iban, solde, created_at
    FROM comptes
    WHERE client_id = ?
    ORDER BY created_at ASC
");
$req_comptes->execute([$client_id]);
$mes_comptes = $req_comptes->fetchAll();

// Calcul du solde total de tous les comptes
$solde_total = 0;
foreach ($mes_comptes as $compte) {
    $solde_total += $compte['solde'];
}

// ============================================================
// Récupérer les 5 dernières transactions
// ============================================================
$req_transactions = $pdo->prepare("
    SELECT
        t.type_operation,
        t.montant,
        t.description,
        t.date_operation,
        c.iban
    FROM transactions t
    JOIN comptes c ON t.compte_id = c.id
    WHERE c.client_id = ?
    ORDER BY t.date_operation DESC
    LIMIT 5
");
$req_transactions->execute([$client_id]);
$dernieres_transactions = $req_transactions->fetchAll();

// Récupérer les infos du conseiller (employé)
$req_conseiller = $pdo->prepare("
    SELECT u.nom, u.prenom, u.email
    FROM clients cl
    JOIN employes e ON cl.employe_id = e.id
    JOIN utilisateurs u ON e.utilisateur_id = u.id
    WHERE cl.id = ?
");
$req_conseiller->execute([$client_id]);
$conseiller = $req_conseiller->fetch();

$titre_page = "Mon espace client";
include '../includes/header.php';
?>

<div class="page-titre">
    <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['prenom']); ?> !</h1>
    <p>Consultez vos comptes et votre historique</p>
</div>

<!-- Solde total mis en valeur -->
<div class="carte" style="border-left: 4px solid var(--couleur-accent); background: linear-gradient(135deg, #1a2e4a 0%, #2c5282 100%); color: white;">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <p style="font-size: 13px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.75; margin-bottom: 8px;">
                Patrimoine total
            </p>
            <div class="solde-grand" style="color: var(--couleur-accent);">
                <?php echo number_format($solde_total, 2, ',', ' '); ?> €
            </div>
            <p style="font-size: 13px; opacity: 0.65; margin-top: 6px;">
                Répartis sur <?php echo count($mes_comptes); ?> compte(s)
            </p>
        </div>
        <div style="text-align: right; opacity: 0.7; font-size: 60px;">
            ⬡
        </div>
    </div>
</div>

<!-- Liste des comptes -->
<div class="tableau-conteneur">
    <div class="tableau-entete">
        <h2>Mes comptes</h2>
        <a href="/banque/client/compte.php" class="btn btn-neutre" style="font-size: 13px; padding: 7px 16px;">
            Détails
        </a>
    </div>

    <table>
        <thead>
            <tr>
                <th>IBAN</th>
                <th>Solde</th>
                <th>Ouvert le</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($mes_comptes as $compte): ?>
            <tr>
                <td class="iban-affichage" style="background: none; border: none; padding: 13px 16px;">
                    <?php echo htmlspecialchars($compte['iban']); ?>
                </td>
                <td>
                    <span style="font-size: 20px; font-weight: bold; color: var(--couleur-principale);">
                        <?php echo number_format($compte['solde'], 2, ',', ' '); ?> €
                    </span>
                </td>
                <td><?php echo date('d/m/Y', strtotime($compte['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Dernières transactions -->
<div class="tableau-conteneur">
    <div class="tableau-entete">
        <h2>Dernières opérations</h2>
        <a href="/banque/client/historique.php" class="btn btn-neutre" style="font-size: 13px; padding: 7px 16px;">
            Voir tout
        </a>
    </div>

    <?php if (count($dernieres_transactions) === 0): ?>
        <div style="padding: 32px; text-align: center; color: #718096;">
            Aucune transaction pour le moment.
        </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($dernieres_transactions as $trans): ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($trans['date_operation'])); ?></td>
                <td>
                    <span class="badge badge-<?php echo $trans['type_operation']; ?>">
                        <?php echo ucfirst($trans['type_operation']); ?>
                    </span>
                </td>
                <td><?php echo htmlspecialchars($trans['description'] ?: '—'); ?></td>
                <td style="font-weight: bold;">
                    <?php
                    // Afficher en rouge pour retrait/virement, vert pour dépôt
                    $couleur = $trans['type_operation'] === 'depot' ? 'var(--couleur-succes)' : 'var(--couleur-danger)';
                    $signe   = $trans['type_operation'] === 'depot' ? '+' : '-';
                    ?>
                    <span style="color: <?php echo $couleur; ?>">
                        <?php echo $signe . number_format($trans['montant'], 2, ',', ' '); ?> €
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- Informations conseiller -->
<?php if ($conseiller): ?>
<div class="carte">
    <h2>Mon conseiller</h2>
    <div class="info-ligne">
        <span class="info-label">Nom</span>
        <span class="info-valeur"><?php echo htmlspecialchars($conseiller['prenom'] . ' ' . $conseiller['nom']); ?></span>
    </div>
    <div class="info-ligne">
        <span class="info-label">Email</span>
        <span class="info-valeur"><?php echo htmlspecialchars($conseiller['email']); ?></span>
    </div>
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
