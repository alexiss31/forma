<?php
session_start();

include_once("includes/database.php");

//Vérifie que l'utilisateur est authentifié sinon reviens à la page de connexion
if (!$_SESSION["user_id"]) {
    header("Location: connexion.php");
    exit();
}

// Vérification des rôles : uniquement accessible par "admin" ou "directeur"
if (!isset($_SESSION['type']) || !in_array($_SESSION['type'], ['admin', 'directeur'])) {
    header('Location: index.php');
    exit();
}

//Affichage d'un message de succès si une formation a été modifié
if (isset($_SESSION['update_success_message'])) {
    echo '<p class="bg-green-100 text-green-800 px-4 py-2 rounded-md text-center">' . htmlspecialchars($_SESSION['update_success_message']) . '</p>';
    unset($_SESSION['update_success_message']); // Supprimer le message après l'avoir affiché
}

//Affichage d'un message de succès après la suppression avec succès d'une formation
if (isset($_SESSION['success_message'])) {
    echo '<p class="bg-green-100 text-green-800 px-4 py-2 rounded-md text-center">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
    unset($_SESSION['success_message']);
}

//Affichage d'un message d'erreur si la suppression d'une formation a échouée
if (isset($_SESSION['error_message'])) {
    echo '<p class="bg-red-100 text-red-800 px-4 py-2 rounded-md text-center">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
    unset($_SESSION['error_message']);
}

// Récupérer toutes les formations
$query = "SELECT * FROM formation";
$formations = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer les formations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-800">

    <!-- Navbar -->
    <?php include_once 'includes/navbar.php'; ?>

     <header style="background-color: #5FB6B6;" class="text-white py-4 mt-8">
        <div class="container mx-auto text-center">
            <h1 class="text-2xl font-bold">Gérer les Formations</h1>
        </div>
    </header>
    <div class="bg-white rounded-lg shadow-lg p-6">
        <ul class="space-y-4">
            <?php foreach ($formations as $formation) : ?>
                <li class="flex justify-between items-center bg-gray-50 p-4 rounded-md shadow-sm hover:shadow-md transition duration-300">
                    <div class="text-lg font-semibold">
                        <?= htmlspecialchars($formation['libelle']) ?>
                    </div>
                    <div class="space-x-4">
                        <a href="modifier.php?id=<?= $formation['id_formation'] ?>" class="bg-green-500 text-white px-4 py-2 rounded-md font-medium hover:bg-green-700 transition duration-300">
                            Modifier
                        </a>
                        <a href="delete.php?id=<?= $formation['id_formation'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette formation ?');" class="bg-red-500 text-white px-4 py-2 rounded-md font-medium hover:bg-red-700 transition duration-300">
                            Supprimer
                        </a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    </div>
</body>

</html>