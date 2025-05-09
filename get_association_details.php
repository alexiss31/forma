<?php
include_once("includes/database.php");

header('Content-Type: application/json'); // Indique que la réponse est en JSON

if (isset($_GET['id_association'])) {
    $idAssociation = intval($_GET['id_association']);

    // Requête pour récupérer les détails de l'association
    $stmt = $pdo->prepare("SELECT n_icom, nom_association, prenom_interlocuteur, nom_interlocuteur, email_interlocuteur, tel_interlocuteur, fax_interlocuteur FROM association WHERE id_association = ?");
    $stmt->execute([$idAssociation]);

    $association = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($association) {
        echo json_encode($association); // Retourne les données sous forme JSON
    } else {
        echo json_encode(['error' => 'Association non trouvée']);
    }
} else {
    echo json_encode(['error' => 'ID de l\'association manquant']);
}
