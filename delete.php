<?php
session_start();

// Inclusion de la connexion à la base de données
include_once("includes/database.php");

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Vous devez être connecté pour effectuer cette action.";
    header("Location: connexion.php");
    exit();
}

// Vérifie que l'utilisateur est "admin" ou "directeur"
if (!isset($_SESSION['type']) || !in_array($_SESSION['type'], ['admin', 'directeur'])) {
    $_SESSION['error_message'] = "Vous n'avez pas les permissions pour supprimer une formation.";
    header("Location: gerer.php");
    exit();
}

// Vérifie qu'un ID de formation est passé en paramètre
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Aucune formation spécifiée pour suppression.";
    header("Location: gerer.php");
    exit();
}

$idFormation = intval($_GET['id']); // Conversion sécurisée de l'ID

try {
    // Préparation de la requête de suppression
    $stmt = $pdo->prepare("DELETE FROM formation WHERE id_formation = ?");
    $stmt->execute([$idFormation]);

    // Vérifie si une formation a bien été supprimée
    if ($stmt->rowCount() > 0) {
        $_SESSION['success_message'] = "La formation a été supprimée avec succès.";
    } else {
        $_SESSION['error_message'] = "La formation spécifiée n'existe pas.";
    }
} catch (Exception $e) {
    // Gestion des erreurs
    $_SESSION['error_message'] = "Une erreur est survenue : " . $e->getMessage();
}

// Redirection vers la page de gestion
header("Location: gerer.php");
exit();
?>
