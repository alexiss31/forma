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

// Récupération des informations de l'utilisateur connecté
$stmtUser = $pdo->prepare("SELECT s.*, u.login, r.nom_role AS type FROM stagiaire s JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur JOIN role r ON u.id_role = r.id_role WHERE s.id_utilisateur = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Récupération des associations existantes
$stmtAssoc = $pdo->query("SELECT n_icom, nom_association FROM association");
$associations = $stmtAssoc->fetchAll(PDO::FETCH_ASSOC);

// Vérification du nombre d'inscriptions pour l'année en cours
$stmtCheckInscriptions = $pdo->prepare("
    SELECT COUNT(*) AS count
    FROM stagiaire_formation sf
    JOIN formation f ON sf.id_formation = f.id_formation
    WHERE sf.id_stagiaire = (SELECT id_stagiaire FROM stagiaire WHERE id_utilisateur = ?)
    AND YEAR(f.date_formation) = YEAR(CURDATE())
");
$stmtCheckInscriptions->execute([$_SESSION['user_id']]);
$inscriptionsCount = $stmtCheckInscriptions->fetch(PDO::FETCH_ASSOC);

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coordonnées de l'association
    $nomAssociation = $_POST['nom_association'] ?? null;
    $icom = $_POST['icom'] ?? null;
    $nomInterlocuteur = $_POST['nom_interlocuteur'] ?? null;
    $prenomInterlocuteur = $_POST['prenom_interlocuteur'] ?? null;
    $emailInterlocuteur = $_POST['email_interlocuteur'] ?? null;
    $telInterlocuteur = $_POST['tel_interlocuteur'] ?? null;
    $faxInterlocuteur = $_POST['fax_interlocuteur'] ?? null;

    // Coordonnées du stagiaire
    $nomStagiaire = $_POST['nom_stagiaire'] ?? null;
    $prenomStagiaire = $_POST['prenom_stagiaire'] ?? null;
    $adresseStagiaire = $_POST['adresse_stagiaire'] ?? null;
    $codePostalStagiaire = $_POST['code_postal_stagiaire'] ?? null;
    $villeStagiaire = $_POST['ville_stagiaire'] ?? null;
    $emailStagiaire = $_POST['email_stagiaire'] ?? null;
    $statutStagiaire = $_POST['statut_stagiaire'] ?? null; // Salarié ou bénévole
    $fonctionStagiaire = $_POST['fonction_stagiaire'] ?? null;

    // Séparation du nom et du prénom pour le stagiaire
    list($prenomStagiaire, $nomStagiaire) = explode(' ', $nomStagiaire, 2);

    // Séparation du nom et du prénom pour l'interlocuteur
    list($prenomInterlocuteur, $nomInterlocuteur) = explode(' ', $nomInterlocuteur, 2);

    // Formation sélectionnée
    $idFormation = $_POST['formation'] ?? null;

    // Validation des champs obligatoires
    if (empty($nomAssociation) || empty($nomStagiaire) || empty($idFormation)) {
        echo "Veuillez remplir tous les champs obligatoires.";
        exit;
    }

    // Vérification du quota d'inscriptions
    if ($inscriptionsCount['count'] >= 3) {
        $_SESSION['error_message'] = "Vous avez atteint le quota de 3 inscriptions pour l'année.";
        header("Location: inscription.php");
        exit();
    }

    // Vérification de la date limite d'inscription
    $stmtCheckDateLimit = $pdo->prepare("SELECT date_limite_inscription FROM formation WHERE id_formation = ?");
    $stmtCheckDateLimit->execute([$idFormation]);
    $dateLimit = $stmtCheckDateLimit->fetch(PDO::FETCH_ASSOC);

    if (strtotime(date('Y-m-d')) > strtotime($dateLimit['date_limite_inscription'])) {
        $_SESSION['error_message'] = "La date limite d'inscription pour cette formation est dépassée.";
        header("Location: inscription.php");
        exit();
    }

    // Vérification du nombre maximum de participants
    $stmtCheckParticipants = $pdo->prepare("SELECT COUNT(*) AS count FROM stagiaire_formation WHERE id_formation = ?");
    $stmtCheckParticipants->execute([$idFormation]);
    $participantsCount = $stmtCheckParticipants->fetch(PDO::FETCH_ASSOC);

    $stmtCheckMaxParticipants = $pdo->prepare("SELECT nb_max_participants FROM formation WHERE id_formation = ?");
    $stmtCheckMaxParticipants->execute([$idFormation]);
    $maxParticipants = $stmtCheckMaxParticipants->fetch(PDO::FETCH_ASSOC);

    if ($participantsCount['count'] >= $maxParticipants['nb_max_participants']) {
        $_SESSION['error_message'] = "Le nombre maximum de participants pour cette formation a été atteint.";
        header("Location: inscription.php");
        exit();
    }

    try {
        // Démarrage de la transaction
        $pdo->beginTransaction();
        echo "Transaction démarrée.<br>";

        // Vérification si l'association existe déjà
        $stmtCheckAssoc = $pdo->prepare("SELECT n_icom FROM association WHERE n_icom = ?");
        $stmtCheckAssoc->execute([$icom]);
        $existingAssoc = $stmtCheckAssoc->fetch();

        if (!$existingAssoc) {
            // Insertion des données dans la table association
            $stmtAssoc = $pdo->prepare("INSERT INTO association
                (n_icom, nom_association, prenom_interlocuteur, nom_interlocuteur, email_interlocuteur, tel_interlocuteur, fax_interlocuteur)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtAssoc->execute([$icom, $nomAssociation, $prenomInterlocuteur, $nomInterlocuteur, $emailInterlocuteur, $telInterlocuteur, $faxInterlocuteur]);
            echo "Association insérée.<br>";
        } else {
            echo "Association existante utilisée.<br>";
        }

        // Vérification si le stagiaire existe déjà
        $stmtCheckStagiaire = $pdo->prepare("SELECT id_stagiaire FROM stagiaire WHERE id_utilisateur = ?");
        $stmtCheckStagiaire->execute([$_SESSION['user_id']]);
        $existingStagiaire = $stmtCheckStagiaire->fetch();

        if ($existingStagiaire) {
            $idStagiaire = $existingStagiaire['id_stagiaire'];
            echo "Stagiaire existant utilisé.<br>";
        } else {
            // Insertion des données dans la table stagiaire
            $stmtStagiaire = $pdo->prepare("INSERT INTO stagiaire
                (id_utilisateur, nom, prenom, email, cp, ville, n_icom, fonction, id_public)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmtStagiaire->execute([
                $_SESSION['user_id'],
                $nomStagiaire,
                $prenomStagiaire,
                $emailStagiaire,
                $codePostalStagiaire,
                $villeStagiaire,
                $icom,
                $fonctionStagiaire,
                ($statutStagiaire === 'salarie') ? 1 : 2 // 1 pour salariés, 2 pour bénévoles
            ]);
            echo "Stagiaire inséré.<br>";

            // Récupération de l'ID du stagiaire créé
            $idStagiaire = $pdo->lastInsertId();
        }

        // Insertion de l'inscription dans la table stagiaire_formation
        $stmtInscription = $pdo->prepare("
            INSERT INTO stagiaire_formation (id_stagiaire, id_formation, date_inscription)
            VALUES (?, ?, CURDATE())
        ");
        $stmtInscription->execute([$idStagiaire, $idFormation]);
        echo "Inscription insérée.<br>";

        // Validation de la transaction
        $pdo->commit();
        echo "Transaction validée.<br>";

        // Message de succès
        $_SESSION['success_message'] = "Le stagiaire \"$nomStagiaire\" a été inscrit avec succès à la formation.";
        header("Location: index.php");
        exit();
    } catch (PDOException $e) {
        // Annulation de la transaction en cas d'erreur
        $pdo->rollBack();
        echo "Transaction annulée.<br>";
        echo "Erreur lors de l'inscription : " . $e->getMessage();
        $_SESSION['error_message'] = "Erreur lors de l'inscription : " . $e->getMessage();
        header("Location: inscription.php");
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

    <header class="bg-teal-600 text-white py-5 mt-8">
        <div class="container mx-auto text-center">
            <h1 class="text-3xl font-bold">Inscription Stagiaire</h1>
        </div>
    </header>

    <?php if (isset($error)) : ?>
        <p class="text-red-500 text-center mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])) : ?>
        <p class="text-red-500 text-center mb-4"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
        unset($_SESSION['error_message']); // Supprime le message après l'affichage
    <?php endif; ?>

    <div class="bg-gray-100 shadow-md rounded-lg p-8">
        <form method="POST" action="inscription.php" class="bg-white shadow-md rounded-lg p-8">
            <!-- Coordonnées Association -->
            <fieldset class="mb-6">
                <legend class="text-lg font-semibold mb-4">Les coordonnées de votre association</legend>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Nom de l'association :</label>
                        <select name="nom_association" id="nom_association" required class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="" disabled selected>Choisissez une association</option>
                            <?php foreach ($associations as $association): ?>
                                <option value="<?= htmlspecialchars($association['nom_association']); ?>" data-icom="<?= htmlspecialchars($association['n_icom']); ?>">
                                    <?= htmlspecialchars($association['nom_association']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Votre n° Icom à Uniformation :</label>
                        <input type="text" name="icom" id="icom" readonly class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['n_icom'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Nom et Prénom (interlocuteur) :</label>
                        <input type="text" name="nom_interlocuteur" class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['nom_interlocuteur'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Courriel :</label>
                        <input type="email" name="email_interlocuteur" class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['email_interlocuteur'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Tél :</label>
                        <input type="tel" name="tel_interlocuteur" maxlength="10" class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['tel_interlocuteur'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Fax :</label>
                        <input type="text" name="fax_interlocuteur" maxlength="10" class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['fax_interlocuteur'] ?? '') ?>">
                    </div>
                </div>
            </fieldset>

            <!-- Stagiaire -->
            <fieldset class="mb-6">
                <legend class="text-lg font-semibold mb-4">Le stagiaire</legend>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Nom et Prénom :</label>
                        <input type="text" name="nom_stagiaire" required class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['nom'] ?? '') . ' ' . htmlspecialchars($user['prenom'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Adresse :</label>
                        <input type="text" name="adresse_stagiaire" class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['adresse'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Code Postal :</label>
                        <input type="text" name="code_postal_stagiaire" class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['cp'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Ville :</label>
                        <input type="text" name="ville_stagiaire" class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['ville'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email :</label>
                        <input type="email" name="email_stagiaire" class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Statut :</label>
                        <div class="flex space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="statut_stagiaire" value="salarie" class="mr-2" <?= ($user['type'] === 'salarie') ? 'checked' : '' ?>> Salarié
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="statut_stagiaire" value="benevole" class="mr-2" <?= ($user['type'] === 'benevole') ? 'checked' : '' ?>> Bénévole
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Fonction :</label>
                        <input type="text" name="fonction_stagiaire" class="w-full p-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($user['fonction'] ?? '') ?>">
                    </div>
                </div>
            </fieldset>

            <?php
            // Connexion à la base de données (à adapter selon vos paramètres)
            try {
                // Récupération des formations
                $stmtFormations = $pdo->query("SELECT id_formation, libelle FROM formation");
                $formations = $stmtFormations->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo "Erreur de connexion : " . $e->getMessage();
                exit();
            }
            ?>

            <!-- Formations -->
            <fieldset class="mb-6">
                <legend class="text-lg font-semibold mb-4">Formation demandée</legend>
                <div class="space-y-2">
                    <label class="block text-sm font-medium">Sélectionnez la formation à laquelle vous souhaitez vous inscrire :</label>
                    <div class="flex items-center">
                        <select name="formation" class="w-full p-2 border border-gray-300 rounded-md">
                            <option value="" disabled selected>Choisissez une formation</option>
                            <?php foreach ($formations as $formation): ?>
                                <option value="<?= htmlspecialchars($formation['id_formation']); ?>">
                                    <?= htmlspecialchars($formation['libelle']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </fieldset>

            <button type="submit" onmouseover="this.style.backgroundColor='#0f766e'" onmouseout="this.style.backgroundColor='#0D9488'" class="bg-teal-600 w-full text-white py-2 px-4 rounded-md hover:bg-blue-700 transition duration-300">
                Envoyer l'inscription
            </button>
        </form>
    </div>

    <script>
        document.getElementById('nom_association').addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            var icom = selectedOption.getAttribute('data-icom');
            document.getElementById('icom').value = icom;
        });
    </script>
</body>

</html>
