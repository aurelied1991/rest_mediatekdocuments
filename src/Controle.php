<?php

header('Content-Type: application/json');

include_once("MyAccessBDD.php");

/**
 * Contrôleur principal de l'API : reçoit et traite les demandes du point d'entrée
 * Reçoit et traite les requêtes HTTP entrantes, interagit avec la base de données
 * via MyAccessBDD et renvoie les réponses au client au format JSON.
 */
class Controle
{
    /**
     * Instance de la classe d'accès à la base de données
     * @var MyAccessBDD
     */
    private $myAaccessBDD;

    /**
     * Constructeur : initialise l'accès à la base de données
     */
    public function __construct()
    {
        try {
            $this->myAaccessBDD = new MyAccessBDD();
        } catch (Exception $e) {
            $this->reponse(500, "erreur serveur");
            die();
        }
    }

    /**
     * Réception d'une demande de requête
     * Demande de traiter la requête puis demande d'afficher la réponse
     * @param string $methodeHTTP La méthode HTTP de la requête (GET, POST, PUT, DELETE)
     * @param string $table Nom de la table cible dans la base de données
     * @param string|null $id Identifiant optionnel pour la requête
     * @param array|null $champs Données optionnelles à insérer ou modifier
     */
    public function demande(string $methodeHTTP, string $table, ?string $id, ?array $champs)
    {
        $result = $this->myAaccessBDD->demande($methodeHTTP, $table, $id, $champs);
        $this->controleResult($result);
    }

    /**
     * Envoie la réponse JSON au client
     * @param int $code Code standard HTTP (200, 500, ...)
     * @param string $message Message correspondant au code
     * @param array|int|string|null $result Données à renvoyer
     */
    private function reponse(int $code, string $message, array|int|string|null $result = "")
    {
        $retour = array(
            'code' => $code,
            'message' => $message,
            'result' => $result
        );
        echo json_encode($retour, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Vérifie le résultat de la requête et renvoie la réponse appropriée
     * @param array|int|null $result résultat de la requête
     */
    private function controleResult(array|int|null $result)
    {
        if (!is_null($result)) {
            $this->reponse(200, "OK", $result);
        } else {
            $this->reponse(400, "requete invalide");
        }
    }

    /**
     * Renvoie une réponse d'authentification incorrecte.
     * Utilisé lorsque les identifiants fournis sont invalides.
     */
    public function unauthorized()
    {
        $this->reponse(401, "authentification incorrecte");
    }
}
