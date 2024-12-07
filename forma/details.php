<?php
session_start();
include_once("includes/database.php");

// Vérification de l'ID de formation
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_formation = intval($_GET['id']);

// Récupération des détails de la formation
$stmt = $pdo->prepare("
    SELECT
        f.id_formation,
        f.libelle, 
        f.objectifs, 
        f.cout, 
        f.date_limite_inscription, 
        i.date_formation, 
        i.heureDeb,
        i.heureFin, 
        i.lieu, 
        i.nb_max_participants
    FROM Formation f
    INNER JOIN Information i ON f.id_information = i.id_information
    WHERE f.id_formation = ?
");
$stmt->execute([$id_formation]);
$formation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formation) {
    header("Location: index.php");
    exit();
}

// Récupération des intervenants
$stmt = $pdo->prepare("
    SELECT 
        CONCAT(inter.nom, ' ', inter.prenom) AS intervenant
    FROM intervenir iv
    INNER JOIN intervenant inter ON iv.id_intervenant = inter.id_intervenant
    WHERE iv.id_formation = ?
");
$stmt->execute([$id_formation]);
$intervenants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des publics visés
$stmt = $pdo->prepare("
    SELECT 
        p.libelle from public p, viser v, formation f
    where   p.id_public = v.id_public AND v.id_formation = f.id_formation
    and f.id_formation = ?
");
$stmt->execute([$id_formation]);
$publics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupération des contenus
$stmt = $pdo->prepare("
    SELECT 
        c.libelle 
    FROM Contenir ctn
    INNER JOIN Contenu c ON ctn.id_contenu = c.id_contenu
    WHERE ctn.id_formation = ?
");
$stmt->execute([$id_formation]);
$contenus = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails de la Formation</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 text-gray-800">
    <?php include_once("includes/navbar.php"); ?>

    <!-- Header Section -->
    <header class="bg-teal-600 text-white py-5 mt-8 shadow-lg">
        <div class="container mx-auto text-center">
            <h1 class="text-3xl font-bold">Détails de la Formation</h1>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto py-6 px-4">
        <a href="index.php" class="text-blue-500 hover:text-blue-700 mb-6 inline-block text-lg font-semibold">← Retour au catalogue</a>

        <!-- Formation Details -->
        <div class="bg-white p-8 shadow-lg rounded-lg">
            <h2 class="text-2xl font-bold text-teal-700 mb-4"><?= htmlspecialchars($formation['libelle']) ?></h2>

            <div class="space-y-6">
                <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow text-center">
                    <h4 class="text-xl font-semibold text-teal-600 mb-2">N° de la Formation</h4>
                    <p class="text-gray-700"><?= htmlspecialchars($formation['id_formation']) ?></p>
                </div>
                <!-- Objectifs, Coût et Autres détails -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                        <h4 class="text-xl font-semibold text-teal-600 mb-2">Objectifs</h4>
                        <p class="text-gray-700"><?= htmlspecialchars($formation['objectifs']) ?></p>
                    </div>

                    <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                        <h4 class="text-xl font-semibold text-teal-600 mb-2">Coût</h4>
                        <p class="text-gray-700"><?= htmlspecialchars($formation['cout']) ?> €</p>
                    </div>

                    <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                        <h4 class="text-xl font-semibold text-teal-600 mb-2">Date limite d'inscription</h4>
                        <p class="text-gray-700"><?= htmlspecialchars($formation['date_limite_inscription']) ?></p>
                    </div>

                    <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                        <h4 class="text-xl font-semibold text-teal-600 mb-2">Date de la formation</h4>
                        <p class="text-gray-700"><?= htmlspecialchars($formation['date_formation']) ?></p>
                    </div>

                    <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                        <h4 class="text-xl font-semibold text-teal-600 mb-2">Heure Début</h4>
                        <p class="text-gray-700"><?= htmlspecialchars($formation['heureDeb']) ?></p>
                    </div>

                    <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                        <h4 class="text-xl font-semibold text-teal-600 mb-2">Heure Fin</h4>
                        <p class="text-gray-700"><?= htmlspecialchars($formation['heureDeb']) ?></p>
                    </div>

                    <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                        <h4 class="text-xl font-semibold text-teal-600 mb-2">Lieu</h4>
                        <p class="text-gray-700"><?= htmlspecialchars($formation['lieu']) ?></p>
                    </div>

                    <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                        <h4 class="text-xl font-semibold text-teal-600 mb-2">Nombre maximum de participants</h4>
                        <p class="text-gray-700"><?= htmlspecialchars($formation['nb_max_participants']) ?></p>
                    </div>
                </div>
                
                <!-- <div class="bg-teal-50 p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow text-center">
                    <h4 class="text-xl font-semibold text-teal-600 mb-2">Nombre maximum de participants</h4>
                    <p class="text-gray-700"></p>
                </div> -->


                <!-- Intervenants Section -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-2xl font-semibold text-teal-700 mb-4">Intervenants</h3>
                    <ul class="list-inside list-disc space-y-2">
                        <?php foreach ($intervenants as $intervenant): ?>
                            <li class="text-gray-700 hover:text-teal-600 transition-colors"><?= htmlspecialchars($intervenant['intervenant']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Publics visés Section -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-2xl font-semibold text-teal-700 mb-4">Publics visés</h3>
                    <ul class="list-inside list-disc space-y-2">
                        <?php foreach ($publics as $public): ?>
                            <li class="text-gray-700 hover:text-teal-600 transition-colors"><?= htmlspecialchars($public['libelle']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contenus Section -->
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <h3 class="text-2xl font-semibold text-teal-700 mb-4">Contenus</h3>
                    <ul class="list-inside list-disc space-y-2">
                        <?php foreach ($contenus as $contenu): ?>
                            <li class="text-gray-700 hover:text-teal-600 transition-colors"><?= htmlspecialchars($contenu['libelle']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

        </div>
    </main>
</body>

</html>