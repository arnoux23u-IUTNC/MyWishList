*You can access the english version [here](README-EN.md)*

                                IUT CHARLEMAGNE - DUT INFORMATIQUE

                                            Année 2022

                                ARNOUX Guillaume - VIGNERON Steven

                                         Projet MyWishList

MyWishList est un projet permettant de gérer des listes de souhaits liées à des utilisateurs.

### Membres du projet :
- ARNOUX Guillaume
- VIGNERON Steven

*****
## Table des matières
1. [Présentation](#presentation)
2. [Instructions d'installation](#instructions)
   1. [Préréquis](#required)
   2. [Linux](#unix)
   3. [Windows](#windows)
   4. [Docker](#docker)
3. [Mise en route](#startup)
*****

<div id="presentation"></div>

# Présentation

### Interface

//TODO IMG

### URLS de test : 
- [Serveur WebEtu - Privé](https://webetu.iutnc.univ-lorraine.fr/www/arnoux23u/mywishlist/) (incomplet, certaines fonctionnalités ne sont pas implémentées) ⚠️
- [Serveur Personnel - Public](https://mywishlist.garnx.fr/) (fonctionnel et stable) ✔️

### Sujet
Sujet disponible [ici](docs/wishlist_2018.pdf)

### Tableau de bord
Le tableau de bord Trello du projet est disponible [ici](https://trello.com/b/2Z3HzkIZ/mywishlist)

---

<div id="instructions"></div>

# Instructions d'installation

<div id="required"></div>

## Préréquis

    - PHP 8.0.0 [Extensions MYSQL & FPM & GD & MBSTRING & SIMPLEXML]
    - Composer
    - Git CLI
    - Nginx / Apache2 Webserver
    - Mysql / MariaDB Server

<div id="unix"></div>

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/6124/6124995.png"> Linux :

On suppose PHP et Composer installés

```sh
git clone git@github.com:arnoux23u-IUTNC/MyWishList.git /var/www/mywishlist && cd /var/www/mywishlist
composer install --working-dir=src

echo "CREATE USER 'usr_mywishlist'@'localhost' IDENTIFIED BY 'motdepasse';" | mysql
mysql < sql/wishlist.sql

mv src/conf/conf.example.ini src/conf/conf.ini
#Remplir le fichier
```

### Configurer Nginx
```sh
mv docs/nginx.conf /etc/nginx/sites-available/mywishlist.conf
#Modifier le fichier avec vos informations
ln -s /etc/nginx/sites-available/mywishlist.conf /etc/nginx/sites-enabled/mywishlist.conf
systemctl restart nginx
```
### Configurer Apache2
```sh
mv docs/apache.conf /etc/apache2/sites-available/mywishlist.conf
#Modifier le fichier avec vos informations
mv docs/.htaccess public/
a2enmod rewrite && a2enmod ssl && a2ensite mywishlist.conf && systemctl restart apache2
```

<div id="windows"></div>

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/888/888882.png"> Windows : (non recommandé)

Pour installer MyWishList sous windows, nous vous recommandons d'utiliser Docker ou d'utiliser un serveur de développement local tel que XAMPP ou WAMP

On suppose PHP, Composer et XAMPP installés

- Téléchargez la dernière version disponible [ici](https://github.com/arnoux23u-IUTNC/MyWishList/releases/latest/)
- Activer l'extension PHP-GD, PHP-MYSQL, PHP-MBSTRING et PHP-SIMPLEXML dans XAMPP
- Importer votre fichier de configuration dans MySQL
- Modifier la configuration de XAMPP afin de faire pointer le chemin par défaut dans le dossier **public**
- Remplir le fichier de configuration

<div id="docker"></div>

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/919/919853.png"> Docker : (experimental)

On suppose Docker et Docker Compose V1 (>=1.25) installés

Prenez garde à bien **remplacer `db_host = localhost` par `db_host = db`** dans le fichier de configuration

```sh
git clone git@github.com:arnoux23u-IUTNC/MyWishList.git mywishlist
cd mywishlist/
mv src/conf/conf.example.ini src/conf/conf.ini
#Remplir le fichier (Remplacer db_host = localhost par db_host = db)

#Modifier le fichier .env | Si vous définissez un mot de passe, merci de le compléter également dans le fichier vu précedemment
docker-compose up
```
Le serveur démarre automatiquement sur le port **8090**

<div id="startup"></div>

---
# Mise en route

## Jeu de données

En utilisant le jeu de données, vous disposez de 2 comptes, 3 listes et 18 items.

### Comptes utilisateur

|ID|Nom d'utilisateur|Mot de passe|Adresse email|Adminsitrateur|
|-----------|-----------|-----------|-----------|-----------|
|1|admin|admin|admin@admin.fr|Oui|
|2|user|user|user@user.fr|Non|

### Listes

|ID|Propriétaire (Utilisateur)|Titre|Token public|Token privé|Partagée|Publique|
|---------|---------|---------|---------|---------|---------|---------|
1|NULL|Pour fêter le bac !|nosecure1|list1|Non|Non|
2|2|yapuka|nosecure2|list2|Oui|Non|
3|NULL|C\'est l\'anniversaire de Charlie|nosecure3|list3|Oui|Oui|

### Items

|ID|Liste associée|Nom|Tarif|Montant cagnotte|Expiration cagnotte|Réservé|
|---------|---------|---------|---------|---------|---------|---------|
1|2|Champagne|20.00|NULL|NULL|Non|
2|2|Musique|25.00|NULL|NULL|Non|
3|2|Exposition|14.00|NULL|NULL|Non|
4|3|Goûter|20.00|45.00|2018-06-29|Non|
5|3|Projection|10.00|NULL|NULL|Non|
6|2|Bouquet|16.00|2.00|2025-12-10|Non|
7|3|Origami|12.00|800.00|2025-12-12|Oui|
8|3|Livres|24.00|NULL|NULL|Oui|
9|2|Diner|59.00|NULL|NULL|Non|
10|NULL|Visite guidée|11.00|NULL|NULL|Non|
11|2|Bijoux|29.00|NULL|NULL|Non|
12|NULL|Jeu contacts|5.00|NULL|NULL|Non|
13|NULL|Concert|17.00|NULL|NULL|Non|
14|1|Appart Hotel|56.00|NULL|NULL|Non|
15|2|Hôtel d\'Haussonville|169.00|NULL|NULL|Non|
16|1|Boite de nuit|32.00|NULL|NULL|Non|
17|1|Planètes Laser|15.00|NULL|NULL|Non|
18|1|Fort Aventure|25.00|NULL|NULL|Non|
