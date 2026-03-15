<?php

/*
 * Index.php : point d'entrée de l'API
 * - Initialise les objets Url et Controle
 * - Contrôle l'authentification
 * - Récupère les variables envoyées (dans l'URL ou le body)
 * - Récupère la méthode d'envoi HTTP (GET, POST, PUT, DELETE)
 * - Demande au contrôleur de gérer la demande
 */
include_once ("Url.php");
include_once("Controle.php");

// Création de l'instance pour accéder aux informations de l'URL
$url = Url::getInstance();
// Création du contrôleur pour traiter les requêtes
$controle = new Controle();

// Vérifie l'authentification
if (!$url->authentification()) {
    // L'authentification a échoué
    $controle->unauthorized();
} else {
    // Récupère la méthode HTTP utilisée pour accéder à l'API
    $methodeHTTP = $url->recupMethodeHTTP();
    //Récupère les données passées dans l'url (visibles ou cachées)
    $table = $url->recupVariable("table");
    $id = $url->recupVariable("id");
    $champs = $url->recupVariable("champs", "json");
    // Demande au controleur de traiter la demande
    $controle->demande($methodeHTTP, $table, $id, $champs);
}
