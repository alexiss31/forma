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
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error_message'] = "ID de formation invalide.";
    header("Location: gerer.php");
    exit();
}

$idFormation = intval($_GET['id']); // Conversion sécurisée de l'ID


try {
    // Début de la transaction
    $pdo->beginTransaction();

    // Récupération de l'ID information associé à la formation
    $stmtInfo = $pdo->prepare("SELECT id_information FROM formation WHERE id_formation = ?");
    $stmtInfo->execute([$idFormation]);
    $idInformation = $stmtInfo->fetchColumn();

    if (!$idInformation) {
        throw new Exception("Formation introuvable ou déjà supprimée.");
    }

    // Suppression des enregistrements dans `stagiaire_formation`
    $stmtStagiaireFormation = $pdo->prepare("DELETE FROM stagiaire_formation WHERE id_formation = ?");
    $stmtStagiaireFormation->execute([$idFormation]);

    // Suppression des enregistrements dans `intervenir` pour cette formation
    $stmtIntervenir = $pdo->prepare("DELETE FROM intervenir WHERE id_formation = ?");
    $stmtIntervenir->execute([$idFormation]);

    // Suppression des enregistrements dans `intervenant` pour cette formation
    $stmtDeleteIntervenants = $pdo->prepare("
    DELETE FROM intervenant 
    WHERE id_intervenant NOT IN (
        SELECT DISTINCT id_intervenant 
        FROM intervenir
    )
");
    $stmtDeleteIntervenants->execute();


    // Suppression des enregistrements dans `viser` pour cette formation
    $stmtViser = $pdo->prepare("DELETE FROM viser WHERE id_formation = ?");
    $stmtViser->execute([$idFormation]);

    // Suppression des enregistrements dans `contenir` pour cette formation
    $stmtContenir = $pdo->prepare("DELETE FROM contenir WHERE id_formation = ?");
    $stmtContenir->execute([$idFormation]);

    // Suppression des contenus non utilisés
    $stmtDeleteContenu = $pdo->prepare("
        DELETE FROM contenu 
        WHERE id_contenu NOT IN (SELECT DISTINCT id_contenu FROM contenir)
    ");
    $stmtDeleteContenu->execute();

    // Suppression des publics non utilisés
    $stmtDeletePublic = $pdo->prepare("
        DELETE FROM public 
        WHERE id_public NOT IN (SELECT DISTINCT id_public FROM viser)
    ");
    $stmtDeletePublic->execute();

    // Suppression de la formation elle-même
    $stmtFormation = $pdo->prepare("DELETE FROM formation WHERE id_formation = ?");
    $stmtFormation->execute([$idFormation]);

    // Suppression de l'information associée à la formation
    $stmtInfoDelete = $pdo->prepare("DELETE FROM information WHERE id_information = ?");
    $stmtInfoDelete->execute([$idInformation]);

    $stmtVerif = $pdo->prepare("SELECT * FROM formation WHERE id_formation = ?");
    $stmtVerif->execute([$idFormation]);

    // Vérification finale
    if ($stmtVerif->rowCount() == 0) {
        $_SESSION['success_message'] = "La formation et toutes ses dépendances ont été supprimées avec succès.";
    } else {
        throw new Exception("La formation spécifiée n'a pas pu être supprimée.");
    }

    // Commit de la transaction
    $pdo->commit();
} catch (Exception $e) {
    $errorInfo = $pdo->errorInfo();
    $_SESSION['error_message'] = "Erreur lors de la suppression : " . $e->getMessage() . " | SQLSTATE: " . $errorInfo[0] . " | Code erreur: " . $errorInfo[1];
    $pdo->rollBack();
}


// Redirection après suppression
header("Location: gerer.php");
exit();
