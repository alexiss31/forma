<?php
include_once "class/Compteur.php";
include "connexion.php";
session_start();

// Détruire toutes les sessions
$_SESSION = array();
session_destroy();

// Rediriger vers la page d'accueil
header("Location: connexion.php");
exit();
?>