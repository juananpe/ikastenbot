#!/bin/bash

# We need to install dependencies only for Docker
[[ ! -e /.dockerenv ]] && exit 0

set -xe

# Install required packages:
# git for composer
# libzip-dev zip unzip for docker-php-ext
# mysql-client to import the schemas
apt-get update -yqq
apt-get install git libzip-dev zip unzip mysql-client -yqq

# Install Composer and the project's dependencies
EXPECTED_SIGNATURE="$(curl -sS https://composer.github.io/installer.sig)"
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]
then
    >&2 echo 'ERROR: Invalid installer signature'
    rm composer-setup.php
    exit 1
fi

php composer-setup.php --quiet
rm composer-setup.php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
export COMPOSER_ALLOW_SUPERUSER=1
composer install

# Configure the PHP extension to use the installed libzip and install the MySQL
# extension
docker-php-ext-configure zip --with-libzip
docker-php-ext-install pdo_mysql zip

# Import the database schemas
mysql -h mysql -u root -p${MYSQL_ROOT_PASSWORD} 'testdb' < vendor/longman/telegram-bot/structure.sql
mysql -h mysql -u root -p${MYSQL_ROOT_PASSWORD} 'testdb' < src/Migrations/structure.sql
php bin/console doctrine:migrations:migrate --no-interaction
