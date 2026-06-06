FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libicu-dev libzip-dev libcurl4-openssl-dev \
        libonig-dev libxml2-dev libfreetype-dev libjpeg-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" mysqli gd intl mbstring zip curl \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

RUN { \
        echo 'error_reporting=E_ALL'; \
        echo 'display_errors=On'; \
        echo 'display_startup_errors=On'; \
        echo 'log_errors=On'; \
        echo 'error_log=/var/log/php_errors.log'; \
        echo 'memory_limit=256M'; \
        echo 'upload_max_filesize=32M'; \
        echo 'post_max_size=32M'; \
    } > /usr/local/etc/php/conf.d/neofrag-debug.ini

RUN sed -i 's|AllowOverride None|AllowOverride All|g' /etc/apache2/apache2.conf \
    && sed -i 's|^Listen 80$|Listen 8080|' /etc/apache2/ports.conf \
    && sed -i 's|<VirtualHost \*:80>|<VirtualHost *:8080>|' /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

EXPOSE 8080
