<?php
$typeUtilisateur = $_SESSION['type'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NavBar</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
<div class="flex justify-center mt-4">
    <nav  class="bg-gradient-to-r from-gray-400 to-slate-600 text-white p-4 rounded-full w-1/2">
        <!-- Logo -->
        <div class="flex items-center space-x-4 justify-between">
            <img src="img/forma_logo.png" alt="Logo Forma" class="h-12 pl-8">
            <a href="index.php" class="text-lg font-semibold hover:text-gray-300 transition duration-300">Accueil</a>
            <?php if ($typeUtilisateur == 'admin' || $typeUtilisateur == 'directeur') : ?>
                <a href="gerer.php" class="text-lg font-semibold hover:text-gray-300 transition duration-300">Gérer</a>
            <?php endif; ?>
            <?php if ($typeUtilisateur != 'directeur') : ?>
                <a href="inscription.php" class="text-lg font-semibold hover:text-gray-300 transition duration-300">Inscription</a>
            <?php endif; ?>
            <a href="logout.php" class ="text-lg font-semibold hover:text-red-700 text-red-500 transition duration-300"> Se déconnecter</a>
        </div>
    </nav>
    </div>

</body>

</html>