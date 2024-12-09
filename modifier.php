<?php
session_save_path('sessions');
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

// Récupération des informations de la formation
$stmt = $pdo->prepare("
    SELECT 
        f.libelle, 
        i.date_formation AS date, 
        i.heureDeb, 
        i.heureFin, 
        i.lieu, 
        i.nb_max_participants,
        GROUP_CONCAT(DISTINCT inter.nom SEPARATOR ', ') AS intervenants, 
        GROUP_CONCAT(DISTINCT p.libelle SEPARATOR ', ') AS public_vise, 
        f.objectifs, 
        GROUP_CONCAT(DISTINCT c.libelle SEPARATOR ', ') AS contenu, 
        f.cout, 
        f.date_limite_inscription, 
        i.nb_max_participants
    FROM Formation f
    JOIN Information i ON f.id_information = i.id_information
    LEFT JOIN intervenir inte ON f.id_formation = inte.id_formation
    LEFT JOIN intervenant inter ON inte.id_intervenant = inter.id_intervenant
    LEFT JOIN viser vi ON f.id_formation = vi.id_formation
    LEFT JOIN public p ON vi.id_public = p.id_public
    LEFT JOIN contenir con ON f.id_formation = con.id_formation
    LEFT JOIN contenu c ON con.id_contenu = c.id_contenu
    WHERE f.id_formation = ?
    GROUP BY f.id_formation
");
$stmt->execute([$idFormation]);
$formation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formation) {
    echo "Formation non trouvée.";
    exit();
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = trim($_POST['libelle']);
    $date = trim($_POST['date']);
    $heureDeb = trim($_POST['heureDeb']);
    $heureFin = trim($_POST['heureFin']);
    $lieu = trim($_POST['lieu']);
    $intervenants = trim($_POST['intervenants']);
    $public_vise = trim($_POST['public_vise']);
    $objectifs = trim($_POST['objectifs']);
    $contenu = trim($_POST['contenu']);
    $cout = trim($_POST['cout']);
    $date_limite_inscription = trim($_POST['date_limite_inscription']);
    $nb_max_participants = trim($_POST['nb_max_participants']);

    // Validation des champs
    if (
        empty($libelle) || empty($date) || empty($heureDeb) || empty($heureFin) ||
        empty($lieu) || empty($intervenants) || empty($public_vise) ||
        empty($objectifs) || empty($contenu) || empty($cout) ||
        empty($date_limite_inscription)
    ) {
        $error = "Tous les champs sont obligatoires.";
    } else {
        try {
            // Début de la transaction
            $pdo->beginTransaction();

            // Mise à jour de la table `information`
            $stmtInformation = $pdo->prepare("
                UPDATE Information 
                SET date_formation = ?, heureDeb = ?, heureFin = ?, lieu = ?, nb_max_participants = ?
                WHERE id_information = (
                    SELECT id_information FROM Formation WHERE id_formation = ?
                )
            ");
            $stmtInformation->execute([$date, $heureDeb, $heureFin, $lieu, $nb_max_participants, $idFormation]);

            // Mise à jour de la table `formation`
            $stmtFormation = $pdo->prepare("
                UPDATE Formation 
                SET libelle = ?, objectifs = ?, cout = ?, date_limite_inscription = ?
                WHERE id_formation = ?
            ");
            $stmtFormation->execute([$libelle, $objectifs, $cout, $date_limite_inscription, $idFormation]);

            // Commit de la transaction
            $pdo->commit();

            // Redirection avec un message de succès
            $_SESSION['update_success_message'] = "La formation a été mise à jour avec succès.";
            header("Location: gerer.php");
            exit();
        } catch (Exception $e) {
            // En cas d'erreur, annuler la transaction
            $pdo->rollBack();
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
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
    <header class="bg-teal-600 text-white py-5 mt-8">
        <div class="container mx-auto text-center">
            <h1 class="text-2xl font-bold">Modifier une Formation</h1>
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
                <label for="date" class="block text-sm font-medium">Date :</label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($formation['date']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div>
                <label for="heureDeb" class="block text-sm font-medium">Heure début :</label>
                <input type="time" id="heureDeb" name="heureDeb" value="<?= htmlspecialchars($formation['heureDeb']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div>
                <label for="heureFin" class="block text-sm font-medium">Heure fin :</label>
                <input type="time" id="heureFin" name="heureFin" value="<?= htmlspecialchars($formation['heureFin']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div>
                <label for="lieu" class="block text-sm font-medium">Lieu :</label>
                <input type="text" id="lieu" name="lieu" value="<?= htmlspecialchars($formation['lieu']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div>
                <label for="intervenants" class="block text-sm font-medium">Intervenants :</label>
                <textarea id="intervenants" name="intervenants" rows="2"
                    class="w-full p-2 border border-gray-300 rounded-md" required><?= htmlspecialchars($formation['intervenants']) ?></textarea>
            </div>

            <div>
                <label for="public_vise" class="block text-sm font-medium">Public visé :</label>
                <textarea id="public_vise" name="public_vise" rows="2"
                    class="w-full p-2 border border-gray-300 rounded-md" required><?= htmlspecialchars($formation['public_vise']) ?></textarea>
            </div>

            <div>
                <label for="objectifs" class="block text-sm font-medium">Objectifs :</label>
                <textarea id="objectifs" name="objectifs" rows="2"
                    class="w-full p-2 border border-gray-300 rounded-md" required><?= htmlspecialchars($formation['objectifs']) ?></textarea>
            </div>

            <div>
                <label for="contenu" class="block text-sm font-medium">Contenu :</label>
                <textarea id="contenu" name="contenu" rows="4"
                    class="w-full p-2 border border-gray-300 rounded-md" required><?= htmlspecialchars($formation['contenu']) ?></textarea>
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
                <label for="nb_max_participants" class="block text-sm font-medium">Nombre de participants maximum:</label>
                <input type="nb_max_participants" id="nb_max_participants" name="nb_max_participants"
                    value="<?= htmlspecialchars($formation['nb_max_participants']) ?>"
                    class="w-full p-2 border border-gray-300 rounded-md" required>
            </div>

            <div class="flex justify-between">
                <button type="submit" class="bg-green-500 text-white py-2 px-4 rounded-md hover:bg-green-700 transition">
                    Modifier
                </button>
                <a href="gerer.php" class="bg-red-500 text-white py-2 px-4 rounded-md hover:bg-red-700 transition">
                    Annuler
                </a>
            </div>
        </form>
    </div>
</body>

</html>