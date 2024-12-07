function verifSaisie(){
    if (document.getElementById("email").value == "" || document.getElementById("mdp").value == ""){
        window.alert("Veuillez entrer tous les champs dans le formulaire afin de vous connecter !");
        return false;
    }
    else{
        var envoiFormulaire = confirm("Êtes vous certain de vouloir envoyer le formulaire ?");
	    if (envoiFormulaire == true)
		    return true;
	    else
		    return false;
    }
}