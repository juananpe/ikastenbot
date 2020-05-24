####################
# Base environment #
####################
FROM php:7.2-fpm as base

# Install required packages:
## - git, zip and unzip for Composer
RUN apt-get update && apt-get install --no-install-recommends -y \
    git \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PDO extension to interact with the database
RUN docker-php-ext-install pdo_mysql

# Install Composer
COPY docker/php-fpm/installComposer.sh .
RUN chmod u+x installComposer.sh
RUN bash installComposer.sh
RUN rm installComposer.sh

# Allow running Composer as a super user https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER 1

###########################
# Development environment #
###########################
FROM base as dev

# Set development settings for php-fpm
RUN mv /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini

# Install XDebug and enable remote debugging
RUN pecl install \
    xdebug-2.6.0 \
    && docker-php-ext-enable xdebug
RUN printf '%s\n%s\n%s\n' \
    'xdebug.remote_autostart=1' \
    'xdebug.remote_enable=1' \
    'xdebug.remote_host=10.0.0.1' \
    >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
