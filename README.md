# galerie_images
Devoir maison d'école dans lequel il est demandé de pouvoir gérer une galerie d'image, faisant certains traitements PHP à chaque ajout d'image.

- Lors de l'ajout d'une image, les méta-données exif sont lues et stockées dans une base de données.
- La date de la prise de photo est ajoutée en filigrane par-dessus l'image, dans le coin en bas à droite. Cette information est ajoutée au nom de l'image dans la galerie  
- Si l'image dispose de tags de géolocalisation, le serveur émet une requête vers une API de géolocalisation inverse de votre choix (consulter par exemple https://geekflare.com/fr/geolocation-ip-api/) et l'adresse correspondant à la localisation de l'image dans la base de données. Cette information est ajoutée à l'image en bas à gauche
- Vous veillerez à produire un CR à joindre avec le code source, présentant les choix techniques réalisés ainsi que le moyen d'accéder à l'API et les requêtes mises en œuvre. 

## Base de données :
- Créer une base de données "RIA_galerie" (nom préféré, sinon le modifier dans "config.php")
- Importer le fichier "script.sql" dans la base de données
- Modifier les informations de connexion à la base de données dans le fichier "config.php"

## Gitignore :
- Si on doit créer un dossier, l'appeler "moi/" pour qu'il soit ignoré avec le gitignore
- Si on doit créer plusieurs dossiers, rajouter un numéro derrière "moixxx/" (ex : "moi52/")
- Le dossier "tom/" est également ignoré

## Images :
- Pour trouver des images avec métadonnées EXIF, https://github.com/ianare/exif-samples