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
    `last_ip`    int(20)      NOT NULL,
    `is_admin`   tinyint(1)   NOT NULL DEFAULT 0,
    `totp_key`   varchar(255)          DEFAULT NULL UNIQUE
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
    `email`   varchar(255) COLLATE utf8_unicode_ci NOT NULL
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

INSERT INTO `accounts` (`username`, `lastname`, `firstname`, `password`, `mail`, `avatar`, `last_ip`, `is_admin`, `totp_key`)
VALUES ('admin', 'ADMINISTRATOR', 'ADMINISTRATOR', '$2y$12$od1gC5TZWJGodSmmJwmC3Olwpf/ssKi1rhRnBfSKnjmARqZQSEtwW', 'admin@mail.com', NULL, 0, 1, NULL);

GRANT ALL PRIVILEGES ON wishlist.* TO 'usr_mywishlist'@'localhost';

FLUSH PRIVILEGES;

COMMIT;