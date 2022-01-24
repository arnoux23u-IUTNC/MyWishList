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
    `img`      varchar(255)  DEFAULT NULL UNIQUE,
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

GRANT ALL PRIVILEGES ON wishlist.* TO 'usr_mywishlist'@'localhost' WITH GRANT OPTION;

FLUSH PRIVILEGES;

COMMIT;
