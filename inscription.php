<?php
session_start();

include_once("includes/database.php");

// Vérifie si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

// Empêche l'accès pour les directeurs
if ($_SESSION['type'] === 'directeur') {
    header("Location: index.php");
    exit();
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomAssociation = $_POST['nom_association'];
    $icom = $_POST['icom'];
    $nomInterlocuteur = $_POST['nom_interlocuteur'];
    $emailInterlocuteur = $_POST['email_interlocuteur'];
    $telInterlocuteur = $_POST['tel_interlocuteur'];
    $faxInterlocuteur = $_POST['fax_interlocuteur'];

    $nomStagiaire = $_POST['nom_stagiaire'];
    $adresseStagiaire = $_POST['adresse_stagiaire'];
    $codePostalStagiaire = $_POST['code_postal_stagiaire'];
    $villeStagiaire = $_POST['ville_stagiaire'];
    $emailStagiaire = $_POST['email_stagiaire'];
    $statutStagiaire = $_POST['statut_stagiaire'];
    $fonctionStagiaire = $_POST['fonction_stagiaire'];

    $formations = $_POST['formations']; // Tableau des formations demandées

    // Validation des champs (optionnel)
    if (empty($nomAssociation) || empty($nomStagiaire) || empty($formations)) {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        // Insertion des données dans la base de données
        $stmt = $pdo->prepare("INSERT INTO inscription 
            (nom_association, icom, nom_interlocuteur, email_interlocuteur, tel_interlocuteur, fax_interlocuteur, 
             nom_stagiaire, adresse_stagiaire, code_postal_stagiaire, ville_stagiaire, email_stagiaire, 
             statut_stagiaire, fonction_stagiaire, formations) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->execute([
            $nomAssociation, $icom, $nomInterlocuteur, $emailInterlocuteur, $telInterlocuteur, $faxInterlocuteur,
            $nomStagiaire, $adresseStagiaire, $codePostalStagiaire, $villeStagiaire, $emailStagiaire,
            $statutStagiaire, $fonctionStagiaire, implode(", ", $formations) // Stocke les formations en tant que chaîne
        ]);

        // Redirection vers index.php après enregistrement
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Stagiaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 text-gray-800 font-sans">
    <?php include_once 'includes/navbar.php'; ?>

    <header style="background-color: #5FB6B6;" class="text-white py-4 mt-8">
        <div class="container mx-auto text-center">
            <h1 class="text-2xl font-bold">Inscription Stagiaire</h1>
        </div>
    </header>

    <?php if (isset($error)) : ?>
        <p class="text-red-500 text-center mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <div class="bg-gray-100 shadow-md rounded-lg p-8">
        <form method="POST" action="inscription.php" class="bg-white shadow-md rounded-lg p-8">
            <!-- Coordonnées Association -->
            <fieldset class="mb-6">
                <legend class="text-lg font-semibold mb-4">Les coordonnées de votre association</legend>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Nom de l'association :</label>
                        <input type="text" name="nom_association" required class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Votre n° Icom à Uniformation :</label>
                        <input type="text" name="icom" maxlength="8" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Nom et Prénom (interlocuteur) :</label>
                        <input type="text" name="nom_interlocuteur" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Courriel :</label>
                        <input type="email" name="email_interlocuteur" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Tél :</label>
                        <input type="tel" name="tel_interlocuteur" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Fax :</label>
                        <input type="text" name="fax_interlocuteur" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                </div>
            </fieldset>

            <!-- Stagiaire -->
            <fieldset class="mb-6">
                <legend class="text-lg font-semibold mb-4">Le stagiaire</legend>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Nom et Prénom :</label>
                        <input type="text" name="nom_stagiaire" required class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Adresse :</label>
                        <input type="text" name="adresse_stagiaire" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Code Postal :</label>
                        <input type="text" name="code_postal_stagiaire" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Ville :</label>
                        <input type="text" name="ville_stagiaire" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email :</label>
                        <input type="email" name="email_stagiaire" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Statut :</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="statut_stagiaire" value="salarie" class="mr-2"> Salarié
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="statut_stagiaire" value="benevole" class="mr-2"> Bénévole
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Fonction :</label>
                        <input type="text" name="fonction_stagiaire" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                </div>
            </fieldset>

            <!-- Formations -->
            <fieldset class="mb-6">
                <legend class="text-lg font-semibold mb-4">Formations demandées</legend>
                <div class="space-y-2">
                    <label class="block text-sm font-medium">Numéros des formations (max 3) :</label>
                    <div class="flex space-x-4">
                        <input type="text" name="formations[]" placeholder="Ex : 101" class="w-full p-2 border border-gray-300 rounded-md">
                        <input type="text" name="formations[]" placeholder="Ex : 102" class="w-full p-2 border border-gray-300 rounded-md">
                        <input type="text" name="formations[]" placeholder="Ex : 103" class="w-full p-2 border border-gray-300 rounded-md">
                    </div>
                </div>
            </fieldset>

            <button type="submit" style="background-color: #5FB6B6;" onmouseover="this.style.backgroundColor='#4D9C9C'" onmouseout="this.style.backgroundColor='#5FB6B6'" class="w-full text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-300">
                Envoyer l'inscription
            </button>
        </form>
    </div>


    </div>
</body>

</html>