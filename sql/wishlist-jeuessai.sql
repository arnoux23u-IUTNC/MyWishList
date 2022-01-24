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
    `last_ip`    varchar(80)  NOT NULL DEFAULT '',
    `is_admin`   tinyint(1)   NOT NULL DEFAULT 0,
    `totp_key`   varchar(255)          DEFAULT NULL UNIQUE,
    `api_key`    varchar(255)          DEFAULT NULL UNIQUE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8;

CREATE TABLE `item`
(
    `id`       int(11) NOT NULL PRIMARY KEY AUTO_INCREMENT,
    `liste_id` int(11)       DEFAULT NULL,
    `nom`      text    NOT NULL,
    `descr`    text          DEFAULT NULL,
    `img`      varchar(255)  DEFAULT NULL UNIQUE,,
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
    `published`   tinyint(1)                           NOT NULL DEFAULT 0,
    `is_public`   tinyint(1)                           NOT NULL DEFAULT 0
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE `cagnotte`
(
    `item_id` int(11)       NOT NULL,
    `montant` decimal(7, 2) NOT NULL,
    `limite`  date DEFAULT NULL,
    PRIMARY KEY (`item_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE `participe`
(
    `cagnotte_itemid` int(11)                              NOT NULL,
    `user_email`      varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `montant`         decimal(7, 2)                        NOT NULL,
    PRIMARY KEY (`cagnotte_itemid`, `user_email`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE `reserve`
(
    `item_id`    int(11)      NOT NULL,
    `user_email` varchar(255) NOT NULL,
    `message`    varchar(255) DEFAULT NULL,
    PRIMARY KEY (`item_id`, `user_email`)
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

CREATE TABLE `messages`
(
    `list_id`    int(11)                              NOT NULL,
    `user_email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `message`    varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    `date`       timestamp                            NOT NULL DEFAULT current_timestamp(),
    PRIMARY KEY (`list_id`, `user_email`, `message`, `date`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE `temporary_waiting_users`
(
    `list_id` int(11)                              NOT NULL,
    `email`   varchar(255) COLLATE utf8_unicode_ci NOT NULL,
    PRIMARY KEY (`list_id`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
CREATE TABLE `passwords_reset`
(
    `token`      varchar(200) NOT NULL,
    `user_id`    int(11)      NOT NULL,
    `expiration` time         NOT NULL DEFAULT '0000-00-00 00:00:00',
    PRIMARY KEY (`token`)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

ALTER TABLE `participe`
    ADD CONSTRAINT `fkparticipe_cagnotte` FOREIGN KEY (`cagnotte_itemid`) REFERENCES `cagnotte` (`item_id`);
ALTER TABLE `cagnotte`
    ADD CONSTRAINT `fkcagnotte_item` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`);
ALTER TABLE `item`
    ADD CONSTRAINT `item_listeidfk` FOREIGN KEY (`liste_id`) REFERENCES `liste` (`no`);
ALTER TABLE `liste`
    ADD CONSTRAINT `liste_useridfk` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`user_id`);
ALTER TABLE `reserve`
    ADD CONSTRAINT `reserve_itemidfk` FOREIGN KEY (`item_id`) REFERENCES `item` (`id`);
ALTER TABLE `totp_rescue_codes`
    ADD CONSTRAINT `totp_useridfk` FOREIGN KEY (`user`) REFERENCES `accounts` (`user_id`);
ALTER TABLE `messages`
    ADD CONSTRAINT `fkmessage_listid` FOREIGN KEY (`list_id`) REFERENCES `liste` (`no`);
ALTER TABLE `passwords_reset`
    ADD CONSTRAINT `fkpassword_userid` FOREIGN KEY (`user_id`) REFERENCES `accounts` (`user_id`);

INSERT INTO `accounts` (`username`, `lastname`, `firstname`, `password`, `mail`, `avatar`, `created_at`, `updated`, `last_login`, `last_ip`, `is_admin`, `totp_key`)
VALUES ('admin', 'admin', 'admin', '$2y$12$od1gC5TZWJGodSmmJwmC3Olwpf/ssKi1rhRnBfSKnjmARqZQSEtwW', 'admin@admin.fr', NULL, '2021-12-21 18:23:50', NULL, '2021-12-23 18:20:04', NULL, 1, NULL),
       ('user', 'user', 'user', '$2y$10$R8Fw2COUhWIQ22y/oY2M2On2snA1ysICUvwxsHRLzpmVQIZzakj.O', 'user@user.fr', NULL, '2021-12-21 18:23:50', NULL, '2021-12-23 19:44:01', NULL, 0, NULL);

INSERT INTO `liste` (`user_id`, `titre`, `description`, `expiration`, `public_key`, `private_key`, `published`, `is_public`)
VALUES (NULL, 'Pour fêter le bac !', 'Pour un week-end à Nancy qui nous fera oublier les épreuves. ', '2018-06-27', 'nosecure1', '$2y$10$Pzc4/hsbV80hay1WwkyFeur59FMvGMOxsA15SyPZKzXtuLPndGAVa', 0, 0),
       (2, 'yapuka', 'Super', '2025-12-10', 'nosecure2', '$2y$10$zKQw9Wlybed46a0fuG1yROGrJQ0yLc52dq3wv1kvyZeZ9Xj3tCJJS', 1, 0),
       (NULL, 'C\'est l\'anniversaire de Charlie', 'Pour lui préparer une fête dont il se souviendra :)', '2027-12-12', 'nosecure3', '$2y$10$ZbpWIWak4vWZKQ9zjgjvgu8NDR9VciPiF1xKqQZtVLfatl2Plny8S', 1, 1);

INSERT INTO `item` (`liste_id`, `nom`, `descr`, `img`, `url`, `tarif`)
VALUES (2, 'Champagne', 'Bouteille de champagne + flutes + jeux &agrave; gratter', 'champagne.jpg', NULL, '20.00'),
       (2, 'Musique', 'Partitions de piano à 4 mains', 'musique.jpg', NULL, '25.00'),
       (2, 'Exposition', 'Visite guidée de l’exposition \'REGARDER\' à la galerie Poirel', 'poirelregarder.jpg', NULL, '14.00'),
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
       (1, 'Fort Aventure',
        'Découvrez Fort Aventure à Bainville-sur-Madon, un site Accropierre unique en Lorraine ! Des Parcours Acrobatiques pour petits et grands, Jeu Mission Aventure, Crypte de Crapahute, Tyrolienne, Saut à l\'élastique inversé, Toboggan géant... et bien plus encore.',
        'fort.jpg', NULL, '25.00');

INSERT INTO `cagnotte` (`item_id`, `montant`, `limite`)
VALUES (4, 45.00, '2018-06-29'),
       (6, 2.00, '2025-12-10'),
       (7, 800.00, '2025-12-12');

INSERT INTO `participe` (`cagnotte_itemid`, `user_email`, `montant`)
VALUES (4, 'user@user.fr', 20.00),
       (6, 'user@user.fr', 0.50),
       (6, 'user2@user.fr', 0.75);

INSERT INTO `reserve` (`item_id`, `user_email`, `message`)
VALUES (7, 'user@user.fr', NULL),
       (8, 'user@user.fr', 'Joyeux Noel');

INSERT INTO `messages` (`list_id`, `user_email`, `message`)
VALUES (2, 'user@user.fr', 'Youhou !'),
       (2, 'user@user.fr', 'Joyeux Noel');

GRANT ALL PRIVILEGES ON wishlist.* TO 'usr_mywishlist'@'localhost' WITH GRANT OPTION;

FLUSH PRIVILEGES;

COMMIT;
