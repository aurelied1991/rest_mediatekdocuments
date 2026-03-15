<?php

include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL et d'accès aux données spécifique à l'application MediatekDocuments.
 * Cette classe hérite de AccessBDD et implémente les méthodes permettant
 * de construire et exécuter les requêtes SQL pour les différentes entités
 * de l'application
 * Elle gère également certaines règles métier et utilise des transactions
 * afin de garantir l'intégrité des données lors des opérations complexes.
 */
class MyAccessBDD extends AccessBDD
{
    /**
     * Constructeur de la classe MyAccessBDD
     * Initialise l'accès à la base de données en appelant
     * le constructeur de la classe mère AccessBDD.
     */
    public function __construct()
    {
        try {
            parent::__construct();
        } catch (\Exception $e) {
            error_log($e->getMessage());
            throw $e;
        }
    }

    /**
     * Traite une requête SELECT (recherche)
     * Selon la table demandée, appelle la méthode correspondante
     * permettant de récupérer les données.
     * @param string $table Nom de la table à interroger
     * @param array|null $champs Paramètres éventuels de filtrage
     * @return array|null Résultat de la requête ou null en cas d'erreur
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
                return $this->selectExemplairesDocument($champs);
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
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }
    }

    /**
     * Traite une requête INSERT (demande d'ajout)
     * Selon la table ciblée, appelle la méthode spécifique
     * permettant d'insérer les données dans la base.
     * @param string $table Nom de la table
     * @param array|null $champs Données à insérer
     * @return int|null Nombre de lignes insérées ou null en cas d'erreur
     * @override
     */
    protected function traitementInsert(string $table, ?array $champs): ?int
    {
        switch ($table) {
            case "" :
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
     * Traite une requête UPDATE (demande de modification)
     * Selon la table ciblée, appelle la méthode correspondante
     * permettant de modifier les données existantes.
     * @param string $table Nom de la table
     * @param string|null $id Identifiant de l'enregistrement à modifier
     * @param array|null $champs Données à modifier
     * @return int|null Nombre de lignes modifiées ou null en cas d'erreur
     * @override
     */
    protected function traitementUpdate(string $table, ?string $id, ?array $champs): ?int
    {
        switch ($table) {
            case "" :
            case "livre" :
                return $this->updateLivre($id, $champs);
            case "dvd" :
                return $this->updateDvd($id, $champs);
            case "revue" :
                return $this->updateRevue($id, $champs);
            case "commandedocument" :
                return $this->updateCommandeDocument($id, $champs);
            case "exemplaire" :
                return $this->updateExemplaireDocument($id, $champs);
            default:
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }
    }

    /**
     * Traite une requête DELETE (demande de suppression)
     * Selon la table ciblée, appelle la méthode spécifique
     * permettant de supprimer les données.
     * @param string $table Nom de la table
     * @param array|null $champs Critères de suppression
     * @return int|null Nombre de lignes supprimées ou null en cas d'erreur
     * @override
     */
    protected function traitementDelete(string $table, ?array $champs): ?int
    {
        switch ($table) {
            case "" :
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
            case "exemplaire":
                return $this->deleteExemplaireDocument($champs);
            default:
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);
        }
    }

    /**
     * Récupère un ou plusieurs enregistrements d'une seule table.
     * Si aucun champ n'est fourni, tous les enregistrements de la table
     * sont retournés. Sinon, une clause WHERE est construite dynamiquement
     * à partir des champs fournis.
     * @param string $table Nom de la table à interroger
     * @param array|null $champs Critères de recherche (clé = colonne, valeur = filtre)
     * @return array|null Liste des enregistrements trouvés ou null en cas d'erreur
     */
    private function selectTuplesOneTable(string $table, ?array $champs): ?array
    {
        if (empty($champs)) {
            // Tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);
        } else {
            // Tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value) {
                $requete .= "$key=:$key and ";
            }
            // Enlève le dernier and
            $requete = substr($requete, 0, strlen($requete) - 5);
            return $this->conn->queryBDD($requete, $champs);
        }
    }

    /**
     * Insère un enregistrement dans une table.
     * La requête INSERT est construite dynamiquement à partir
     * des champs fournis en paramètre.
     * @param string $table Nom de la table dans laquelle insérer les données
     * @param array|null $champs Données à insérer (clé = colonne, valeur = donnée)
     * @return int|null Nombre de lignes insérées (0 ou 1) ou null en cas d'erreur
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
        // Enlève la dernière virgule
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ") values (";
        foreach ($champs as $key => $value) {
            $requete .= ":$key,";
        }
        
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * Modifie un enregistrement dans une table
     * La requête UPDATE est construite dynamiquement à partir
     * des champs fournis. L'enregistrement à modifier est identifié
     * grâce à son identifiant.
     * @param string $table Nom de la table à modifier
     * @param string\null $id Identifiant de l'enregistrement
     * @param array|null $champs Champs à mettre à jour
     * @return int|null Nombre de lignes modifiées (0 ou 1) ou null en cas d'erreur
     */
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        if (is_null($id)) {
            return null;
        }
        // Construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key,";
        }
        // Enlève la dernière virgule
        $requete = substr($requete, 0, strlen($requete) - 1);
        $champs["id"] = $id;
        $requete .= " where id=:id;";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * Supprime un ou plusieurs enregistrements d'une table.
     * La clause WHERE est construite dynamiquement à partir
     * des champs fournis en paramètre.
     * @param string $table Nom de la table
     * @param array|null $champs Critères de suppression
     * @return int|null Nombre de lignes supprimées ou null en cas d'erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        // Construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key and ";
        }
        // Enlève le dernier and
        $requete = substr($requete, 0, strlen($requete) - 5);
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * Récupère les enregistrements d'une table simple.
     * Cette méthode est utilisée pour les tables contenant uniquement un identifiant
     * et un libellé (ex : genre, public, rayon).
     * @param string $table Nom de la table
     * @return array|null Liste des enregistrements triés par libellé
     */
    private function selectTableSimple(string $table): ?array
    {
        $requete = "select * from $table order by libelle;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * Récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null Liste des livres avec leurs informations
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
     * Récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null Liste des DVD avec leurs informations
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
     * Récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null Liste des revues avec leurs informations
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
     * Récupère tous les exemplaires d'un document
     * Les exemplaires sont triés par date d'achat décroissante
     * et incluent l'état de chaque exemplaire.
     * @param array|null $champs Doit contenir l'identifiant du document (id)
     * @return array|null Liste des exemplaires du document ou null si erreur
     */
    private function selectExemplairesDocument(?array $champs): ?array
    {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $champNecessaire['id'] = $champs['id'];

        $requete = "SELECT e.numero, e.dateAchat, e.photo, e.idEtat, et.libelle AS libelleEtat ";
        $requete .= "FROM exemplaire e ";
        $requete .= "JOIN etat et ON e.idEtat = et.id ";
        $requete .= "WHERE e.id = :id ";
        $requete .= "ORDER BY e.dateAchat DESC";

        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Insère un nouveau livre dans la bdd en respectant l'héritage des tables
     * L'opération est réalisée dans une transaction afin de garantir
     * l'intégrité des données en cas d'erreur lors d'une des insertions.
     * @param array|null $champs Données du livre à insérer
     * @return int|null 1 si l'insertion est réussie, null en cas d'échec
     */
    private function insertLivre(?array $champs): ?int
    {
        // Vérifie que les données sont présentes
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
     * @param array|null $champs Données du DVD à insérer
     * @return int|null 1 si l'insertion est réussie, null en cas d'échec
     */
    private function insertDvd(?array $champs): ?int
    {
        if (empty($champs)) {
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
            $this->conn->beginTransaction();
            $verif = $this->conn->queryBDD(
                "SELECT COUNT(*) as nb FROM document WHERE id = :id",
                ["id" => $champs["id"]]
            );
            if (!empty($verif) && $verif[0]["nb"] > 0) {
                $this->conn->rollback();
                return null;
            }

            $champsDocument = [
                "id" => $champs["id"],
                "titre" => $champs["titre"] ?? "",
                "image" => $champs["image"] ?? "",
                "idGenre" => $champs["idgenre"],
                "idPublic" => $champs["idpublic"],
                "idRayon" => $champs["idrayon"]
            ];

            $champsLivreDvd = [
                "id" => $champs["id"]
            ];

            $champsDvd = [
                "id" => $champs["id"],
                "synopsis" => $champs["synopsis"] ?? "",
                "realisateur" => $champs["realisateur"] ?? "",
                "duree" => $champs["duree"] ?? ""
            ];
            
            $reqDoc = $this->insertOneTupleOneTable("document", $champsDocument);
            $reqLivreDvd = $this->insertOneTupleOneTable("livres_dvd", $champsLivreDvd);
            $reqDvd = $this->insertOneTupleOneTable("dvd", $champsDvd);

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
            $this->conn->rollBack();
        }
        return null;
    }

    /**
     * Insère une nouvelle revue dans la bdd en respectant l'héritage des tables
     * Opération réalisée dans une transaction pour la sécurité des données
     * @param array|null $champs Données de la revue à insérer
     * @return int|null 1 si l'insertion est réussie, null en cas d'échec
     */
    private function insertRevue(?array $champs): ?int
    {
        if (empty($champs)) {
            return null;
        }
        $champs = array_change_key_case($champs, CASE_LOWER);
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

            $reqDoc = $this->insertOneTupleOneTable("document", $champsDocument);
            $reqRevue = $this->insertOneTupleOneTable("revue", $champsRevue);
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
            $this->conn->rollBack();
        }
        return null;
    }

    /**
     * Met à jour un livre existant dans la bdd
     * @param string|null $id Identifiant du livre à modifier
     * @param array|null $champs Données mises à jour du livre
     * @return int|null 1 si la mise à jour est réussie, null en cas d'échec
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
     * Met à jour un DVD existant dans la base de données.
     * @param string|null $id Identifiant du DVD à modifier
     * @param array|null $champs Données mises à jour du DVD
     * @return int|null 1 si la mise à jour est réussie, null en cas d'échec
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
     * Met à jour une revue existante dans la base de données
     * @param string|null $id Identifiant de la revue à modifier
     * @param array|null $champs Données mises à jour de la revue
     * @return int|null 1 si la mise à jour est réussie, null en cas d'échec
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

            $champsDocument = [
                "titre" => $champs["titre"],
                "image" => $champs["image"] ?? "",
                "idGenre" => $champs["idgenre"],
                "idPublic" => $champs["idpublic"],
                "idRayon" => $champs["idrayon"]
            ];
            
            $champsRevue = [
                "periodicite" => $champs["periodicite"] ?? "",
                "delaimiseadispo" => $champs["delaimiseadispo"] ?? "",
            ];
            
            $reqDoc = $this->updateOneTupleOneTable("document", $id, $champsDocument);
            $reqRevue = $this->updateOneTupleOneTable("revue", $id, $champsRevue);
            
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
     * Suppressions effectuées dans l'ordre des tables héritées
     * @param array|null $champs Doit contenir l'identifiant du livre à supprimer
     * @return int|null 1 si la suppression est réussie, null en cas d'échec
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
     * @param array|null $champs Doit contenir l'identifiant du DVD à supprimer
     * @return int|null 1 si la suppression est réussie, null en cas d'échec
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
     * Supprime une revue de la base de données s'il n'y a aucune dépendance (exemplaire ou commande)
     * @param array|null $champs Doit contenir l'identifiant de la revue à supprimer
     * @return int|null 1 si la suppression est réussie, null en cas d'échec
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
     * Récupère la liste des commandes associées à un document (livre ou DVD)
     * @param array|null $champs Tableau contenant l'identifiant du document 'idLivreDvd'
     * @return array|null Liste des commandes associées au document ou null si paramètre invalide
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
     * Insère une commande associée à un document (livre ou DVD)
     * @param array|null $champs Données nécessaires à l'insertion de la commande
     * @return int|null 1 si l'insertion est réussie, null en cas d'échec
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
     * Met à jour l'état de suivi d'une commande de document en respectant les règles métier
     * @param string|null $id Identifiant de la commande
     * @param array|null $champs Données contenant le nouvel état de suivi
     * @return int|null 1 si la mise à jour est réussie, null en cas d'échec
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
     * Supprime une commande associée à un document.
     * Autorisée uniquement si la commande n'est pas livrée ou réglée.
     * @param array|null $champs Tableau contenant l'identifiant de la commande à supprimer
     * @return int|null 1 si la suppression est réussie, null en cas d'échec
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
     * Récupère la liste des commandes d'abonnement associées à une revue.
     * La méthode retourne les informations de commande ainsi que
     * la date de fin d'abonnement correspondante.
     * @param array|null $champs Tableau contenant l'identifiant de la revue (idRevue)
     * @return array|null Liste des commandes d'abonnement ou null si paramètre invalide
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
     * Insère un abonnement pour une revue
     * @param array|null $champs Données nécessaires à l'insertion de l'abonnement
     * @return int|null 1 si l'insertion est réussie, null en cas d'échec
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
     * Supprime un abonnement associé à une revue
     * @param array|null $champs Tableau contenant l'identifiant de l'abonnement
     * @return int|null 1 si la suppression est réussie, null en cas d'échec
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
     * Récupère la liste des abonnements arrivant à expiration dans moins de 30 jours
     * @param array|null $champs Paramètre non utilisé
     * @return array|null Liste des revues et dates de fin d'abonnement
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

    /**
     * Met à jour un enregistrement d'une table possédant une clé composée.
     * La requête UPDATE est construite dynamiquement à partir :
     * - des colonnes composant la clé primaire (clause WHERE)
     * - des champs à modifier (clause SET).
     * Cette méthode permet de gérer les tables dont l'identification
     * d'un enregistrement nécessite plusieurs colonnes.
     * @param string $nomTable Nom de la table à mettre à jour
     * @param array $keys Tableau associatif des colonnes composant la clé (ex : ["id" => "123", "numero" => 1])
     * @param array $updateFields  Tableau associatif des colonnes à modifier et de leurs valeurs
     * @return int|null 1 si la mise à jour est réussie, null en cas d'échec
     */
    private function updateWithCompositeKey(string $nomTable, array $keys, array $updateFields): ?int
    {
        // Si aucune clé ou aucun champs à modifier, on ne fait rien
        if (empty($keys) || empty($updateFields)) {
            return null;
        }

        //Construction de la partie SET de la requête
        $setParts = [];
        $parametres = [];
        foreach ($updateFields as $colonne => $value) {
            $setParts[] = "$colonne = :$colonne";
            $parametres[$colonne] = $value;
        }
        $setSql = implode(", ", $setParts);

        //Construction de la partie WHERE de la requête (clés composées)
        $whereParts = [];
        foreach ($keys as $colonne => $value) {
            $whereParts[] = "$colonne = :where_$colonne";
            $parametres["where_$colonne"] = $value;
        }
        $whereSql = implode(" AND ", $whereParts);

        //Requête SQL finale
        $sql = "UPDATE $nomTable SET $setSql WHERE $whereSql";

        try {
            //Exécution de la requête avec les parametres
            $result = $this->conn->queryBDD($sql, $parametres);
            if ($result !== null) {
                return 1;
            } else {
                return null;
            }
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Met à jour l'état d'un exemplaire d'un document
     *  Un exemplaire est identifié par une clé composée :
     * - l'identifiant du document
     * - le numéro d'exemplaire.
     * @param string|null $id Identifiant du document
     * @param array|null $champs Tableau contenant au minimum les champs 'numero' et 'idEtat'
     * @return int|null 1 si la mise à jour est réussie, null en cas d'échec
     */
    private function updateExemplaireDocument(?string $id, ?array $champs): ?int
    {
        if (empty($champs) || empty($id) || !isset($champs['numero'])) {
            return null;
        }

        $champs = array_change_key_case($champs, CASE_LOWER);

        // Vérifier qu'il y a bien un idEtat à modifier
        if (empty($champs['idetat'])) {
            return null;
        }

        try {
            if ($this->conn === null) {
                return null;
            }

            // Vérifier que l'exemplaire existe
            $verif = $this->conn->queryBDD(
                "SELECT idEtat FROM exemplaire WHERE id = :id AND numero = :numero",
                ["id" => $id, "numero" => $champs['numero']]
            );
            if (empty($verif)) {
                return null;
            }

            $etatActuel = $verif[0]['idEtat'];
            $nouvelEtat = $champs['idetat'];

            //Pas de modification si l'état est identique
            if ($etatActuel === $nouvelEtat) {
                return null;
            }


            $this->conn->beginTransaction();

            // Mise à jour uniquement de l'état
            $champsExemplaire = ["idEtat" => $nouvelEtat];

            // Mise à jour avec clé composée (id + numero)
            $resultat = $this->updateWithCompositeKey(
                "exemplaire",
                ["id" => $id, "numero" => $champs['numero']],
                $champsExemplaire
            );

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
     * Supprime un exemplaire d'un document. L'exemplaire est identifié par une clé composée :
     * - l'identifiant du document
     * - le numéro d'exemplaire.
     * @param array|null $champs Tableau associatif contenant 'id' et 'numero'
     * @return int|null 1 si la suppression est réussie, null en cas d'échec
     */
    private function deleteExemplaireDocument(?array $champs): ?int
    {
        if (empty($champs) || !isset($champs["id"], $champs["numero"])) {
            return null;
        }

        $id = $champs["id"];
        $numero = (int) $champs["numero"];

        try {
            $this->conn->beginTransaction();

            $req = $this->conn->updateBDD(
                "DELETE FROM exemplaire WHERE id = :id AND numero = :numero",
                ["id" => $id, "numero" => $numero]
            );

            // vérifier si au moins une ligne a été supprimée
            if ($req === null || $req === 0) {
                $this->conn->rollBack();
                return null;
            }

            $this->conn->commit();
            return 1;
        } catch (Exception $e) {
            $this->conn->rollBack();
            return null;
        }
    }
}
