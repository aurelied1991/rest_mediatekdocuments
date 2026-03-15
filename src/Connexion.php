<?php

/**
 * Classe de connexion à la BDD MySQL (singleton)
 * Fournit une connexion unique à la base de données et des méthodes
 * pour exécuter des requêtes SQL (SELECT, INSERT, UPDATE, DELETE).
 * Retourne pour les requêtes LID : contenu du curseur au format tableau associatif
 * Retourne pour les requêtes LMD : nbre d'enregistrements impactés
 * Dans tous les cas, 'null' est renvoyé si la requête échoue.
 */
class Connexion
{
    /**
     * Instance unique de la classe (singleton)
     * @var Connexion
     */
    private static $instance = null;
    /**
     * Objet PDO de connexion à la base
     * @var \PDO
     */
    private $conn = null;

    /**
     * Démarre une transaction sur la connexion à la bdd
     */
    public function beginTransaction()
    {
        $this->conn->beginTransaction();
    }

    /**
     * Valide la transaction en cours
     */
    public function commit()
    {
        $this->conn->commit();
    }

    /**
     * Annule la transaction en cours
     */
    public function rollback()
    {
        $this->conn->rollBack();
    }

    /**
     * Vérifie si une transaction est actuellement en cours
     * @return bool True si une transaction est active, false sinon
     */
    public function inTransaction(): bool
    {
        return $this->conn->transactionEnCours();
    }

    /**
     * Constructeur privé : initialise la connexion à la BDD
     * @param string $login Nom d'utilisateur MySQL
     * @param string $pwd Mot de passe MySQL
     * @param string $bd Nom de la base de données
     * @param string $server Adresse du serveur MySQL
     * @param string $port Port du serveur MySQL
     * @throws \Exception En cas d'erreur de connexion
     */
    private function __construct(string $login, string $pwd, string $bd, string $server, string $port)
    {
        try {
            $this->conn = new \PDO("mysql:host=$server;dbname=$bd;port=$port", $login, $pwd);
            $this->conn->query('SET CHARACTER SET utf8');
        } catch (\Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    /**
     * Méthode statique de création de l'instance unique. Retourne l'instance unique de la classe (singleton)
     * @param string $login Nom d'utilisateur MySQL
     * @param string $pwd Mot de passe MySQL
     * @param string $bd Nom de la base de données
     * @param string $server Adresse du serveur MySQL
     * @param string $port Port du serveur MySQL
     * @return Connexion instance unique de la classe
     */
    public static function getInstance(string $login, string $pwd, string $bd, string $server, string $port): Connexion
    {
        if (self::$instance === null) {
            self::$instance = new Connexion($login, $pwd, $bd, $server, $port);
        }
        return self::$instance;
    }

    /**
     * Exécute une requête de mise à jour, de modification (insert, update, delete)
     * @param string $requete Requête SQL à exécuter
     * @param array|null $param Paramètres à lier à la requête
     * @return int|null Nombre de lignes affectées, ou null si erreur
     */
    public function updateBDD(string $requete, ?array $param = null): ?int
    {
        try {
            $result = $this->prepareRequete($requete, $param);
            $reponse = $result->execute();
            if ($reponse === true) {
                return $result->rowCount();
            } else {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Exécute une requête de lecture : select, retournant 0 à plusieurs lignes
     * @param string $requete Requête SQL à exécuter
     * @param array|null $param Paramètres à lier à la requête
     * @return array|null Lignes récupérées ou null si erreur
     */
    public function queryBDD(string $requete, ?array $param = null): ?array
    {
        try {
            $result = $this->prepareRequete($requete, $param);
            $reponse = $result->execute();
            if ($reponse === true) {
                return $result->fetchAll(PDO::FETCH_ASSOC);
            } else {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Prépare une requête PDO avec liaison des paramètres
     * @param string $requete Requête SQL à préparer
     * @param array|null $param Paramètres à lier
     * @return \PDOStatement Requête PDO préparée
     * @throws \Exception En cas d'erreur de préparation
     */
    private function prepareRequete(string $requete, ?array $param = null): \PDOStatement
    {
        try {
            $requetePrepare = $this->conn->prepare($requete);
            if ($param !== null && is_array($param)) {
                foreach ($param as $key => &$value) {
                    $requetePrepare->bindParam(":$key", $value);
                }
            }
            return $requetePrepare;
        } catch (Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }
}
