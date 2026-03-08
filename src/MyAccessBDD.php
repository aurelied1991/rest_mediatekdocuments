<?php

include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD
{
    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */
    protected function traitementSelect(string $table, ?array $champs): ?array
    {
        switch ($table) {
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "commandedocument" :
                return $this->selectDocumentCommande($champs);
            case "abonnement" :
                return $this->selectCommandesRevue($champs);
            case "abonnementfinissant" :
                return $this->selectAbonnementsFinissant($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "" :
            // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */
    protected function traitementInsert(string $table, ?array $champs): ?int
    {
        switch ($table) {
            case "" :
            // return $this->uneFonction(parametres);
            case "livre" :
                return $this->insertLivre($champs);
            case "dvd" :
                return $this->insertDvd($champs);
            case "revue" :
                return $this->insertRevue($champs);
            case "commandedocument" :
                return $this->insertCommandeDocument($champs);
            case "abonnement" :
                return $this->insertAbonnementRevue($champs);
            default:
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);
        }
    }

    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */
    protected function traitementUpdate(string $table, ?string $id, ?array $champs): ?int
    {
        switch ($table) {
            case "" :
            // return $this->uneFonction(parametres);
            case "livre" :
                return $this->updateLivre($id, $champs);
            case "dvd" :
                return $this->updateDvd($id, $champs);
            case "revue" :
                return $this->updateRevue($id, $champs);
            case "commandedocument" :
                return $this->updateCommandeDocument($id, $champs);
            default:
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }
    }

    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */
    protected function traitementDelete(string $table, ?array $champs): ?int
    {
        switch ($table) {
            case "" :
            // return $this->uneFonction(parametres);
            case "livre":
                return $this->deleteLivre($champs);
            case "dvd":
                return $this->deleteDvd($champs);
            case "revue":
                return $this->deleteRevue($champs);
            case "commandedocument":
                return $this->deleteCommandeDocument($champs);
            case "abonnement":
                return $this->deleteAbonnementRevue($champs);
            default:
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);
        }
    }

    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null
     */
    private function selectTuplesOneTable(string $table, ?array $champs): ?array
    {
        if (empty($champs)) {
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);
        } else {
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value) {
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete) - 5);
            return $this->conn->queryBDD($requete, $champs);
        }
    }

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function insertOneTupleOneTable(string $table, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value) {
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ") values (";
        foreach ($champs as $key => $value) {
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        if (is_null($id)) {
            return null;
        }
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $champs["id"] = $id;
        $requete .= " where id=:id;";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete) - 5);
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table): ?array
    {
        $requete = "select * from $table order by libelle;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres(): ?array
    {
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd(): ?array
    {
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues(): ?array
    {
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Insère un nouveau livre dans la bdd en respectant l'héritage des tables
     * Opération réalisée dans une transaction pour la sécurité des données
     * @param array|null $champs Données du livre
     * @return int|null 1 si succès, null si non
     */
    private function insertLivre(?array $champs): ?int
    {
        //Vérifier que les données sont présentes
        if (empty($champs)) {
            return null;
        }

        // Normalisation des clés en minisucules pour éviter les problèmes de casse
        $champs = array_change_key_case($champs, CASE_LOWER);

        // Contrôle des champs obligatoires
        if (
                empty($champs["id"]) ||
                empty($champs["titre"]) ||
                empty($champs["idgenre"]) ||
                empty($champs["idpublic"]) ||
                empty($champs["idrayon"])
        ) {
            return null;
        }

        try {
            if ($this->conn === null) {
                return null;
            }
            $this->conn->beginTransaction();
            $verif = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM document WHERE id = :id",
                ["id" => $champs["id"]]
            );

            if (!empty($verif) && $verif[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }
            // Découpages des données par niveau d'héritage
            // Données communes à tous les documents
            $champsDocument = [
                "id" => $champs["id"],
                "titre" => $champs["titre"] ?? "",
                "image" => $champs["image"] ?? "",
                "idGenre" => $champs["idgenre"],
                "idPublic" => $champs["idpublic"],
                "idRayon" => $champs["idrayon"]
            ];

            // Table intermédiaire liés à l'héritage (livres_dvd)
            $champsLivreDvd = [
                "id" => $champs["id"]
            ];

            // Données propres aux livres
            $champsLivre = [
                "id" => $champs["id"],
                "ISBN" => $champs["isbn"] ?? "",
                "auteur" => $champs["auteur"] ?? "",
                "collection" => $champs["collection"] ?? ""
            ];

            // Insertion dans chaque table concernée
            $reqDoc = $this->insertOneTupleOneTable("document", $champsDocument);
            $reqLivreDvd = $this->insertOneTupleOneTable("livres_dvd", $champsLivreDvd);
            $reqLivre = $this->insertOneTupleOneTable("livre", $champsLivre);

            // Vérification des inserts
            if (
                    empty($reqDoc) ||
                    empty($reqLivreDvd) ||
                    empty($reqLivre)
            ) {
                $this->conn->rollback();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            // En cas d'exception, annulation de la transaction
            $this->conn->rollBack();
        }
        return null;
    }

    /**
     * Insère un nouveau dvd dans la bdd en respectant l'héritage des tables
     * Opération réalisée dans une transaction pour la sécurité des données
     * @param array|null $champs
     * @return int|null
     */
    private function insertDvd(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);
        // Contrôle des champs obligatoires
        if (
                empty($champs["id"]) ||
                empty($champs["titre"]) ||
                empty($champs["synopsis"]) ||
                empty($champs["realisateur"]) ||
                empty($champs["duree"]) ||
                empty($champs["idgenre"]) ||
                empty($champs["idpublic"]) ||
                empty($champs["idrayon"])
        ) {
            return null;
        }
        try {
            if ($this->conn === null) {
                return null;
            }
            $this->conn->beginTransaction();
            $verif = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM document WHERE id = :id",
                ["id" => $champs["id"]]
            );
            if (!empty($verif) && $verif[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }
            // Découpages des données par niveau d'héritage
            // Données communes à tous les documents
            $champsDocument = [
                "id" => $champs["id"],
                "titre" => $champs["titre"] ?? "",
                "image" => $champs["image"] ?? "",
                "idGenre" => $champs["idgenre"],
                "idPublic" => $champs["idpublic"],
                "idRayon" => $champs["idrayon"]
            ];

            // Table intermédiaire liés à l'héritage (livres_dvd)
            $champsLivreDvd = [
                "id" => $champs["id"]
            ];

            $champsDvd = [
                "id" => $champs["id"],
                "synopsis" => $champs["synopsis"] ?? "",
                "realisateur" => $champs["realisateur"] ?? "",
                "duree" => $champs["duree"] ?? ""
            ];
            // Insertion dans chaque table concernée
            $reqDoc = $this->insertOneTupleOneTable("document", $champsDocument);
            $reqLivreDvd = $this->insertOneTupleOneTable("livres_dvd", $champsLivreDvd);
            $reqDvd = $this->insertOneTupleOneTable("dvd", $champsDvd);
            // Vérification des inserts
            if (
                    empty($reqDoc) ||
                    empty($reqLivreDvd) ||
                    empty($reqDvd)
            ) {
                $this->conn->rollback();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            // En cas d'exception, annulation de la transaction
            $this->conn->rollBack();
        }
        return null;
    }

    /**
     * Insère une nouvelle revue dans la bdd en respectant l'héritage des tables
     * Opération réalisée dans une transaction pour la sécurité des données
     * @param array|null $champs
     * @return int|null
     */
    private function insertRevue(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);
        // Contrôle des champs obligatoires
        if (
                empty($champs["id"]) ||
                empty($champs["titre"]) ||
                empty($champs["periodicite"]) ||
                empty($champs["delaimiseadispo"]) ||
                empty($champs["idgenre"]) ||
                empty($champs["idpublic"]) ||
                empty($champs["idrayon"])
        ) {
            return null;
        }
        try {
            if ($this->conn === null) {
                return null;
            }
            $this->conn->beginTransaction();
            $verif = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM document WHERE id = :id",
                ["id" => $champs["id"]]
            );
            if (!empty($verif) && $verif[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }
            // Découpages des données par niveau d'héritage
            // Données communes à tous les documents
            $champsDocument = [
                "id" => $champs["id"],
                "titre" => $champs["titre"] ?? "",
                "image" => $champs["image"] ?? "",
                "idGenre" => $champs["idgenre"],
                "idPublic" => $champs["idpublic"],
                "idRayon" => $champs["idrayon"]
            ];

            $champsRevue = [
                "id" => $champs["id"],
                "periodicite" => $champs["periodicite"] ?? "",
                "delaimiseadispo" => $champs["delaimiseadispo"] ?? "",
            ];
            // Insertion dans chaque table concernée
            $reqDoc = $this->insertOneTupleOneTable("document", $champsDocument);
            $reqRevue = $this->insertOneTupleOneTable("revue", $champsRevue);
            // Vérification des inserts
            if (
                    empty($reqDoc) ||
                    empty($reqRevue)
            ) {
                $this->conn->rollback();
                return null;
            }
            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            // En cas d'exception, annulation de la transaction
            $this->conn->rollBack();
        }
        return null;
    }

    /**
     * Met à jour un livre existant dans la bdd
     * @param string|null $id
     * @param array|null $champs
     * @return int|null
     */
    private function updateLivre(?string $id, ?array $champs): ?int
    {
        if (empty($champs) || empty($id)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);
        if (
                empty($champs["id"]) ||
                empty($champs["titre"]) ||
                empty($champs["idgenre"]) ||
                empty($champs["idpublic"]) ||
                empty($champs["idrayon"])
        ) {
            return null;
        }
        try {
            if ($this->conn === null) {
                return null;
            }
            // Vérifier que le document existe
            $verif = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM document WHERE id = :id",
                ["id" => $id]
            );

            if (empty($verif) || $verif[0]["nb"] == 0) {
                return null;
            }

            $this->conn->beginTransaction();

            $champsDocument = [
                "titre" => $champs["titre"],
                "image" => $champs["image"] ?? "",
                "idGenre" => $champs["idgenre"],
                "idPublic" => $champs["idpublic"],
                "idRayon" => $champs["idrayon"]
            ];
            $champsLivre = [
                "isbn" => $champs["isbn"] ?? "",
                "auteur" => $champs["auteur"] ?? "",
                "collection" => $champs["collection"] ?? ""
            ];
            $reqDoc = $this->updateOneTupleOneTable("document", $id, $champsDocument);
            $reqLivre = $this->updateOneTupleOneTable("livre", $id, $champsLivre);
            if ($reqDoc === null || $reqLivre === null) {
                $this->conn->rollback();
                return null;
            }
            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Met à jour un Dvd dans la bdd
     * @param string|null $id
     * @param array|null $champs
     * @return int|null
     */
    private function updateDvd(?string $id, ?array $champs): ?int
    {
        if (empty($champs) || empty($id)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);
        if (
                empty($champs["id"]) ||
                empty($champs["titre"]) ||
                empty($champs["synopsis"]) ||
                empty($champs["realisateur"]) ||
                empty($champs["duree"]) ||
                empty($champs["idgenre"]) ||
                empty($champs["idpublic"]) ||
                empty($champs["idrayon"])
        ) {
            return null;
        }
        try {

            if ($this->conn === null) {
                return null;
            }
            // Vérifier que le document existe
            $verif = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM document WHERE id = :id",
                ["id" => $id]
            );
            if (empty($verif) || $verif[0]["nb"] == 0) {
                return null;
            }
            $this->conn->beginTransaction();

            $champsDocument = [
                "titre" => $champs["titre"],
                "image" => $champs["image"] ?? "",
                "idGenre" => $champs["idgenre"],
                "idPublic" => $champs["idpublic"],
                "idRayon" => $champs["idrayon"]
            ];

            $champsDvd = [
                "synopsis" => $champs["synopsis"] ?? "",
                "realisateur" => $champs["realisateur"] ?? "",
                "duree" => $champs["duree"] ?? ""
            ];

            $reqDoc = $this->updateOneTupleOneTable("document", $id, $champsDocument);
            $reqDvd = $this->updateOneTupleOneTable("dvd", $id, $champsDvd);

            if ($reqDoc === null || $reqDvd === null) {
                $this->conn->rollback();
                return null;
            }
            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Met à jour une revue dans la bdd
     * @param string|null $id
     * @param array|null $champs
     * @return int|null
     */
    private function updateRevue(?string $id, ?array $champs): ?int
    {
        //Vérifier que les données sont présentes
        if (empty($champs) || empty($id)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);
        // Contrôle des champs obligatoires
        if (
                empty($champs["id"]) ||
                empty($champs["titre"]) ||
                empty($champs["periodicite"]) ||
                empty($champs["delaimiseadispo"]) ||
                empty($champs["idgenre"]) ||
                empty($champs["idpublic"]) ||
                empty($champs["idrayon"])
        ) {
            return null;
        }
        try {

            if ($this->conn === null) {
                return null;
            }
            // Vérifier que le document existe
            $verif = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM document WHERE id = :id",
                ["id" => $id]
            );
            if (empty($verif) || $verif[0]["nb"] == 0) {
                return null;
            }
            $this->conn->beginTransaction();

            // Données table document (SANS id)
            $champsDocument = [
                "titre" => $champs["titre"],
                "image" => $champs["image"] ?? "",
                "idGenre" => $champs["idgenre"],
                "idPublic" => $champs["idpublic"],
                "idRayon" => $champs["idrayon"]
            ];
            // Données table livre
            $champsRevue = [
                "periodicite" => $champs["periodicite"] ?? "",
                "delaimiseadispo" => $champs["delaimiseadispo"] ?? "",
            ];
            // Updates
            $reqDoc = $this->updateOneTupleOneTable("document", $id, $champsDocument);
            $reqRevue = $this->updateOneTupleOneTable("revue", $id, $champsRevue);
            // Vérification
            if ($reqDoc === null || $reqRevue === null) {
                $this->conn->rollback();
                return null;
            }
            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Supprime un livre de la bdd s'il n'y a aucune dépendance (exemplaire ou commande)
     * @param array|null $champs
     * @return int|null
     */
    private function deleteLivre(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);
        if (!isset($champs["id"])) {
            return null;
        }
        $id = $champs["id"];
        try {
            if ($this->conn === null) {
                return null;
            }

            $this->conn->beginTransaction();
            // Vérifier exemplaires
            $verifExemplaires = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM exemplaire WHERE id = :id",
                ["id" => $id]
            );

            if (!empty($verifExemplaires) && $verifExemplaires[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }

            // Vérifier commandes
            $verifCommandes = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM commande WHERE id = :id",
                ["id" => $id]
            );

            if (!empty($verifCommandes) && $verifCommandes[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }

            // Suppression dans le bon ordre
            $req1 = $this->deleteTuplesOneTable("livre", ["id" => $id]);
            $req2 = $this->deleteTuplesOneTable("livres_dvd", ["id" => $id]);
            $req3 = $this->deleteTuplesOneTable("document", ["id" => $id]);

            if ($req1 === null || $req2 === null || $req3 === null) {
                $this->conn->rollback();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Supprime un dvd de la bdd s'il n'y a aucune dépendance (exemplaire ou commande)
     * @param array|null $champs
     * @return int|null
     */
    private function deleteDvd(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);
        if (!isset($champs["id"])) {
            return null;
        }

        $id = $champs["id"];

        try {
            if ($this->conn === null) {
                return null;
            }
            $this->conn->beginTransaction();
            $verifEx = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM exemplaire WHERE id = :id",
                ["id" => $id]
            );
            if (!empty($verifEx) && $verifEx[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }

            $verifCom = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM commande WHERE id = :id",
                ["id" => $id]
            );
            if (!empty($verifCom) && $verifCom[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }
            $req1 = $this->deleteTuplesOneTable("dvd", ["id" => $id]);
            $req2 = $this->deleteTuplesOneTable("livres_dvd", ["id" => $id]);
            $req3 = $this->deleteTuplesOneTable("document", ["id" => $id]);

            if ($req1 === null || $req2 === null || $req3 === null) {
                $this->conn->rollback();
                return null;
            }
            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Supprime un dvd de la bdd s'il n'y a aucune dépendance (exemplaire ou commande)
     * @param array|null $champs
     * @return int|null
     */
    private function deleteRevue(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);

        if (!isset($champs["id"])) {
            return null;
        }
        $id = $champs["id"];

        try {
            if ($this->conn === null) {
                return null;
            }
            $this->conn->beginTransaction();
            $verifEx = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM exemplaire WHERE id = :id",
                ["id" => $id]
            );
            if (!empty($verifEx) && $verifEx[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }

            $verifCom = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM commande WHERE id = :id",
                ["id" => $id]
            );

            if (!empty($verifCom) && $verifCom[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }
            $req1 = $this->deleteTuplesOneTable("revue", ["id" => $id]);
            $req2 = $this->deleteTuplesOneTable("document", ["id" => $id]);
            if ($req1 === null || $req2 === null) {
                $this->conn->rollback();
                return null;
            }
            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Récupère la liste des commandes associées à un livre ou à un dvd
     * @param array|null $champs Tableau contenant l'identifiant 'idLivreDvd'
     * @return array|null Liste des commandes ou null si paramètre invalide
     */
    private function selectDocumentCommande(?array $champs): ?array
    {
        if (empty($champs)) {
            return null;
        }

        if (!array_key_exists('idLivreDvd', $champs)) {
            return null;
        }

        $champNecessaire['idLivreDvd'] = $champs['idLivreDvd'];

        // Construction de la requête SQL
        $requete = "SELECT c.id, c.dateCommande, c.montant, ";
        $requete .= "cd.nbExemplaire, cd.idLivreDvd, cd.idSuivi, ";
        $requete .= "s.libelleSuivi ";
        $requete .= "FROM commandedocument cd ";
        $requete .= "JOIN commande c ON cd.id = c.id ";
        $requete .= "LEFT JOIN suivi s ON cd.idSuivi = s.idSuivi ";
        $requete .= "WHERE cd.idLivreDvd = :idLivreDvd ";
        $requete .= "ORDER BY c.dateCommande DESC";

        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Insère une commande à un document (livre ou dvd)
     * @param array|null $champs Données nécessaires à l'insertion
     * @return int|null Retourne 1 en cas de réussite, null en cas d'échec
     */
    private function insertCommandeDocument(?array $champs): ?int
    {
        // Vérification des paramètres
        if (empty($champs)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);
        // Contrôle des champs obligatoires
        if (
                !isset($champs["idcommande"]) ||
                !isset($champs["datecommande"]) ||
                !isset($champs["montant"]) ||
                !isset($champs["nbexemplaire"]) ||
                !isset($champs["idlivredvd"]) ||
                !isset($champs["idsuivi"])
        ) {
            return null;
        }
        if ($this->conn === null) {
            return null;
        }

        try {
            $this->conn->beginTransaction();
            // Vérification de l'existence de la commande
            $verif = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM commande WHERE id = :id",
                ["id" => $champs["idcommande"]]
            );
            if (!empty($verif) && $verif[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }
            // Données table commande
            $champsCommande = [
                "id" => $champs["idcommande"],
                "dateCommande" => $champs["datecommande"],
                "montant" => $champs["montant"]
            ];

            // Données table commandedocument
            $champsCommandedDocument = [
                "id" => $champs["idcommande"],
                "nbExemplaire" => $champs["nbexemplaire"],
                "idLivreDvd" => $champs["idlivredvd"],
                "idSuivi" => $champs["idsuivi"]
            ];

            // Insertion dans les deux tables
            $reqCommande = $this->insertOneTupleOneTable("commande", $champsCommande);
            $reqCommandedDocument = $this->insertOneTupleOneTable("commandedocument", $champsCommandedDocument);

            if ($reqCommande === null || $reqCommandedDocument === null) {
                $this->conn->rollback();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Met à jour l'état de suivi d'une commande
     * @param string|null $id Identifiant de la commande
     * @param array|null $champs Données contenant le nouvel état
     * @return int|null Retourne 1 en cas de réussite, null en cas d'échec
     */
    private function updateCommandeDocument(?string $id, ?array $champs): ?int
    {
        if (empty($champs) || empty($id)) {
            return null;
        }

        $champs = array_change_key_case($champs, CASE_LOWER);
        $idSuivi = $champs["idsuivi"] ?? $champs["idsuivi"] ?? null;

        if (empty($champs["idsuivi"])) {
            return null;
        }
        if ($this->conn === null) {
            return null;
        }

        try {
            // Récupération de l'état actuel
            $verif = $this->conn->queryBDD(
                "SELECT idSuivi FROM commandedocument WHERE id = :id",
                ["id" => $id]
            );

            if (empty($verif)) {
                return null;
            }

            $etatActuel = $verif[0]["idSuivi"];
            $nouvelEtat = $idSuivi;

            // Pas de mise à jour si l'état est identique
            if ($etatActuel === $nouvelEtat) {
                return null;
            }

            /*
              Règles métier
             * Une commande réglée (0003) ne peut plus être modifiée
             * Réglée(0003) ne peut être atteinte que depuis livrée(0002)
             * Retour en arrière interdit
             */
            // Réglée = état terminal
            if ($etatActuel === "0003") {
                return null;
            }

            // Réglée uniquement depuis Livrée
            if ($nouvelEtat === "0003" && $etatActuel !== "0002") {
                return null;
            }

            // Retour en arrière interdit (0001 : en cours)
            if ($etatActuel === "0002" && $nouvelEtat === "0001") {
                return null;
            }

            $this->conn->beginTransaction();

            $resultat = $this->updateOneTupleOneTable("commandedocument", $id, ["idSuivi" => $nouvelEtat]);

            if ($resultat === null) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $e) {
            if ($this->conn !== null) {
                $this->conn->rollBack();
            }
            return null;
        }
    }

    /**
     * Supprime la commande d'une document
     * @param array|null $champs Tableau contenant l'identifiant de la commande à supprimer
     * @return int|null Retourne 1 en cas de réussite, null en cas d'échec
     */
    private function deleteCommandeDocument(?array $champs): ?int
    {
        // Vérification des paramètres
        if (empty($champs) || !isset($champs["id"])) {
            return null;
        }

        $id = $champs["id"];

        if ($this->conn === null) {
            return null;
        }

        try {
            // Vérifier l'état actuel de la commande avant sa suppression
            $verifEtat = $this->conn->queryBDD(
                "SELECT idSuivi FROM commandedocument WHERE id = :id",
                ["id" => $id]
            );

            if (!empty($verifEtat)) {
                $etat = $verifEtat[0]["idSuivi"];
                // Une commande livrée (0002) ou une commande réglée (0003) ne peut être supprimée
                if ($etat === "0002" || $etat === "0003") {
                    return null;
                }
            }

            $this->conn->beginTransaction();

            // Suppression dans l'ordre logique : table enfant puis table parent
            $req1 = $this->deleteTuplesOneTable(
                "commandedocument",
                ["id" => $id]
            );

            $req2 = $this->deleteTuplesOneTable(
                "commande",
                ["id" => $id]
            );
            //Vérification que les deux suppressions ont réusssi
            if ($req1 === null || $req2 === null) {
                $this->conn->rollback();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Retourne la liste des commandes d'une revue (abonnements)
     * @param array|null $champs
     * @return array|null
     */
    private function selectCommandesRevue(?array $champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('idRevue', $champs)) {
            return null;
        }
        $champNecessaire['idRevue'] = $champs['idRevue'];

        $requete = "SELECT c.id AS idCommande, ";
        $requete .= "c.dateCommande AS dateCommande, ";
        $requete .= "c.montant AS montant, ";
        $requete .= "a.dateFinAbonnement AS dateFinAbonnement, ";
        $requete .= "a.idRevue AS idRevue ";
        $requete .= "FROM abonnement a ";
        $requete .= "JOIN commande c ON a.id = c.id ";
        $requete .= "WHERE a.idRevue = :idRevue ";
        $requete .= "ORDER BY c.dateCommande DESC ";

        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Insère un abonnement à une revue
     * @param array|null $champs
     * @return int|null Retourne 1 en cas de réussite, null en cas d'échec
     */
    private function insertAbonnementRevue(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }

        $champs = array_change_key_case($champs, CASE_LOWER);

        if (
                !isset($champs["idcommande"]) ||
                !isset($champs["datecommande"]) ||
                !isset($champs["montant"]) ||
                !isset($champs["datefinabonnement"]) ||
                !isset($champs["idrevue"])
        ) {
            return null;
        }

        if ($this->conn === null)
            return null;

        try {

            $this->conn->beginTransaction();

            $verif = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM commande WHERE id = :id",
                ["id" => $champs["idcommande"]]
            );

            if (!empty($verif) && $verif[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }

            // Vérification de la cohérence des dates
            if (strtotime($champs["datefinabonnement"]) < strtotime($champs["datecommande"])) {
                $this->conn->rollback();
                return null;
            }

            $champsCommande = [
                "id" => $champs["idcommande"],
                "dateCommande" => $champs["datecommande"],
                "montant" => $champs["montant"]
            ];
            $champsAbonnement = [
                "id" => $champs["idcommande"],
                "dateFinAbonnement" => $champs["datefinabonnement"],
                "idRevue" => $champs["idrevue"]
            ];
            //Insertion dans la table commande (table mère) puis dans la table abonnement (table fille)
            $reqCommande = $this->insertOneTupleOneTable("commande", $champsCommande);
            $reqAbonnement = $this->insertOneTupleOneTable("abonnement", $champsAbonnement);

            if ($reqCommande === null || $reqAbonnement === null) {
                $this->conn->rollback();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $ex) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Supprime l'abonnement d'une revue
     * @param array|null $champs
     * @return int|null Retourne 1 en cas de réussite, null en cas d'échec
     */
    private function deleteAbonnementRevue(?array $champs): ?int
    {
        if (empty($champs) || !isset($champs["id"])) {
            return null;
        }
        $id = $champs["id"];
        try {
            $this->conn->beginTransaction();

            // suppression dans abonnement (table fille)
            $req1 = $this->conn->updateBDD(
                "DELETE FROM abonnement WHERE id = :id",
                ["id" => $id]
            );

            if ($req1 === null) {
                $this->conn->rollback();
                return null;
            }

            // suppression dans commande (table mère)
            $req2 = $this->conn->updateBDD(
                "DELETE FROM commande WHERE id = :id",
                ["id" => $id]
            );

            if ($req2 === null) {
                $this->conn->rollback();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $e) {
            $this->conn->rollback();
            return null;
        }
    }

    /**
     * Retourne les abonnements se terminant dans les 30 jours
     * @param array|null $champs
     * @return array|null liste des revues et dates de fin d'abonnement
     */
    private function selectAbonnementsFinissant(?array $champs): ?array
    {
        $requete = "SELECT d.titre AS titreRevue, a.dateFinAbonnement ";
        $requete .= "FROM document d ";
        $requete .= "JOIN abonnement a ON d.id = a.idRevue ";
        $requete .= "WHERE a.dateFinAbonnement BETWEEN CURRENT_DATE() ";
        $requete .= "AND DATE_ADD(CURRENT_DATE(), INTERVAL 30 DAY) ";
        $requete .= "ORDER BY a.dateFinAbonnement ASC";

        return $this->conn->queryBDD($requete);
    }
}
