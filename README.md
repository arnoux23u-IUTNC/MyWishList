*You can access the english version [here](README-EN.MD)*

# MyWishList

MyWishList est un projet permettant de gérer des listes de souhaits liées à des utilisateurs.

## Table des matières
1. [Présentation](#presentation)
2. [Instructions d'installation](#linkhere)
3. [Configuration du serveur Web](#linkhere)
<!--2. [Instructions d'installation](#linkhere)-->

# Présentation

URLS de test : 

[Serveur WebEtu](https://webetu.iutnc.univ-lorraine.fr/www/arnoux23u/mywishlist/)

[Serveur Personnel](https://mywishlist.garnx.fr/)

# Instructions d'installation

## Préréquis

    - PHP 8.0.0
    - Git CLI
    - Nginx / Apache2 Webserver
    - Mysql / MariaDB Server

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/6124/6124995.png"> Linux :
On suppose PHP et Composer installés
```sh
git clone git@github.com:arnoux23u-IUTNC/MyWishList.git .
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
a2ensite mywishlist.conf && systemctl restart apache2
```

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/888/888882.png"> Windows : (not recommended)

Pour installer MyWishList sous windows, nous vous recommandons d'utiliser Docker ou d'utiliser un serveur de développement local tel que XAMPP ou WAMP

On suppose PHP, Composer et XAMPP installés

- Téléchargez la dernière version disponible ici //TODO METTRE LIEN
- Activer l'extension PHP-GD dans XAMPP
- Modifier la configuration de XAMPP afin de faire pointer le chemin par défaut dans le dossier **public**

```sh
git clone git@github.com:arnoux23u-IUTNC/MyWishList.git .
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
a2ensite mywishlist.conf && systemctl restart apache2
```

## <img height="20px" src="https://cdn-icons-png.flaticon.com/512/919/919853.png"> Docker : (experimental)

Prenez garde à bien **remplacer `db_host = localhost` par `db_host = db`** dans le fichier de configuration

```sh
git clone git@github.com:arnoux23u-IUTNC/MyWishList.git mywishlist
cd mywishlist/
mv src/conf/conf.example.ini src/conf/conf.ini
#Remplir le fichier (Remplacer db_host = localhost par db_host = db)
#Modifier le fichier .env
docker-compose up
```



Default account admin admin created