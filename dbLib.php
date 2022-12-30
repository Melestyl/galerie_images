<?php
// Fichier contenant les functions utiles à l'interaction avec la base de données

include_once "config.php";

function connectDB() {
	// Connexion PDO à la base de données
	global $DB_NAME, $HOST, $NICKNAME, $PASSWORD;

	$dsn = "mysql:host=".$HOST.";dbname=".$DB_NAME.";charset=utf8";
	$pdo = new PDO($dsn, $NICKNAME, $PASSWORD);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	return $pdo;
}

function createDirectory($pdo, $directory) {
	// Crée un répertoire dans la base de données

	$sql = "INSERT INTO REPERTOIRE (nom) VALUES (:nom)";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(':nom', $directory);
	$stmt->execute();
}

function getDirectoryID($pdo, $directory) {
	// Retourne l'ID du répertoire passé en paramètre

	$sql = "SELECT id FROM REPERTOIRE WHERE nom = :directory";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(":directory", $directory);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result["id"];
}

function deleteDirectory($pdo, $directory) {
	// Supprime un répertoire de la base de données

	// Récupère toutes les images du répertoire pour les supprimer de la table IMAGE (pour éviter les erreurs de clé étrangère)
	$sql = "SELECT id FROM IMAGE WHERE repertoire = (SELECT id FROM REPERTOIRE WHERE nom = :directory)";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(":directory", $directory);
	$stmt->execute();
	// Supprime les images du répertoire
	while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
		deleteImage($pdo, $result["id"]);
	}

	// Supprime le répertoire
	$sql = "DELETE FROM REPERTOIRE WHERE nom = :nom";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(':nom', $directory);
	$stmt->execute();
}

function createImage($pdo, $image, $directory) {
	// Crée une image dans la BDD

	// On récupère l'ID du répertoire
	$directoryID = getDirectoryID($pdo, $directory);

	// On crée l'image dans la base de données
	$sql = "INSERT INTO IMAGE (nom, repertoire) VALUES (:imageName, :directory)";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(":imageName", $image);
	$stmt->bindParam(":directory", $directoryID);
	$stmt->execute();

	// On récupère l'ID de l'image
	$imageID = $pdo->lastInsertId();

	return $imageID;
}

function renameImage($pdo, $image, $newName) {
	// Renomme une image dans la base de données

	$sql = "UPDATE IMAGE SET nom = :newName WHERE id = :image";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(":newName", $newName);
	$stmt->bindParam(":image", $image);
	$stmt->execute();
}

function getImageID($pdo, $image, $directory) {
	// Retourne l'ID de l'image

	// On récupère l'ID du répertoire
	$directoryID = getDirectoryID($pdo, $directory);

	$sql = "SELECT id FROM IMAGE WHERE nom = :image AND repertoire = :directory";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(":image", $image);
	$stmt->bindParam(":directory", $directoryID);
	$stmt->execute();
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	return $result["id"];
}

function storeExifData($pdo, $image, $directory, $metadataArray) {
	// Stocke les données exif dans la base de données

	$imageID = createImage($pdo, $image, $directory);

	if (!isset($metadataArray["EXIF"]))
		return;

	// On stocke les données exif dans la base de données
	foreach ($metadataArray as $name => $subarray) {
		foreach ($subarray as $key => $value) {
			$key = $name.'_'.$key;

			if (is_array($value)) // Utile pour le sous-tableau GPS, car il contient des sous-tableaux
				$value = implode(", ", $value);

			$value = substr($value, 0, 100); // Pour tronquer la chaine de caractère à 100 caractères, sinon erreur de longueur de chaine

			$sql = "INSERT INTO METADONNEE (cle, valeur, image) VALUES (:key, :value, :image)";
			$stmt = $pdo->prepare($sql);
			$stmt->bindParam(":key", $key);
			$stmt->bindParam(":value", $value);
			$stmt->bindParam(":image", $imageID);
			$stmt->execute();
		}
	}
}

function deleteExifData($pdo, $imageID) {
	// Supprime les données exif d'une image

	$sql = "DELETE FROM METADONNEE WHERE image = :image";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(":image", $imageID);
	$stmt->execute();
}

function deleteImage($pdo, $imageID) {
	// Supprime une image de la base de données

	deleteExifData($pdo, $imageID);

	$sql = "DELETE FROM IMAGE WHERE id = :imageID";
	$stmt = $pdo->prepare($sql);
	$stmt->bindParam(":imageID", $imageID);
	$stmt->execute();
}