FROM php:5.6-cli
MAINTAINER Rémi Alvergnat <toilal.dev@gmail.com>

RUN apt-get update && apt-get install git zip unzip libsqlite3-dev libpq-dev -y && rm -rf /var/lib/apt/lists/*
RUN docker-php-ext-install pdo pdo_sqlite pdo_pgsql pdo_mysql

RUN if [ ! -z "$http_proxy" ]; then pear config-set http_proxy ${http_proxy}; fi;

RUN yes | pecl install xdebug-2.5.5 \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

# Register the COMPOSER_HOME environment variable
ENV COMPOSER_HOME /composer

# Add global binary directory to PATH and make sure to re-export it
ENV PATH /composer/vendor/bin:$PATH

# Allow Composer to be run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Setup the Composer installer
RUN curl -o /tmp/composer-setup.php https://getcomposer.org/installer \
  && curl -o /tmp/composer-setup.sig https://composer.github.io/installer.sig \
  && php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }"

# Install Composer
RUN php /tmp/composer-setup.php --no-ansi --install-dir=/usr/local/bin --filename=composer --snapshot && rm -rf /tmp/composer-setup.php

# Github token
RUN composer config -g github-oauth.github.com db14c113decece351f1e20cf012ce0b3c8612cd5

# Install prestissimo
RUN composer global require hirak/prestissimo

# Fix write permissions on Linux/Mac
RUN usermod -u ${HOST_UID:-1000} www-data
RUN groupmod -g ${HOST_GID:-1000} www-data
RUN chown -R www-data:www-data $COMPOSER_HOME

# Add composer cache to a volume
RUN mkdir -p /composer/cache && chown -R www-data:www-data /composer/cache
VOLUME /composer/cache
