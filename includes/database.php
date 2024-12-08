<?php
	// Définitions de constantes pour la connexion à MySQL
	$hote= "localhost";
	$login= "root";
	$mdp= "";
	$nombd= "forma";

	// Connection au serveur
	try { 
			$pdo = new PDO("mysql:host=$hote;dbname=$nombd",$login,$mdp);
	} catch ( Exception $e ) {
		die("Erreur de connexion à la base de données : " . $e->getMessage());
	}
?>