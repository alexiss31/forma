<?php
include 'includes/database.php';
include_once 'class/Utilisateur.php'; // Inclure la classe Utilisateur
session_start();

// Création d'une instance de la classe Utilisateur
$utilisateur = new Utilisateur($pdo);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $erreur = "";
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $mdp = $_POST['mdp'];

    if ($email === false) {
        echo"Adresse e-mail invalide!";
    } elseif (empty($mdp)) {
        echo "Le mot de passe ne peut pas être vide!";
    } else {
        $user = $utilisateur->seConnecter($email, $mdp);
        if($user!=null){
            $_SESSION['user_id'] = $user['id_stagiaire'];
            $_SESSION['type'] = $user['type'];
            header('Location: index.php');
            exit(); // Utiliser exit() après header() pemet d'arrêter l'exécution du script
        }else{
            echo "Identifiants incorrects";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification</title>
    <script type="text/javascript" src="includes/controleFormulaire.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="src/output.css" rel="stylesheet">
</head>

<body class="bg-gradient-to-r from-gray-500 to-slate-600">

    <section class="flex justify-between items-center w-full h-screen p-9">

        <div class="w-1/2">
            <div class="bg-white rounded-3xl p-4 m-auto" style="max-width:600px;">
            <h1 class="font-bold text-2xl flex justify-center">Connexion</h1>

            <form class="mt-6" action = "connexion.php" method ="post">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label> <br>
                    <input name="email" type="email" id="email" class="mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"> <br>
                </div>

                <div class="mb-6">
                    <label for="mdp" class="block text-sm font-medium text-gray-700">Mot de passe</label> <br>
                    <input name="mdp" type="password" id="mdp" class="mt-1 block w-full px-4 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500"> <br>
                </div>

                <button type="submit" class="w-full py-2 px-4 text-white font-semibold rounded-3xl transition duration-300  " style="background-color: #5FB6B6;" onmouseover="this.style.backgroundColor='#4D9C9C'" onmouseout="this.style.backgroundColor='#5FB6B6'">Se connecter</button>
            </form>
            </div>
        </div>

        <div class="w-1/2">
            <img src="img/forma_logo.png" alt="Logo Forma" class="m-auto d-block">
        </div>
    </section>

</body>

</html>