<h1>Présentation de l'API</h1>
Cette API REST permet de gérer les documents (livres, DVD, revues) d’une médiathèque via une application cliente C#.
Voici le lien vers le dépôt d'origine de l'API REST dont se trouve, dans le README, la présentation de l'API REST d'origine avec ses premières fonctionnalités et comment l'exploiter : <br>
https://github.com/CNED-SLAM/rest_mediatekdocuments <br>
Le readme actuel présente les nouvelles fonctionnalités ajoutées à cette l'API REST et comment installer cette dernière en local.<br>
Cette API permet d'exécuter des requêtes SQL sur la BDD Mediatek86 créée avec le SGBDR MySQL.<br>
Sa vocation actuelle est de répondre aux demandes de l'application MediaTekDocuments, mise en ligne sur le dépôt :<br>
https://github.com/aurelied1991/mediaTekDocuments

<h1>Installation de l'API en local</h1>
Pour tester l'API REST en local, voici le mode opératoire (similaire à celui donné dans le dépôt d'API de base) :
<ul>
   <li>Installer les outils nécessaires (WampServer ou équivalent, NetBeans ou équivalent pour gérer l'API dans un IDE, Postman pour les tests).</li>
   <li>Télécharger le zip du code de l'API et le dézipper dans le dossier www de wampserver (renommer le dossier en "rest_mediatekdocuments", donc en enlevant "_master").</li>
   <li>Si 'Composer' n'est pas installé, le télécharger avec ce lien et l'installer : https://getcomposer.org/Composer-Setup.exe </li>
   <li>Dans une fenêtre de commandes ouverte en mode admin, aller dans le dossier de l'API et taper 'composer install' puis valider pour recréer le vendor.</li>
   <li>Récupérer le script mediatek86.sql en racine du projet puis, avec phpMyAdmin, créer la BDD mediatek86 et, dans cette BDD, exécuter le script pour remplir la BDD.</li>
   <li>Ouvrir l'API dans NetBeans pour pouvoir analyser le code et le faire évoluer suivant les besoins.</li>
   <li>Adresse de l'API (en local) : http://localhost/rest_mediatekdocuments/ </li>
   <li>Pour tester l'API avec Postman, configurer l'authentification (onglet "Authorization", Type "Basic Auth"). Les identifiants (username et password) sont à définir dans le fichier `.env`, non fourni dans le dépôt pour des raisons de sécurité.</li>
</ul>

<h1>Les fonctionnalités ajoutées</h1>
Dans MyAccessBDD, plusieurs fonctions ont été ajoutées pour répondre aux nouvelles demandes de l'application C# MediaTekDocuments :<br>
<h2>Gestion des livres</h2>
<ul>
   <li><strong>insertLivre : </strong>ajoute un livre avec ses informations.</li>
   <li><strong>updateLivre : </strong>modifie les informations d'un livre.</li>
   <li><strong>deleteLivre : </strong>supprime un livre de la base de données.</li>
</ul>
<h2>Gestion des DVD</h2>
<ul>
   <li><strong>insertDvd : </strong>ajoute un DVD avec ses informations.</li>
   <li><strong>updateDvd : </strong>modifie les informations d'un DVD.</li>
   <li><strong>deleteDvd : </strong>supprime un DVD de la base de données.</li>
</ul>
<h2>Gestion des revues</h2>
<ul>
   <li><strong>insertRevue : </strong>ajoute une revue avec ses informations.</li>
   <li><strong>updateRevue : </strong>modifie les informations d'une revue.</li>
   <li><strong>deleteRevue : </strong>supprime une revue de la base de données.</li>
</ul>
   <h2>Gestion des commandes</h2>
<ul>
   <li><strong>selectDocumentCommande : </strong>récupère la liste des commandes associées à un document (DVD ou livre).</li>
   <li><strong>insertCommandeDocument : </strong>Insère une commande liée à un livre ou à un DVD.</li>
   <li><strong>updateCommandeDocument : </strong>met à jour l'état de suivi d'une commande d'un document.</li>
   <li><strong>deleteCommandeDocument : </strong>supprime une commande associée à un document.</li>
   <li><strong>selectCommandesRevue : </strong>récupère la liste des commandes associées à une revue.</li>
</ul>
   <h2>Gestion des abonnements</h2>
<ul>
   <li><strong>insertAbonnementRevue : </strong>insère un abonnement associé à une revue.</li>
   <li><strong>deleteAbonnementRevue : </strong>supprime un abonnement associé à une revue.</li>
   <li><strong>selectAbonnementsFinissant : </strong>sélectionne les abonnements se terminant dans moins de 30 jours.</li>
   <li><strong>updateWithCompositeKey : </strong>met à jour l'enregistrement d'une table possédant une clé composée.</li>
</ul>
   <h2>Autres</h2>
   <ul>
   <li><strong>selectExemplairesDocument : </strong>récupère tous les exemplaires d'un document (par son id) avec leur état (neuf, usagé, détérioré, inutilisable), triés par date d'achat décroissante.</li>
   <li><strong>updateExemplaireDocument : </strong>met à jour l'état d'un exemplaire d'un document.</li>
   <li><strong>deleteExemplaireDocument : </strong> supprime l'exemplaire d'un document.</li>
</ul>
