<?php

class utilisateur
{
    private $pdo;
    private $id_stagiaire;
    private $nom;
    private $email;
    private $mdp;
    private $cp;
    private $ville;
    private $n_Icom;
    private $id_public;

    public function __construct($pdo, $id_stagiaire = null, $nom = null, $email = null, $mdp = null, $cp = null, $ville = null, $n_Icom = null, $id_public = null)
    {
        $this->pdo = $pdo; // Stocke l'instance PDO
        $this->id_stagiaire = $id_stagiaire;
        $this->nom = $nom;
        $this->email = $email;
        $this->mdp = $mdp;
        $this->cp = $cp;
        $this->ville = $ville;
        $this->n_Icom = $n_Icom;
        $this->id_public = $id_public;
    }

    public function seConnecter($login, $mdp)
    {
        // Préparer la requête pour sélectionner l'utilisateur par login
        $stmt = $this->pdo->prepare("SELECT u.*, r.nom_role AS type FROM Utilisateur u JOIN Role r ON u.id_role = r.id_role WHERE login = :login");
        $stmt->bindParam(':login', $login, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();

        // Si le login existe et que le mot de passe correspond, on est loggué et redirigé avec la page index
        if ($user) {
            if (hash("sha256", $mdp) == $user["mdp"]) {
                echo "Mot de passe vérifié avec succès !";
                return $user;
            } else {
                echo "Échec de la vérification du mot de passe.";
            }
        } else {
            echo "L'utilisateur avec ce login n'existe pas.";
        }
        return null;
    }



    public function verifierMotDePasse($mdp)
    {
        return password_verify($mdp, $this->mdp);
    }
}
