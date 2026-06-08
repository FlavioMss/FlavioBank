<?php
// ============================================================
// client/compte.php
// Détails des comptes du client (IBAN, solde, infos)
// Le client ne peut rien modifier sur cette page
// ============================================================

require_once '../includes/session.php';
require_once '../config/database.php';

verifier_role('client');

// Récupérer le profil client
$req_client = $pdo->prepare("SELECT id FROM clients WHERE utilisateur_id = ?");
$req_client->execute([$_SESSION['utilisateur_id']]);
$client = $req_client->fetch();
$client_id = $client['id'];

// Récupérer tous les comptes du client
$req_comptes = $pdo->prepare("
    SELECT id, iban, solde, created_at
    FROM comptes
    WHERE client_id = ?
    ORDER BY created_at ASC
");
$req_comptes->execute([$client_id]);
$mes_comptes = $req_comptes->fetchAll();

$titre_page = "Mes comptes — Informations";
include '../includes/header.php';
?>

<div class="page-titre">
    <h1>Mes informations bancaires</h1>
    <p>Détails de vos comptes et IBAN</p>
</div>

<!-- Informations personnelles -->
<div class="carte">
    <h2>Mes informations personnelles</h2>
    <div class="info-ligne">
        <span class="info-label">Nom complet</span>
        <span class="info-valeur">
            <?php echo htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?>
        </span>
    </div>
    <div class="info-ligne">
        <span class="info-label">Email</span>
        <span class="info-valeur"><?php echo htmlspecialchars($_SESSION['email']); ?></span>
    </div>
    <div class="info-ligne">
        <span class="info-label">Rôle</span>
        <span class="info-valeur">
            <span class="badge badge-client">Client</span>
        </span>
    </div>
</div>

<!-- Un bloc par compte bancaire -->
<?php foreach ($mes_comptes as $index => $compte): ?>
<div class="carte">
    <h2>Compte <?php echo ($index + 1); ?></h2>

    <div class="info-ligne">
        <span class="info-label">IBAN</span>
        <span class="info-valeur">
            <span class="iban-affichage"><?php echo htmlspecialchars($compte['iban']); ?></span>
        </span>
    </div>

    <div class="info-ligne">
        <span class="info-label">Solde actuel</span>
        <span class="info-valeur">
            <span class="solde-grand <?php echo $compte['solde'] >= 0 ? 'solde-positif' : 'solde-negatif'; ?>">
                <?php echo number_format($compte['solde'], 2, ',', ' '); ?> €
            </span>
        </span>
    </div>

    <div class="info-ligne">
        <span class="info-label">Date d'ouverture</span>
        <span class="info-valeur"><?php echo date('d/m/Y', strtotime($compte['created_at'])); ?></span>
    </div>

    <div class="info-ligne">
        <span class="info-label">BIC</span>
        <span class="info-valeur" style="font-family: monospace;">BNPAFRPPXXX</span>
    </div>

    <div class="info-ligne">
        <span class="info-label">Domiciliation</span>
        <span class="info-valeur">Banque Nationale — Agence Principale</span>
    </div>

    <div style="margin-top: 16px;">
        <a href="/banque/client/historique.php?compte_id=<?php echo $compte['id']; ?>"
           class="btn btn-neutre">
            Voir l'historique de ce compte
        </a>
    </div>
</div>
<?php endforeach; ?>

<?php if (count($mes_comptes) === 0): ?>
<div class="alerte alerte-info">
    Aucun compte bancaire associé à votre profil. Contactez votre conseiller.
</div>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
