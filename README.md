*You can access the english version [here](README-EN.MD)*

# MyWishList

MyWishList est un projet permettant de gérer des listes de souhaits liées à des utilisateurs.

## Table des matières
1. [Présentation](#presentation)
2. [Instructions d'installation](#linkhere)
3. [Configuration du serveur Web](#linkhere)
<!--2. [Instructions d'installation](#linkhere)-->

# Présentation

### Interface

//TODO PHOTOS ICI

### URLS de test : 

- [Serveur WebEtu](https://webetu.iutnc.univ-lorraine.fr/www/arnoux23u/mywishlist/) (incomplet, certaines fonctionnalités ne sont pas implémentées)
- [Serveur Personnel](https://mywishlist.garnx.fr/) (fonctionnement optimal)

# Instructions d'installation

## Préréquis

    - PHP 8.0.0 [Extensions MYSQL & FPM & GD & MBSTRING & SIMPLEXML]
    - Composer
    - Git CLI
    - Nginx / Apache2 Webserver
    - Mysql / MariaDB Server

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

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/888/888882.png"> Windows : (not recommended)

Pour installer MyWishList sous windows, nous vous recommandons d'utiliser Docker ou d'utiliser un serveur de développement local tel que XAMPP ou WAMP

- Téléchargez la dernière version disponible ici
- Importer votre fichier de configuration dans MySQL
- Installer composer comme avec le système d'exploitation Linux

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
