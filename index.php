<?php
session_start();
include_once("includes/database.php");

// Vérification si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

// Récupération des informations de l'utilisateur
$typeUtilisateur = $_SESSION['type'];

// Récupération des formations dans la base de données uniquement si l'utilisateur n'est pas directeur
$formations = [];
if ($typeUtilisateur !== 'directeur') {
    $stmt = $pdo->prepare("
        SELECT 
            f.id_formation, 
            f.libelle 
        FROM Formation f
    ");
    $stmt->execute();
    $formations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catalogue des Formations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        // Fonction pour afficher le formulaire
        function toggleForm() {
            var form = document.getElementById('create-form');
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';

            const button = document.getElementById('button');
            if(button.innerText === 'Créer une formation'){
                button.innerText = 'Annuler la formation';
            } else{
                button.innerText = 'Créer une formation';
            }
        }
    </script>
</head>
<?php include_once('includes/navbar.php'); ?>

<body class="bg-gray-100 text-gray-800">
    <header class="bg-teal-600 text-white py-5 mt-8">
        <div class="container mx-auto text-center">
            <h1 class="text-3xl font-bold">Catalogue des Formations</h1>
        </div>
    </header>

    <main class="container mx-auto py-6">
        <!-- Formulaire de création de formation visible seulement pour le directeur et l'admin -->
        <?php if ($typeUtilisateur === 'directeur' || $typeUtilisateur === 'admin') : ?>
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <!-- Encadré avec la question et le bouton -->
                <h2 class="text-xl font-semibold text-teal-600 mb-4">Vous souhaitez créer une formation ?</h2>
                <button onclick="toggleForm()" id="button" class="bg-teal-600 text-white py-2 px-4 rounded-lg hover:bg-teal-700 focus:outline-none mb-4">
                    Créer une formation
                </button>

                <!-- Formulaire caché initialement -->
                <div id="create-form" style="display: none;">
                    <form action="create_formation.php" method="POST">

                        <div class="mb-4">
                            <label for="libelle" class="block text-sm font-semibold text-gray-800">Libellé de la formation</label>
                            <input type="text" name="libelle" id="libelle" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="mb-4">
                            <label for="date" class="block text-sm font-semibold text-gray-800">Date de la formation</label>
                            <input type="date" name="date" id="date" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>
                        <div class="flex gap-4">
                            <div>
                                <label for="heureDeb" class="inline flex text-sm font-semibold text-gray-800">Heure de début de la formation</label>
                                <div class="mb-4 block-grid flex items-center">
                                    <input type="time" name="heureDeb" id="heureDeb" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>
                            </div>
                            <div>
                                <label for="heureFin" class="inline flex text-sm font-semibold text-gray-800">Heure de fin de la formation</label>
                                <div class="mb-4 block-grid flex items-center">
                                    <input type="time" name="heureFin" id="heureFin" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="lieu" class="block text-sm font-semibold text-gray-800">Lieu de la formation</label>
                            <input type="text" name="lieu" id="lieu" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="mb-4">
                            <label for="intervenant" class="block text-sm font-semibold text-gray-800">Intervenant de la formation</label>
                            <input type="text" name="intervenant" id="intervenant" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="mb-4">
                            <label for="public" class="block text-sm font-semibold text-gray-800">Public</label>
                            <input type="text" name="public" id="public" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="mb-4">
                            <label for="objectifs" class="block text-sm font-semibold text-gray-800">Objectifs de la formation</label>
                            <textarea name="objectifs" id="objectifs" rows="2" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="contenu" class="block text-sm font-semibold text-gray-800">Contenu de la formation</label>
                            <textarea name="contenu" id="contenu" rows="4" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500"></textarea>
                        </div>

                        <div class="mb-4">
                            <label for="cout" class="block text-sm font-semibold text-gray-800">Coût (€)</label>
                            <input type="number" name="cout" id="cout" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="mb-4">
                            <label for="dateLimit" class="block text-sm font-semibold text-gray-800">Date limite d'inscription</label>
                            <input type="date" name="dateLimit" id="dateLimit" required class="w-full p-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        </div>

                        <button type="submit" class="bg-teal-600 text-white py-2 px-4 rounded-lg hover:bg-teal-700 focus:outline-none">Créer la formation</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($typeUtilisateur != 'directeur') : ?>
            <!-- Liste des formations pour les autres utilisateurs -->
            <?php if (count($formations) > 0) : ?>
                <div class="grid gap-4">
                    <?php foreach ($formations as $formation) : ?>
                        <div class="flex justify-between items-center bg-white p-4 shadow rounded">
                            <span class="text-lg font-medium"><?= htmlspecialchars($formation['libelle']) ?></span>
                            <a href="details.php?id=<?= $formation['id_formation'] ?>" class="text-blue-500 hover:text-blue-700"> Voir les détails de la formation ➔ </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <p class="text-center text-gray-600">Aucune formation disponible pour le moment.</p>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</body>

</html>