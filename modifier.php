<?php
session_start();
include_once("includes/database.php");

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

// Vérifie que l'utilisateur est "admin" ou "directeur"
if (!isset($_SESSION['type']) || !in_array($_SESSION['type'], ['admin', 'directeur'])) {
    header('Location: index.php');
    exit();
}

// Vérifie qu'un ID de formation est passé en paramètre
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: gerer.php");
    exit();
}

$idFormation = $_GET['id'];

// Récupération des informations de la formation à modifier
$stmt = $pdo->prepare("SELECT * FROM formation WHERE id_formation = ?");
$stmt->execute([$idFormation]);
$formation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formation) {
    echo "Formation non trouvée.";
    exit();
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = trim($_POST['libelle']);
    $objectifs = trim($_POST['objectifs']);
    $cout = trim($_POST['cout']);
    $dateLimiteInscription = trim($_POST['date_limite_inscription']);
    $idInformation = trim($_POST['id_information']);

    // Validation des champs
    if (empty($libelle) || empty($objectifs) || empty($dateLimiteInscription) || empty($idInformation)) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        // Mise à jour dans la base de données
        $stmt = $pdo->prepare("UPDATE formation 
                               SET libelle = ?, objectifs = ?, cout = ?, date_limite_inscription = ?, id_information = ?
                               WHERE id_formation = ?");
        $stmt->execute([$libelle, $objectifs, $cout, $dateLimiteInscription, $idInformation, $idFormation]);

        // Ajouter un message de succès dans la session
        $_SESSION['update_success_message'] = "La formation a été mise à jour avec succès.";

        // Redirection après modification
        header("Location: gerer.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier la formation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-800 font-sans">
    <?php include_once 'includes/navbar.php'; ?>
    <header style="background-color: #5FB6B6;" class="text-white py-4 mt-8">
        <div class="container mx-auto text-center">
            <h1 class="text-2xl font-bold">Gérer les Formations</h1>
        </div>
    </header>
    <div class="container mx-auto p-8">


        <?php if (isset($error)): ?>
            <p class="text-red-500 text-center mb-4"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" action="" class="bg-white shadow-md rounded-lg p-6 space-y-6">
            <div>
                <label for="libelle" class="block text-sm font-medium">Libellé :</label>
                <input type="text" id="libelle" name="libelle" value="<?= htmlspecialchars($formation['libelle']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div>
                <label for="objectifs" class="block text-sm font-medium">Objectifs :</label>
                <input type="text" id="objectifs" name="objectifs" value="<?= htmlspecialchars($formation['objectifs']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div>
                <label for="cout" class="block text-sm font-medium">Coût (€) :</label>
                <input type="number" id="cout" name="cout" value="<?= htmlspecialchars($formation['cout']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div>
                <label for="date_limite_inscription" class="block text-sm font-medium">Date limite d'inscription :</label>
                <input type="date" id="date_limite_inscription" name="date_limite_inscription"
                    value="<?= htmlspecialchars($formation['date_limite_inscription']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div>
                <label for="id_information" class="block text-sm font-medium">ID Information :</label>
                <input type="number" id="id_information" name="id_information" value="<?= htmlspecialchars($formation['id_information']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div class="flex justify-between">
                <button type="submit" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
                    Modifier
                </button>
                <a href="gerer.php" class="bg-red-500 text-gray-800 py-2 px-4 rounded-md hover:bg-red-700 transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</body>

</html>