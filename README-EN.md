*Vous pouvez accéder à la version française [ici](README.md)*

                                IUT CHARLEMAGNE - DUT INFORMATIQUE

                                            Year 2022

                                ARNOUX Guillaume - VIGNERON Steven

                                         MyWishList Project

MyWishList is a project allowing to manage wish lists linked to users.

![w3c](https://www.w3.org/Icons/valid-html401)

### Project members :
- ARNOUX Guillaume
- VIGNERON Steven

*****
## Table of contents
1. [Presentation](#presentation)
2. [Installation Instructions](#instructions)
   1. [Required](#required)
   2. [Linux](#unix)
   3. [Windows](#windows)
   4. [Docker](#docker)
3. [Run project](#startup)
*****

<div id="presentation"></div>

# Presentation

### GUI

![en](https://user-images.githubusercontent.com/37373941/150550414-a2498531-5b2c-4cc1-a6ac-ef6b4563a1fe.PNG)

### Live Demos : 
- [WebEtu Server - Private](https://webetu.iutnc.univ-lorraine.fr/www/arnoux23u/mywishlist/?lang=en) (not complete, missing functionalities) ⚠️
- [Personnal Server - Public](https://mywishlist.garnx.fr?lang=en) (working and stable) ✔️

### Project Instructions
Instructions are available [here](docs/wishlist_2018.pdf)

### Board
The Trello board is available [here](https://trello.com/b/2Z3HzkIZ/mywishlist)

---

<div id="instructions"></div>

# Installation Instructions

<div id="required"></div>

## Required

    - PHP 8.0.0 [MYSQL & FPM & GD & MBSTRING & SIMPLEXML Extensions]
    - Composer
    - Git CLI
    - Nginx / Apache2 Webserver
    - Mysql / MariaDB Server

<div id="unix"></div>

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/6124/6124995.png"> Linux :

We suppose PHP and Composer are already installed.

```sh
git clone git@github.com:arnoux23u-IUTNC/MyWishList.git /var/www/mywishlist && cd /var/www/mywishlist
composer install --working-dir=src

echo "CREATE USER 'usr_mywishlist'@'localhost' IDENTIFIED BY 'password';" | mysql
mysql < sql/wishlist.sql

mv src/conf/conf.example.ini src/conf/conf.ini
#Fill the conf.ini file with your credentials
```

### Configurer Nginx
```sh
mv docs/nginx.conf /etc/nginx/sites-available/mywishlist.conf
#Complete the file with your settings
ln -s /etc/nginx/sites-available/mywishlist.conf /etc/nginx/sites-enabled/mywishlist.conf
systemctl restart nginx
```
### Configurer Apache2
```sh
mv docs/apache.conf /etc/apache2/sites-available/mywishlist.conf
#Complete the file with your settings
mv docs/.htaccess public/
a2enmod rewrite && a2enmod ssl && a2ensite mywishlist.conf && systemctl restart apache2
```

<div id="windows"></div>

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/888/888882.png"> Windows : (non recommandé)

For installing MyWishList on Windows, we recommend you to use Docker or use a local development server such as XAMPP or WAMP

We suppose PHP and Composer are already installed.

- Download the last release available [here](https://github.com/arnoux23u-IUTNC/MyWishList/releases/latest/)
- Enable PHP-GD, PHP-MYSQL, PHP-MBSTRING and PHP-SIMPLEXML extensions in XAMPP configuration
- Import the SQL file in XAMPP
- Modify XAMPP configuration to set root directory in folder **public**
- Fill the conf.ini file with your credentials

<div id="docker"></div>

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/919/919853.png"> Docker : (experimental)

We suppose Docker and Docker Compose V1 (>=1.25) installés

Take care to **replace `db_host = localhost` by `db_host = db`** in the conf.ini file

```sh
git clone git@github.com:arnoux23u-IUTNC/MyWishList.git mywishlist
cd mywishlist/
mv src/conf/conf.example.ini src/conf/conf.ini
#Fill the conf file (replace db_host = localhost by db_host = db)

#Fill up the .env file | If you change passwords, please change them in conf.ini too
docker-compose up
```
Server started on port **8090**

<div id="startup"></div>

---
# Runnning the project

## Dataset

By using dataset file, you have 2 accounts, 3 lists and 18 items.

### Accounts

|ID|Username|Password|Email|Administrator|
|-----------|-----------|-----------|-----------|-----------|
|1|admin|admin|admin@admin.fr|Yes|
|2|user|user|user@user.fr|No|

### Listes

|ID|Owner ID|Title|Public token|Private token|Shared|Public|
|---------|---------|---------|---------|---------|---------|---------|
1|NULL|Pour fêter le bac !|nosecure1|list1|No|No|
2|2|yapuka|nosecure2|list2|Yes|No|
3|NULL|C\'est l\'anniversaire de Charlie|nosecure3|list3|Yes|Yes|

### Items

|ID|Associated List ID|Name|Price|Pool amount|Pool expiration|Reserved|
|---------|---------|---------|---------|---------|---------|---------|
1|2|Champagne|20.00|NULL|NULL|No|
2|2|Musique|25.00|NULL|NULL|No|
3|2|Exposition|14.00|NULL|NULL|No|
4|3|Goûter|20.00|45.00|2018-06-29|No|
5|3|Projection|10.00|NULL|NULL|No|
6|2|Bouquet|16.00|2.00|2025-12-10|No|
7|3|Origami|12.00|800.00|2025-12-12|Yes|
8|3|Livres|24.00|NULL|NULL|Yes|
9|2|Diner|59.00|NULL|NULL|No|
10|NULL|Visite guidée|11.00|NULL|NULL|No|
11|2|Bijoux|29.00|NULL|NULL|No|
12|NULL|Jeu contacts|5.00|NULL|NULL|No|
13|NULL|Concert|17.00|NULL|NULL|No|
14|1|Appart Hotel|56.00|NULL|NULL|No|
15|2|Hôtel d\'Haussonville|169.00|NULL|NULL|No|
16|1|Boite de nuit|32.00|NULL|NULL|No|
17|1|Planètes Laser|15.00|NULL|NULL|No|
18|1|Fort Aventure|25.00|NULL|NULL|No|
