<?php
require_once 'config/database.php';

$nouveau_hash = password_hash('password123', PASSWORD_DEFAULT);

$req = $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ?");
$req->execute([$nouveau_hash]);

echo "Mots de passe mis à jour ! Hash utilisé : " . $nouveau_hash;
echo "<br><br><a href='login.php'>→ Aller à la connexion</a>";
?>