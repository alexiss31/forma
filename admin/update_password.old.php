<?php
include '../includes/database.php';

// Sélectionner tous les utilisateurs
$stmt = $pdo->query("SELECT id_utilisateur, mdp FROM Utilisateur");
$utilisateurs = $stmt->fetchAll();

foreach ($utilisateurs as $user) {
    $id = $user['id_utilisateur'];
    $mot_de_passe_en_clair = $user['mdp'];

    // Crypter le mot de passe
    $mot_de_passe_crypte = hash("sha256", $mot_de_passe_en_clair);

    // Mettre à jour la base de données
    try {
        $stmt = $pdo->prepare("UPDATE Utilisateur SET mdp = ? WHERE id_utilisateur = ?");
        $stmt->execute([$mot_de_passe_crypte, $id]);
        echo "Mot de passe mis à jour pour l'utilisateur ID : $id<br>";
    } catch (PDOException $e) {
        die("Erreur lors de la mise à jour du mot de passe pour l'utilisateur ID : $id. Détails de l'erreur : " . $e->getMessage());
    }

    echo "Mot de passe en clair : $mot_de_passe_en_clair<br>";
    echo "Mot de passe haché : $mot_de_passe_crypte<br>";
}

echo "Mots de passe mis à jour avec succès !";
?>
