<?php
session_start();
include_once("includes/database.php");

// Vérification des champs du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = htmlspecialchars($_POST['libelle']);
    $objectifs = htmlspecialchars($_POST['objectifs']);
    $cout = $_POST['cout'];

    // Préparation de la requête SQL pour insérer la formation
    $stmt = $pdo->prepare("
        INSERT INTO Formation (libelle, objectifs, cout) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$libelle, $objectifs, $cout]);

    // Redirection vers l'accueil après création de la formation
    header("Location: index.php");
    exit();
}
?>
