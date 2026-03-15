<?php

include_once("Connexion.php");

/**
 * Classe abstraite qui sollicite ConnexionBDD pour l'accès à la BDD MySQL
 * Fournit les méthodes de base pour interagir avec la BDD MySQL via Connexion.
 * Les classes filles doivent redéfinir les méthodes abstraites pour construire
 * et exécuter les requêtes SQL spécifiques.
 */
abstract class AccessBDD
{
    /**
     * Instance de connexion à la base de données
     * @var Connexion
     */
    protected $conn = null;

    /**
     * Constructeur : initialise la connexion à la BDD
     * Récupère les informations de connexion depuis les variables d'environnement
     * et crée l'instance unique de Connexion.
     * @throws \Exception En cas d'échec de connexion
     */
    protected function __construct()
    {
        try {
            // Récupération des variables d'environnement de l'accès à la BDD
            $login = htmlspecialchars($_ENV['BDD_LOGIN'] ?? '');
            $pwd = htmlspecialchars($_ENV['BDD_PWD'] ?? '');
            $bd = htmlspecialchars($_ENV['BDD_BD'] ?? '');
            $server = htmlspecialchars($_ENV['BDD_SERVER'] ?? '');
            $port = htmlspecialchars($_ENV['BDD_PORT'] ?? '');
            // Création de la connexion à la BDD
            $this->conn = Connexion::getInstance($login, $pwd, $bd, $server, $port);
        } catch (Exception $e) {
            error_log("Erreur requête BDD : " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Traite une demande HTTP reçue par le contrôleur
     * Délègue la requête à la méthode appropriée selon la méthode HTTP.
     * @param string $methodeHTTP Méthode HTTP (GET, POST, PUT, DELETE)
     * @param string $table Nom de la table cible
     * @param string|null $id Identifiant pour la requête
     * @param array|null $champs Données envoyées pour l'insertion ou la modification
     * @return array|int|null Résultat requête(tableau associatif pr GET,
     * nombre de lignes pr POST/PUT/DELETE, null si erreur)
     */
    public function demande(string $methodeHTTP, string $table, ?string $id, ?array $champs): array|int|null
    {
        if (is_null($this->conn)) {
            return null;
        }
        switch ($methodeHTTP) {
            case 'GET' :
                return $this->traitementSelect($table, $champs);
            case 'POST' :
                return $this->traitementInsert($table, $champs);
            case 'PUT' :
                return $this->traitementUpdate($table, $id, $champs);
            case 'DELETE' :
                return $this->traitementDelete($table, $champs);
            default :
                return null;
        }
    }

    /**
     * Traite une requête SELECT
     * @param string $table Nom de la table
     * @param array|null $champs Filtres ou colonnes à récupérer
     * @return array|null Résultat du SELECT ou null si erreur
     */
    abstract protected function traitementSelect(string $table, ?array $champs): ?array;

    /**
     * Traite une requête INSERT
     * @param string $table Nom de la table
     * @param array|null $champs Données à insérer
     * @return int|null Nombre de lignes insérées ou null si erreur
     */
    abstract protected function traitementInsert(string $table, ?array $champs): ?int;

    /**
     * Traite une requête UPDATE
     * @param string $table Nom de la table
     * @param string|null $id Identifiant de la ligne à modifier
     * @param array|null $champs Données à modifier
     * @return int|null Nombre de lignes mises à jour ou null si erreur
     */
    abstract protected function traitementUpdate(string $table, ?string $id, ?array $champs): ?int;

    /**
     * Traite une requête DELETE
     * @param string $table Nom de la table
     * @param array|null $champs Critères de suppression
     * @return int|null Nombre de lignes supprimées ou null si erreur
     */
    abstract protected function traitementDelete(string $table, ?array $champs): ?int;
}
