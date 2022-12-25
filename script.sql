-- Preferred DB name : "RIA_galerie"

CREATE TABLE `REPERTOIRE` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `nom` varchar(50)
);

CREATE TABLE `IMAGE` (
  `id` int PRIMARY KEY AUTO_INCREMENT,
  `repertoire` int,
  `nom` varchar(50)
);

CREATE TABLE `METADONNEE` (
  `image` int,
  `cle` varchar(50),
  `valeur` varchar(100),
  PRIMARY KEY (`image`, `cle`)
);

ALTER TABLE `IMAGE` ADD FOREIGN KEY (`repertoire`) REFERENCES `REPERTOIRE` (`id`);

ALTER TABLE `METADONNEE` ADD FOREIGN KEY (`image`) REFERENCES `IMAGE` (`id`);
