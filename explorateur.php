<?php


include_once "dbLib.php";

if (isset($_REQUEST["nomRep"])) $nomRep = $_REQUEST["nomRep"];
else $nomRep = false;

if (isset($_REQUEST["action"])) {
	$pdo = connectDB();
	switch ($_REQUEST["action"]) {
		case 'Creer' :
			if (isset($_GET["nomRep"]) && ($_GET["nomRep"] != ""))
				if (!is_dir("./" . $_GET["nomRep"])) {
					// Crée le répertoire
					mkdir("./" . $_GET["nomRep"]);

					// On l'ajoute dans la BDD
					createDirectory($pdo, $_GET["nomRep"]);
				}
			break;

		case 'Supprimer' :
			if (isset($_GET["nomRep"]) && ($_GET["nomRep"] != ""))
				if (isset($_GET["fichier"]) && ($_GET["fichier"] != "")) {
					$nomRep = $_GET["nomRep"];
					$fichier = $_GET["fichier"];

					// Supprime l'image dans le dossier
					unlink($nomRep . "/" . $fichier);
					// Supprime aussi la miniature si elle existe
					unlink($nomRep . "/thumbs/" . $fichier);

					// Supprime l'entrée dans la BDD
					$imageID = getImageID($pdo, $fichier, $nomRep);
					deleteImage($pdo, $imageID);
				}
			break;

		case 'Renommer' :
			if (isset($_GET["nomRep"]) && ($_GET["nomRep"] != ""))
				if (isset($_GET["fichier"]) && ($_GET["fichier"] != ""))
					if (isset($_GET["nomFichier"]) && ($_GET["nomFichier"] != "")) {
						$nomRep = $_GET["nomRep"];
						$fichier = $_GET["fichier"];
						$nomFichier = $_GET["nomFichier"]; // nouveau nom

						// Renomme le fichier et sa miniature si elle existe
						if (file_exists("./$nomRep/$fichier"))
							rename("./$nomRep/$fichier", "./$nomRep/$nomFichier");

						if (file_exists("./$nomRep/thumbs/$fichier"))
							rename("./$nomRep/thumbs/$fichier", "./$nomRep/thumbs/$nomFichier");

						// Met à jour l'entrée dans la BDD
						$imageID = getImageID($pdo, $fichier, $nomRep);
						renameImage($pdo, $imageID, $nomFichier);
					}
			break;

		case 'Uploader' :
			if (!empty($_FILES["FileToUpload"])) {

				if (is_uploaded_file($_FILES["FileToUpload"]["tmp_name"])) {
					/*print("Quelques informations sur le fichier récupéré :<br>");
					print("Nom : ".$_FILES["FileToUpload"]["name"]."<br>");
					print("Type : ".$_FILES["FileToUpload"]["type"]."<br>");
					print("Taille : ".$_FILES["FileToUpload"]["size"]."<br>");
					print("Tempname : ".$_FILES["FileToUpload"]["tmp_name"]."<br>");*/

					$name = $_FILES["FileToUpload"]["name"];
					copy($_FILES["FileToUpload"]["tmp_name"], "./$nomRep/$name");
					$metadata = exif_read_data("./$nomRep/$name", 0, true);

					// Crée le répertoire miniature s'il n'existe pas
					if (!is_dir("./$nomRep/thumbs")) {
						mkdir("./$nomRep/thumbs");
					}

					$dataImg = getimagesize("./$nomRep/$name");
					$type = substr($dataImg["mime"], 6);// on enlève "image/"

					// Crée la miniature dans ce répertoire
					miniature($type, "./$nomRep/$name", 200, "./$nomRep/thumbs/$name","./$nomRep/$name",$dataImg,$metadata['EXIF']['DateTimeDigitized'],$metadata['GPS']['GPSLatitude'],$metadata['GPS']['GPSLatitudeRef'],$metadata['GPS']['GPSLongitude'],$metadata['GPS']['GPSLongitudeRef']);

					// Ajoute les métadonnées dans la BDD et crée l'image en meme temps
					
					storeExifData($pdo, $name, $nomRep, $metadata);
				} else {
					echo "Erreur lors de l'upload de l'image";
				}
			}

			break;

		case 'Supprimer Repertoire':
			// On ne peut supprimer que des répertoires vides, il faut donc supprimer les éléments à l'intérieur avant de pouvoir le supprimer
			if (isset($_GET["nomRep"]) && ($_GET["nomRep"] != "")) {
				// Supprime le répertoire des miniatures s'il existe, puis le répertoire principal

				if (is_dir("./$nomRep/thumbs")) {
					$rep = opendir("./$nomRep/thumbs");        // ouverture du repertoire
					while ($fichier = readdir($rep))    // parcours de tout le contenu de ce répertoire
					{

						if (($fichier != ".") && ($fichier != "..")) {
							// Pour éliminer les autres répertoires du menu déroulant, 
							// on dispose de la fonction 'is_dir'
							if (!is_dir("./$nomRep/thumbs/" . $fichier)) {
								unlink("./$nomRep/thumbs/" . $fichier);
							}
						}
					}
					rmdir("./$nomRep/thumbs");
				}

				// Répertoire principal
				$rep = opendir("./$nomRep");        // ouverture du repertoire
				while ($fichier = readdir($rep))    // parcours de tout le contenu de ce répertoire
				{

					if (($fichier != ".") && ($fichier != "..")) {
						// Pour éliminer les autres répertoires du menu déroulant, 
						// on dispose de la fonction 'is_dir'
						if (!is_dir("./$nomRep/" . $fichier)) {
							unlink("./$nomRep/" . $fichier);
						}
					}
				}

				// Supprime l'entrée dans la BDD (en supprimant les images associées)
				deleteDirectory($pdo, $nomRep);

				rmdir("./$nomRep");
				$nomRep = false;

			}
			break;
	}
}


function miniature($type, $nom, $dw, $nomMin, $nomMin2 , $dataImg , $date, $latitude,$latitudeRef,$longitude,$longitudeRef)
{
	// Crée une miniature de l'image $nom
	// de largeur $dw
	// et l'enregistre dans le fichier $nomMin 


	// lecture de l'image d'origine, enregistrement dans la zone mémoire $im
	switch ($type) {
		case "jpeg" :
			$im = imagecreatefromjpeg($nom);
			break;
		case "png" :
			$im = imagecreatefrompng($nom);
			break;
		case "gif" :
			$im = imagecreatefromgif($nom);
			break;
		default:
			return;
	}

	$sw = imagesx($im); // largeur de l'image d'origine
	$sh = imagesy($im); // hauteur de l'image d'origine
	$dh = $dw * $sh / $sw;

// creation de l'image
$im2 = imagecreatetruecolor($dw, $dh);

// copie de $im dans $im2
$dst_x = 0;
$dst_y = 0;
$src_x = 0;
$src_y = 0;
$dst_w = $dw;
$dst_h = $dh;
$src_w = $sw;
$src_h = $sh;
imagecopyresized($im2, $im, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

//DEBUT TEXTE DATE
	//$im2
	// plusieurs couleurs
	$white = imagecolorallocate($im2, 255, 255, 255);
	$black = imagecolorallocate($im2, 0, 0, 0);
	imagefilledrectangle($im2, 0, $dh, $dw, $dh-14, $white);

	// le texte
	$date = new DateTime($date);
	$text = date_format($date, 'd/m/Y');

	// font
	$font = './font/unispace.ttf';

	// ajout du texte
	imagettftext($im2, 5, 0, $dw-55, $dh-4, $black, $font, $text);

	//$im
	// plusieurs couleurs
	$whiteIm = imagecolorallocate($im, 255, 255, 255);
	$blackIm = imagecolorallocate($im, 0, 0, 0);
	imagefilledrectangle($im, 0, round($sh-($sh*3/100)), $sw, $sh, $whiteIm);

	// ajout du texte
	imagettftext($im, round($sh*1/100), 0, round($sw-($sw*15/100)), round($sh-($sh*1/100)), $blackIm, $font, $text);

//FIN TEXTE DATE

//DEBUT TEXTE LOCALISATION
	//DEBUT Recuperation de l'adresse via API
		//Debut zone de convertion des données GPS
			$latitudeFinal = convertirGPS($latitude);
			if ($latitudeRef == 'S' && $latitudeFinal != null){ //si le sud alors c'est négatif
				$latitudeFinal = $latitude * -1;
			}

			$longitudeFinal = convertirGPS($longitude);
			if ($longitudeRef == 'W' && $longitudeFinal != null){ //si L'ouest alors c'est négatif
				$longitudeFinal = $longitude * -1;
			}
		//Fin zone de convertion des données GPS
		//Debut zone API
			if ($latitudeFinal != null && $latitudeFinal != 0 && $longitudeFinal != null && $longitudeFinal != 0){
				$cle = '8963ac49c1744c6ea9e3e400ac71129c';
				$language = 'fr';
				$lien = "https://api.opencagedata.com/geocode/v1/json?q=" . strval($latitudeFinal) . "," . strval($longitudeFinal) . "&key=" . $cle . "&language=" . $language;
	
				$reponseArray = file_get_contents($lien);
				$adresse = json_decode($reponseArray,true)['results'][0]['formatted'];
			} else {
				$adresse = "Aucune adresse !";
			}
			
		//Fin zone API
	//FIN Recuperation de l'adresse via API
	//DEBUT Texte
		// plusieurs couleurs

		// le texte
		$text = $adresse;

		// font
		$font = './font/unispace.ttf';

		// réglage du texte
		$textIm = $text;
		$taille = round((75 / 100 * $sw) / round($sh * 1 / 100));
		if (strlen($textIm) > $taille){
			$textIm = substr($textIm,0,$taille-4);
			$textIm = $textIm . " ...";
		}
		$textIm2 = $text;
		$taille = round((70 / 100 * $dw) / 5);
		if (strlen($textIm2) > $taille){
			$textIm2 = substr($textIm2,0,$taille-4);
			$textIm2 = $textIm2 . "...";
		}

		// ajout du texte
		imagettftext($im2, 5, 0, 0, $dh-4, $black, $font, $textIm2);
		imagettftext($im, round($sh*1/100), 0, round($sh*1/100), round($sh-($sh*1/100)), $blackIm, $font, $textIm);
	
	//FIN Texte
//FIN TEXTE LOCALISATION


	switch ($type) {
			case "jpeg" :
				imagejpeg($im, $nomMin2);
				break;
			case "png" :
				imagepng($im, $nomMin2);
				break;
			case "gif" :
				imagegif($im, $nomMin2);
				break;
		}




	switch ($type) {
		case "jpeg" :
			imagejpeg($im2, $nomMin);
			break;
		case "png" :
			imagepng($im2, $nomMin);
			break;
		case "gif" :
			imagegif($im2, $nomMin);
			break;
	}
	imagedestroy($im);
	imagedestroy($im2);
}
function diviser($donnee){ //permet de faire une division alors que c'est un string
	$tampon = "";
	$numerateur = 0;
	$denominateur = 0;
	foreach(str_split($donnee) as $i){
		if ($i === '/'){
			$numerateur = intval($tampon);
			$tampon = "";
		} else{
			$tampon = $tampon . $i;
		}
	}
	$denominateur = intval($tampon);
	if ($denominateur != 0){
		$numerateur = $numerateur / $denominateur;
	} else {
		$numerateur = 0;
	}
	
	return $numerateur;
}
function convertirGPS($donnee){//permet de convertir 
	if ($donnee === null){
		return null;
	}
	$resultat = diviser($donnee[0]);
	$resultat += diviser($donnee[1])/60;
	$resultat += diviser($donnee[2])/3600;
	return $resultat;
}

?>



<html lang="fr">
<head>
	<style>

		.mini {
			position: relative;
			width: 200px;
			height: 400px;
			float: left;
			border: 1px black solid;
			margin-right: 5px;
			margin-bottom: 5px;
		}

		div img {
			margin: 0 auto 0 auto;
			border: none;
		}

		div div {
			position: absolute;
			bottom: 0;
			width: 100%;
			background-color: lightgrey;
			border-top: 1px black solid;
			text-align: center;
		}

		.renommer {
			width: 150px;
		}

		.btn_renommer {

			width: 35px;
		}

	</style>
	<title>Galerie d'images !</title>
</head>

<body>

<h1>Gestion des répertoires </h1>
<form>
	<label>Créer un nouveau répertoire : </label>
	<input type="text" name="nomRep"/>
	<input type="submit" name="action" value="Creer"/>
</form>

<form>
	<label>Choisir un répertoire : </label>
	<select name="nomRep">
		<?php
		$rep = opendir("./"); // ouverture du repertoire
		while ($fichier = readdir($rep)) {
			// On élimine le résultat '.' (répertoire courant)
			// et '..' (répertoire parent)

			if (($fichier != ".") && ($fichier != "..")) {
				// Pour éliminer les autres fichiers du menu déroulant,
				// on dispose de la fonction 'is_dir'
				if (is_dir("./" . $fichier))
					printf("<option value=\"$fichier\">$fichier</option>");
			}
		}
		closedir($rep);
		?>
	</select>
	<input type="submit" value="Explorer"> <input type="submit" name="action" value="Supprimer Repertoire">
</form>

<?php
if (!$nomRep) die("Choisissez un répertoire");
// interrompt immédiatement l'exécution du code php
?>

<hr/>
<h2> Contenu du répertoire '<?php echo $_GET["nomRep"] ?>' </h2>


<form enctype="multipart/form-data" method="post">
	<input type="hidden" name="MAX_FILE_SIZE" value="10000000">
	<input type="hidden" name="nomRep" value="<?php echo $nomRep; ?>">
	<label>Ajouter un fichier image : </label>
	<input type="file" name="FileToUpload">
	<input type="submit" value="Uploader" name="action">
</form>

<?php

$numImage = 0;
$rep = opendir("./$nomRep");        // ouverture du repertoire
while ($fichier = readdir($rep))    // parcours de tout le contenu de ce répertoire
{

	if (($fichier != ".") && ($fichier != "..")) {
		// Pour éliminer les autres répertoires du menu déroulant,
		// on dispose de la fonction 'is_dir'
		if (!is_dir("./$nomRep/" . $fichier)) {
			// Un fichier... est-ce une image ?
			// On ne liste que les images ...
			$formats = ".jpeg.jpg.gif.png";
			if (strstr($formats, strrchr($fichier, "."))) {
				$numImage++;
				$dataImg = getimagesize("./$nomRep/$fichier");

				// Récupérer le type d'une image et sa taille
				$width = $dataImg[0];
				$height = $dataImg[1];
				$type = substr($dataImg["mime"], 6);

				// On cherche si une miniature existe pour l'afficher...
				// Sinon, on crée éventuellement le répertoire des miniatures et la miniature que l'on place dans ce sous-répertoire

				echo "<div class=\"mini\">\n";
				echo "<a target=\"_blank\" href=\"$nomRep/$fichier\"><img src=\"$nomRep/thumbs/$fichier\" alt='$fichier'/></a>\n";
				echo "<div>$fichier \n";
				echo "<a href=\"?nomRep=$nomRep&fichier=$fichier&action=Supprimer\" >Supp</a>\n";
				echo "<br />($width * $height $type)\n";
				echo "<br />\n";

				echo "<form>\n";
				echo "<input type=\"hidden\" name=\"fichier\" value=\"$fichier\" />\n";
				echo "<input type=\"hidden\" name=\"nomRep\" value=\"$nomRep\" />\n";
				echo "<input type=\"hidden\" name=\"action\" value=\"Renommer\" />\n";
				echo "<input type=\"text\" class=\"renommer\" name=\"nomFichier\" value=\"$fichier\" onclick=\"this.select();\" />\n";
				echo "<input type=\"submit\" class=\"btn_renommer\" value=\">\" />\n";
				echo "</form>\n";

				echo "</div></div>\n";

				// Appelle echo "<br style=\"clear:left;\" />"; si on a affiché 5 images sur la ligne actuelle

				if (($numImage % 5) == 0)
					echo "<br style=\"clear:left;\" />";
			}
		}
	}


}
closedir($rep);

// Affiche un message lorsque le répertoire est vide
if ($numImage == 0) echo "<h3>Aucune image dans le répertoire</h3>";

?>


</body>
