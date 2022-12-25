<?php
// Fichier contenant les functions utiles à l'interaction avec la base de données

// TODO: Créer des fonctions spécifiques aux actions qu'on peut faire, pour ne pas utiliser des fonctions génériques

include_once "config.php";

function SQLSelect($query) {
	global $HOST, $DB_NAME, $NICKNAME, $PASSWORD; // Nécessaire pour accéder aux variables de config.php

	$db = new PDO("mysql:host=$HOST;dbname=$DB_NAME", $NICKNAME, $PASSWORD);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$result = $db->query($query);
	$db = null;

	return $result->fetchAll(PDO::FETCH_ASSOC);
}

function SQLInsert($query) {
	global $HOST, $DB_NAME, $NICKNAME, $PASSWORD; // Nécessaire pour accéder aux variables de config.php

	$db = new PDO("mysql:host=$HOST;dbname=$DB_NAME", $NICKNAME, $PASSWORD);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$result = $db->query($query);
	$db = null;
}

function SQLUpdate($query) {
	SQLInsert($query); // La fonction SQLUpdate est identique à SQLInsert, mais son nom diffère pour des raisons de compréhension
}