####################
# Base environment #
####################
FROM php:7.2-fpm as base

# Install required packages:
## - git, zip and unzip for Composer
RUN apt-get update && apt-get install --no-install-recommends -y \
    git=1:2.11.0-3+deb9u4 \
    zip=3.0-11+b1 \
    unzip=6.0-21 \
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

# Install XDebug and enable remote debugging
RUN pecl install \
    xdebug-2.6.0 \
    && docker-php-ext-enable xdebug
RUN printf '%s\n%s\n%s\n' \
    'xdebug.remote_autostart=1' \
    'xdebug.remote_enable=1' \
    'xdebug.remote_host=10.0.0.1' \
    >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
