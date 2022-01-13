SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

DROP DATABASE IF EXISTS `wishlist`;
CREATE DATABASE `wishlist` DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;
USE `wishlist`;

CREATE TABLE `accounts`
(
    `user_id`    int(11)      NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `username`   varchar(20)  NOT NULL UNIQUE,
    `lastname`   varchar(40)  NOT NULL,
    `firstname`  varchar(40)  NOT NULL,
    `password`   varchar(255) NOT NULL,
    `mail`       varchar(100) NOT NULL UNIQUE,
    `avatar`     varchar(50)           DEFAULT NULL,
    `created_at` timestamp    NOT NULL DEFAULT current_timestamp(),
    `updated`    timestamp    NULL     DEFAULT NULL,
    `last_login` timestamp    NULL     DEFAULT NULL,
    `last_ip`    bigint(20)   NOT NULL,
    `is_admin`   tinyint(1)   NOT NULL DEFAULT 0,
    `totp_key`   varchar(255)          DEFAULT NULL UNIQUE,
    `api_key`   varchar(255)           DEFAULT NULL UNIQUE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `item`
(
    `id`       int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `liste_id` int(11)       DEFAULT NULL,
    `nom`      text    NOT NULL,
    `descr`    text          DEFAULT NULL,
    `img`      text          DEFAULT NULL UNIQUE,
    `url`      text          DEFAULT NULL,
    `tarif`    decimal(5, 2) DEFAULT NULL
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE `liste`
(
    `no`          int(11)                              NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `user_id`     int(11)                                       DEFAULT NULL,
    `titre`       varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `description` text COLLATE utf8_unicode_ci                  DEFAULT NULL,
    `expiration`  date                                          DEFAULT NULL,
    `public_key`  varchar(255) COLLATE utf8_unicode_ci          DEFAULT NULL,
    `private_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `published`   tinyint(1)                           NOT NULL DEFAULT 0
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE `reserve`
(
    `item_id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `message` text    NOT NULL,
    PRIMARY KEY (`item_id`, `user_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `totp_rescue_codes`
(
    `user`       int(11)   NOT NULL,
    `code`       int(8)    NOT NULL UNIQUE,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`user`, `code`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `temporary_waiting_users`
(
    `data_id` int(11)                              NOT NULL,
    `type`    int(1)                               NOT NULL,
    `email`   varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (`data_id`, `type`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

ALTER TABLE `item`
    ADD CONSTRAINT `item_listeidfk` FOREIGN KEY (`liste_id`) REFERENCES `liste` (`no`);
ALTER TABLE `liste`
    ADD CONSTRAINT `liste_useridfk` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`user_id`);
ALTER TABLE `reserve`
    ADD CONSTRAINT `reserve_useridfk` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`user_id`);
ALTER TABLE `reserve`
    ADD CONSTRAINT `reserve_itemidfk` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`);
ALTER TABLE `totp_rescue_codes`
    ADD CONSTRAINT `totp_useridfk` FOREIGN KEY (`user`) REFERENCES `accounts` (`user_id`);

INSERT INTO `accounts` (`username`, `lastname`, `firstname`, `password`, `mail`, `avatar`, `created_at`, `updated`, `last_login`, `last_ip`, `is_admin`, `totp_key`) VALUES
('admin', 'admin', 'admin', '$2y$12$od1gC5TZWJGodSmmJwmC3Olwpf/ssKi1rhRnBfSKnjmARqZQSEtwW', 'arnouxguillaume54@hotmail.fr', NULL, '2021-12-21 18:23:50', NULL, '2021-12-23 18:20:04', NULL, 1, NULL),
('guigz', 'ARNOUX', 'Guillaume', '$2y$12$od1gC5TZWJGodSmmJwmC3Olwpf/ssKi1rhRnBfSKnjmARqZQSEtwW', 'arnouxguillaume54@gmail.com', NULL, '2021-12-21 18:23:50', NULL, '2021-12-23 19:44:01', NULL, 0, NULL);

INSERT INTO `liste` (`user_id`, `titre`, `description`, `expiration`, `public_key`, `private_key`, `published`) VALUES
(NULL, 'Pour fêter le bac !', 'Pour un week-end à Nancy qui nous fera oublier les épreuves. ', '2018-06-27', 'nosecure1', '$2y$12$cZTnuOiMTg4tybmTxGCAU.eSIh65U2E87dT6gxnehBLQArs15/rSW', 0),
(2, 'yapuka', 'Super', '2025-12-10', 'no', '$2y$12$cZTnuOiMTg4tybmTxGCAU.eSIh65U2E87dT6gxnehBLQArs15/rSW', 1),
(NULL, 'C\'est l\'anniversaire de Charlie', 'Pour lui préparer une fête dont il se souviendra :)', '2017-12-12', 'nosecure3', 'jdfl', 0);

INSERT INTO `item` (`liste_id`, `nom`, `descr`, `img`, `url`, `tarif`) VALUES
(2, 'Champagne', 'Bouteille de champagne + flutes + jeux &agrave; gratter', 'champagne.jpg', NULL, '20.00'),
(2, 'Musique', 'Partitions de piano à 4 mains', 'musique.jpg', NULL, '25.00'),
(2, 'Exposition', 'Visite guidée de l’exposition ‘REGARDER’ à la galerie Poirel', 'poirelregarder.jpg', NULL, '14.00'),
(3, 'Goûter', 'Goûter au FIFNL', 'gouter.jpg', NULL, '20.00'),
(3, 'Projection', 'Projection courts-métrages au FIFNL', 'film.jpg', NULL, '10.00'),
(2, 'Bouquet', 'Bouquet de roses et Mots de Marion Renaud', 'rose.jpg', NULL, '16.00'),
(3, 'Origami', 'Baguettes magiques en Origami en buvant un thé', 'origami.jpg', NULL, '12.00'),
(3, 'Livres', 'Livre bricolage avec petits-enfants + Roman', 'bricolage.jpg', NULL, '24.00'),
(2, 'Diner  Grand Rue ', 'Diner au Grand’Ru(e) (Apéritif / Entrée / Plat / Vin / Dessert / Café)', 'grandrue.jpg', NULL, '59.00'),
(NULL, 'Visite guidée', 'Visite guidée personnalisée de Saint-Epvre jusqu’à Stanislas', 'place.jpg', NULL, '11.00'),
(2, 'Bijoux', 'Bijoux de manteau + Sous-verre pochette de disque + Lait après-soleil', 'bijoux.jpg', NULL, '29.00'),
(NULL, 'Jeu contacts', 'Jeu pour échange de contacts', 'contact.png', NULL, '5.00'),
(NULL, 'Concert', 'Un concert à Nancy', 'concert.jpg', NULL, '17.00'),
(1, 'Appart Hotel', 'Appart’hôtel Coeur de Ville, en plein centre-ville', 'apparthotel.jpg', NULL, '56.00'),
(2, 'Hôtel d\'Haussonville', 'Hôtel d\'Haussonville, au coeur de la Vieille ville à deux pas de la place Stanislas', 'hotel_haussonville_logo.jpg', NULL, '169.00'),
(1, 'Boite de nuit', 'Discothèque, Boîte tendance avec des soirées à thème & DJ invités', 'boitedenuit.jpg', NULL, '32.00'),
(1, 'Planètes Laser', 'Laser game : Gilet électronique et pistolet laser comme matériel, vous voilà équipé.', 'laser.jpg', NULL, '15.00'),
(1, 'Fort Aventure', 'Découvrez Fort Aventure à Bainville-sur-Madon, un site Accropierre unique en Lorraine ! Des Parcours Acrobatiques pour petits et grands, Jeu Mission Aventure, Crypte de Crapahute, Tyrolienne, Saut à l\'élastique inversé, Toboggan géant... et bien plus encore.', 'fort.jpg', NULL, '25.00');


GRANT ALL PRIVILEGES ON wishlist.* TO 'usr_mywishlist'@'localhost' WITH GRANT OPTION;

FLUSH PRIVILEGES;

COMMIT;