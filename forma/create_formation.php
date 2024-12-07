<?php
session_start();
include_once("includes/database.php");

// Vérification si l'utilisateur est connecté et autorisé
if (!isset($_SESSION['user_id']) || !($_SESSION['type'] === 'directeur' || $_SESSION['type'] === 'admin')) {
    header("Location: connexion.php");
    exit();
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $libelle = htmlspecialchars($_POST['libelle']);
    $dateFormation = htmlspecialchars($_POST['date']);
    $heureDeb = htmlspecialchars($_POST['heureDeb']);
    $heureFin = htmlspecialchars($_POST['heureFin']);
    $lieu = htmlspecialchars($_POST['lieu']);
    $intervenantNom = htmlspecialchars($_POST['intervenant']);
    $publicCible = htmlspecialchars($_POST['public']);
    $objectifs = htmlspecialchars($_POST['objectifs']);
    $contenuFormation = htmlspecialchars($_POST['contenu']);
    $cout = (int)$_POST['cout'];
    $dateLimiteInscription = htmlspecialchars($_POST['dateLimit']);
    $nb_max_participants = htmlspecialchars($_POST['nb_max_participants']);

    try {
        // Démarrage de la transaction
        $pdo->beginTransaction();

        // 1. Insérer les informations générales de la formation (table `Information`)
        $stmtInfo = $pdo->prepare("
            INSERT INTO Information (date_formation, heureDeb, heureFin, lieu, nb_max_participants) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtInfo->execute([$dateFormation, $heureDeb, $heureFin, $lieu, $nb_max_participants]);
        $idInformation = $pdo->lastInsertId();

        // 2. Insérer la formation (table `Formation`)
        $stmtFormation = $pdo->prepare("
            INSERT INTO Formation (libelle, objectifs, cout, date_limite_inscription, id_information) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmtFormation->execute([$libelle, $objectifs, $cout, $dateLimiteInscription, $idInformation]);
        $idFormation = $pdo->lastInsertId();

        // 3. Insérer l'intervenant (table `intervenant`) s'il n'existe pas déjà
        $stmtCheckIntervenant = $pdo->prepare("
            SELECT id_intervenant FROM intervenant WHERE nom = ?
        ");
        $stmtCheckIntervenant->execute([$intervenantNom]);
        $idIntervenant = $stmtCheckIntervenant->fetchColumn();

        if (!$idIntervenant) {
            $stmtIntervenant = $pdo->prepare("
                INSERT INTO intervenant (nom, prenom) 
                VALUES (?, ?)
            ");
            $intervenantPrenom = ""; // Pas fourni dans le formulaire
            $stmtIntervenant->execute([$intervenantNom, $intervenantPrenom]);
            $idIntervenant = $pdo->lastInsertId();
        }

        // 4. Associer l'intervenant à la formation (table `intervenir`)
        $stmtIntervenir = $pdo->prepare("
            INSERT INTO intervenir (id_intervenant, id_formation) 
            VALUES (?, ?)
        ");
        $stmtIntervenir->execute([$idIntervenant, $idFormation]);

        // 5. Insérer le public cible (table `Public`) s'il n'existe pas déjà
        $stmtCheckPublic = $pdo->prepare("
            SELECT id_public FROM Public WHERE libelle = ?
        ");
        $stmtCheckPublic->execute([$publicCible]);
        $idPublic = $stmtCheckPublic->fetchColumn();

        if (!$idPublic) {
            $stmtPublic = $pdo->prepare("
                INSERT INTO Public (libelle) 
                VALUES (?)
            ");
            $stmtPublic->execute([$publicCible]);
            $idPublic = $pdo->lastInsertId();
        }

        // 6. Associer le public cible à la formation (table `viser`)
        $stmtViser = $pdo->prepare("
            INSERT INTO viser (id_formation, id_public) 
            VALUES (?, ?)
        ");
        $stmtViser->execute([$idFormation, $idPublic]);

        // 7. Insérer le contenu de la formation (table `Contenu`)
        $stmtContenu = $pdo->prepare("
            INSERT INTO Contenu (libelle) 
            VALUES (?)
        ");
        $stmtContenu->execute([$contenuFormation]);
        $idContenu = $pdo->lastInsertId();

        // 8. Associer le contenu à la formation (table `Contenir`)
        $stmtContenir = $pdo->prepare("
            INSERT INTO Contenir (id_formation, id_contenu) 
            VALUES (?, ?)
        ");
        $stmtContenir->execute([$idFormation, $idContenu]);

        // Validation de la transaction
        $pdo->commit();

        // Redirection après succès
        header("Location: index.php?success=1");
        exit();
    } catch (Exception $e) {
        // Annulation de la transaction en cas d'erreur
        $pdo->rollBack();
        echo "Erreur lors de la création de la formation : " . $e->getMessage();
    }
}
