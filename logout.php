<?php
session_save_path('sessions');
session_start();

// Détruire toutes les sessions
$_SESSION = array();
session_unset();
session_destroy();

// Rediriger vers la page d'accueil
header("Location: connexion.php");
exit();
?>