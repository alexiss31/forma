<?php
session_save_path('sessions');
session_start();

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
    // Début de la transaction
    $pdo->beginTransaction();

    // Étape 1 : Récupérer l'ID de l'information liée à la formation
    $stmtInfo = $pdo->prepare("SELECT id_information FROM formation WHERE id_formation = ?");
    $stmtInfo->execute([$idFormation]);
    $idInformation = $stmtInfo->fetchColumn();

    if (!$idInformation) {
        throw new Exception("Impossible de trouver les informations associées à la formation.");
    }

    // Étape 2 : Supprimer les dépendances liées à cette formation
    try {
        $stmtIntervenir = $pdo->prepare("DELETE FROM intervenir WHERE id_formation = ?");
        $stmtIntervenir->execute([$idFormation]);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la suppression des dépendances dans la table 'intervenir': " . $e->getMessage());
    }

    try {
        $stmtViser = $pdo->prepare("DELETE FROM viser WHERE id_formation = ?");
        $stmtViser->execute([$idFormation]);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la suppression des dépendances dans la table 'viser': " . $e->getMessage());
    }

    try {
        $stmtContenir = $pdo->prepare("DELETE FROM contenir WHERE id_formation = ?");
        $stmtContenir->execute([$idFormation]);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la suppression des dépendances dans la table 'contenir': " . $e->getMessage());
    }

    // Étape 3 : Supprimer la formation elle-même
    try {
        $stmtFormation = $pdo->prepare("DELETE FROM formation WHERE id_formation = ?");
        $stmtFormation->execute([$idFormation]);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la suppression de la formation dans la table 'formation': " . $e->getMessage());
    }

    // Étape 4 : Supprimer les informations associées
    try {
        $stmtInfoDelete = $pdo->prepare("DELETE FROM information WHERE id_information = ?");
        $stmtInfoDelete->execute([$idInformation]);
    } catch (PDOException $e) {
        throw new Exception("Erreur lors de la suppression des informations associées dans la table 'information': " . $e->getMessage());
    }

    // Vérification si la formation a bien été supprimée
    $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM formation WHERE id_formation = ?");
    $stmtCheck->execute([$idFormation]);
    $formationExists = $stmtCheck->fetchColumn();

    if ($formationExists == 0) {
        $_SESSION['success_message'] = "La formation et toutes ses dépendances ont été supprimées avec succès.";
        $pdo->commit();
    } else {
        throw new Exception("La formation n'a pas été correctement supprimée.");
    }
} catch (Exception $e) {
    // Annulation de la transaction en cas d'erreur
    $pdo->rollBack();
    error_log("Erreur lors de la suppression : " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors de la suppression : " . $e->getMessage();
    header("Location: gerer.php");
    exit();
}

// Redirection après suppression
header("Location: gerer.php");
exit();
?>
