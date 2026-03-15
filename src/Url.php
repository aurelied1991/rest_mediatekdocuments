<?php

require '../vendor/autoload.php';

use Dotenv\Dotenv;

/**
 * Singleton car la récupération des données ne peut se faire qu'une fois
 * Permet de récupérer les variables d'environnement et les données envoyées
 * par l'URL (GET, POST, ou PUT/DELETE via php://input), ainsi que la méthode HTTP
 * utilisée et l'authentification si nécessaire.
 */
class Url
{
    /**
     * Instance unique de la classe actuelle (singleton)
     * @var Url
     */
    private static $instance = null;

    /**
     * Objet Dotenv pour accéder aux variables d'environnement
     * @var Dotenv
     */
    private $dotenv;

    /**
     * Tableau associatif contenant toutes les variables envoyées par l'URL
     * @var array
     */
    private $data = [];

    /**
     * Méthode HTTP utilisée pour la requête (GET, POST, PUT, DELETE)
     * (GET, PUT, POST, DELETE)
     * @var string
     */
    private $methodeHTTP;

    /**
     * Constructeur privé : initialise l'accès aux variables d'environnement
     * et récupère toutes les données envoyées par l'URL
     */
    private function __construct()
    {
        // variables d'environnement
        $this->dotenv = Dotenv::createImmutable(__DIR__);
        $this->dotenv->load();
        // variables envoyées par l'url
        $this->data = $this->recupAllData();
    }

    /**
     * Retourne l'instance unique de la classe (singleton)
     * @return Url Instance unique
     */
    public static function getInstance(): Url
    {
        if (self::$instance === null) {
            self::$instance = new Url();
        }
        return self::$instance;
    }

    /**
     * Récupère la méthode HTTP utilisée pour la requête
     * @return string Méthode HTTP (GET, POST, PUT, DELETE)
     */
    public function recupMethodeHTTP(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Retour la valeur d'une variable avec les caractères spéciaux convertis
     * et au format array si format "json" reçu.
     * Possibilité d'ajouter d'autres 'case' de conversions
     * @param string $nom Nom de la variable
     * @param string $format Format souhaité ("string" ou "json")
     * @return string|array|null Valeur de la variable ou null si absente
     */
    public function recupVariable(string $nom, string $format = "string"): string|array|null
    {
        $variable = $this->data[$nom] ?? '';
        switch ($format) {
            case "json" :
                $variable = $variable ? json_decode($variable, true) : null;
                break;
            default:
                break;
        }
        return $variable;
    }

    /**
     * Vérifie si la requête est authentifiée
     * Peut être étendu pour différents types d'authentification selon $_ENV['AUTHENTIFICATION']
     * @return bool true si authentifié, false sinon
     */
    public function authentification(): bool
    {
        $authentification = htmlspecialchars($_ENV['AUTHENTIFICATION'] ?? '');
        switch ($authentification) {
            case '' : return true;
            case 'basic' : return self::basicAuthentification();
            default : return true;
        }
    }

    /**
     * Authentification HTTP Basic : compare user/pwd envoyés avec ceux en environnement
     * @return bool true si identifiants corrects, false sinon
     */
    private function basicAuthentification(): bool
    {
        // récupère les variables d'environnement de l'authentification
        $expectedUser = htmlspecialchars($_ENV['AUTH_USER'] ?? '');
        $expectedPw = htmlspecialchars($_ENV['AUTH_PW'] ?? '');
        // récupère les variables envoyées en 'basic auth'
        $authUser = htmlspecialchars($_SERVER['PHP_AUTH_USER'] ?? '');
        $authPw = htmlspecialchars($_SERVER['PHP_AUTH_PW'] ?? '');
        // Contrôle si les valeurs d'authentification sont identiques
        return ($authUser === $expectedUser && $authPw === $expectedPw);
    }

    /**
     * Récupère toutes les variables envoyées par l'URL (GET, POST, PUT/DELETE)
     * Applique un nettoyage HTML (htmlspecialchars) à chaque valeur.
     * @return array Tableau associatif des variables reçues
     */
    private function recupAllData(): array
    {
        $data = [];
        if (!empty($_GET)) {
            $data = array_merge($data, $_GET);
        }
        if (!empty($_POST)) {
            $data = array_merge($data, $_POST);
        }
        $input = file_get_contents('php://input');
        parse_str($input, $postData);
        $data = array_merge($data, $postData);
        // htmlspeciachars appliqué à chaque valeur du tableau
        $data = array_map(function ($value) {
            return htmlspecialchars($value, ENT_NOQUOTES);
        }, $data);
        return $data;
    }
}
