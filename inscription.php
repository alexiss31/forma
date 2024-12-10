<?php
session_save_path('sessions');
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
$stmtUser = $pdo->prepare("SELECT s.*, u.login, r.nom_role AS type, a.*
                            FROM stagiaire s
                            JOIN utilisateur u ON s.id_utilisateur = u.id_utilisateur
                            JOIN role r ON u.id_role = r.id_role
                            JOIN association a ON s.n_icom = a.n_icom
                            WHERE s.id_utilisateur = ?");
$stmtUser->execute([$_SESSION['user_id']]);
$user = $stmtUser->fetch(PDO::FETCH_ASSOC);

// Stocker le n_icom dans une variable de session
$_SESSION['n_icom'] = $user['n_icom'] ?? null;

// Récupération des associations existantes
$stmtAssoc = $pdo->query("SELECT n_icom, nom_association, prenom_interlocuteur, nom_interlocuteur, email_interlocuteur, tel_interlocuteur, fax_interlocuteur FROM association");
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
    // Vérification du quota d'inscriptions
    if ($inscriptionsCount['count'] >= 3) {
        $_SESSION['error_message'] = "Vous avez atteint le quota de 3 inscriptions pour l'année.";
        header("Location: inscription.php");
        exit();
    }

    // Formation sélectionnée
    $idFormation = $_POST['formation'] ?? null;

    // Vérification de la date limite d'inscription
    $stmtCheckDateLimit = $pdo->prepare("SELECT date_limite_inscription FROM formation WHERE id_formation = ?");
    $stmtCheckDateLimit->execute([$idFormation]);
    $dateLimit = $stmtCheckDateLimit->fetch(PDO::FETCH_ASSOC);

    if (strtotime(date('Y-m-d')) > strtotime($dateLimit['date_limite_inscription'])) {
        $_SESSION['error_message'] = "La date limite d'inscription pour cette formation est dépassée.";
        header("Location: inscription.php");
        exit();
    }

    try {
        // Démarage de la transaction
        $pdo->beginTransaction();
        echo "Transaction démarrée.<br>";

        // Insertion de l'inscription dans la table stagiaire_formation
        $stmtInscription = $pdo->prepare("
            INSERT INTO stagiaire_formation (id_stagiaire, id_formation, date_inscription)
            VALUES (?, ?, CURDATE())
        ");
        $stmtInscription->execute([$user['id_stagiaire'], $idFormation]);
        echo "Inscription insérée.<br>";

        // Validation de la transaction
        $pdo->commit();
        echo "Transaction validée.<br>";

        // Message de succès
        $_SESSION['success_message'] = "Vous avez été inscrit avec succès à la formation.";
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

    // Traitement spécifique pour l'admin
    if ($_SESSION['type'] === 'admin') {
        // Récupérer les valeurs du formulaire
        $nomAssociation = $_POST['nom_association'];
        $icom = $_POST['icom'];
        $nomInterlocuteur = $_POST['nom_interlocuteur'];
        $emailInterlocuteur = $_POST['email_interlocuteur'];
        $telInterlocuteur = $_POST['tel_interlocuteur'];
        $faxInterlocuteur = $_POST['fax_interlocuteur'];
        $nomStagiaire = $_POST['nom_stagiaire'];
        $codePostalStagiaire = $_POST['code_postal_stagiaire'];
        $villeStagiaire = $_POST['ville_stagiaire'];
        $emailStagiaire = $_POST['email_stagiaire'];
        $statutStagiaire = $_POST['statut_stagiaire'];
        $fonctionStagiaire = $_POST['fonction_stagiaire'];

        try {
            // Démarage de la transaction
            $pdo->beginTransaction();
            echo "Transaction démarrée.<br>";

            // Insertion dans la table association (si l'association n'existe pas)
            $stmtAssocInsert = $pdo->prepare("
                INSERT INTO association (nom_association, prenom_interlocuteur, nom_interlocuteur, email_interlocuteur, tel_interlocuteur, fax_interlocuteur)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmtAssocInsert->execute([$nomAssociation, $nomInterlocuteur, $emailInterlocuteur, $telInterlocuteur, $faxInterlocuteur]);

            // Insertion dans la table stagiaire
            $stmtStagiaireInsert = $pdo->prepare("
                INSERT INTO stagiaire (nom, prenom, cp, ville, email, statut, fonction, n_icom)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtStagiaireInsert->execute([$nomStagiaire, $codePostalStagiaire, $villeStagiaire, $emailStagiaire, $statutStagiaire, $fonctionStagiaire, $icom]);

            // Validation de la transaction
            $pdo->commit();
            echo "Transaction validée.<br>";

            // Message de succès
            $_SESSION['success_message'] = "Les informations ont été ajoutées avec succès.";
            header("Location: index.php");
            exit();
        } catch (PDOException $e) {
            // Annulation de la transaction en cas d'erreur
            $pdo->rollBack();
            echo "Transaction annulée.<br>";
            echo "Erreur lors de l'insertion : " . $e->getMessage();
            $_SESSION['error_message'] = "Erreur lors de l'insertion : " . $e->getMessage();
            header("Location: inscription.php");
            exit();
        }
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

    <?php if (isset($error)): ?>
        <p class="text-red-500 text-center mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <p class="text-red-500 text-center mb-4"><?= htmlspecialchars($_SESSION['error_message']) ?></p>
    <?php
        unset($_SESSION['error_message']); // Supprime le message après l'affichage
    endif; ?>

    <div class="bg-gray-100 shadow-md rounded-lg p-8">
        <form method="POST" action="inscription.php" class="bg-white shadow-md rounded-lg p-8">
            <!-- Coordonnées Association -->
            <fieldset class="mb-6">
                <legend class="text-lg font-semibold mb-4">Les coordonnées de votre association</legend>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Nom de l'association :</label>
                        <select name="nom_association" id="nom_association" required class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                            <option value="<?= htmlspecialchars($user['nom_association']) ?>" selected>
                                <?= htmlspecialchars($user['nom_association']) ?>
                            </option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Nom Interlocuteur :</label>
                        <input type="text" name="nom_interlocuteur" id="nom_interlocuteur" value="<?= htmlspecialchars($user['nom_interlocuteur']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email Interlocuteur :</label>
                        <input type="email" name="email_interlocuteur" id="email_interlocuteur" value="<?= htmlspecialchars($user['email_interlocuteur']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Téléphone :</label>
                        <input type="tel" name="tel_interlocuteur" id="tel_interlocuteur" value="<?= htmlspecialchars($user['tel_interlocuteur']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Fax :</label>
                        <input type="tel" name="fax_interlocuteur" id="fax_interlocuteur" value="<?= htmlspecialchars($user['fax_interlocuteur']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                    </div>
                </div>
            </fieldset>

            <!-- Stagiaire Infos -->
            <fieldset class="mb-6">
                <legend class="text-lg font-semibold mb-4">Les informations du stagiaire</legend>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium">Nom du stagiaire :</label>
                        <input type="text" name="nom_stagiaire" id="nom_stagiaire" value="<?= htmlspecialchars($user['nom']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Code Postal :</label>
                        <input type="text" name="code_postal_stagiaire" id="code_postal_stagiaire" value="<?= htmlspecialchars($user['cp']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Ville :</label>
                        <input type="text" name="ville_stagiaire" id="ville_stagiaire" value="<?= htmlspecialchars($user['ville']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Email :</label>
                        <input type="email" name="email_stagiaire" id="email_stagiaire" value="<?= htmlspecialchars($user['email']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Statut :</label>
                        <input type="text" name="statut_stagiaire" id="statut_stagiaire" value="<?= htmlspecialchars($user['type']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
                    </div>
                    <div>
                        <label class="block text-sm font-medium">Fonction :</label>
                        <input type="text" name="fonction_stagiaire" id="fonction_stagiaire" value="<?= htmlspecialchars($user['fonction']) ?>" class="w-full p-2 border border-gray-300 rounded-md" <?= ($_SESSION['type'] === 'admin') ? '' : 'disabled' ?>>
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
                                <option value="<?= htmlspecialchars($formation['id_formation']) ?>">
                                    <?= htmlspecialchars($formation['libelle']) ?>
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
</body>

</html>