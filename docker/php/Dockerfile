FROM php:8.0-fpm
RUN docker-php-ext-install pdo pdo_mysql

RUN apt-get update -y && apt-get install -y zlib1g-dev libpng-dev libzip-dev zip
RUN docker-php-ext-install gd zip

COPY public/ /var/www/mywishlist/public
COPY errors/ /var/www/mywishlist/errors
COPY src/ /var/www/mywishlist/src

#Composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('sha384', 'composer-setup.php') === '906a84df04cea2aa72f40b5f787e49f22d4c2f19492ac310e8cba5b96ac8b64115ac402c8cd292b8a03482574915d1a8') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php
RUN php -r "unlink('composer-setup.php');"
RUN mv composer.phar /usr/local/bin/composer

WORKDIR /var/www/mywishlist
RUN composer install -d src