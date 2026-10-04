# PHP pour les épreuves des serveurs web (`installation.yml`) : PHP-FPM derrière nginx ou Caddy, ou
# Apache avec PHP, selon VARIANTE. Les extensions exigées par le produit (`Installer::PREREQUIS`) : celles
# qui ne sont pas compilées d'office dans l'image officielle.
ARG PHP=8.5
ARG VARIANTE=fpm
FROM php:${PHP}-${VARIANTE}

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libicu-dev libzip-dev libfreetype-dev libjpeg-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" mysqli gd intl zip \
    && rm -rf /var/lib/apt/lists/*

# Apache : le site sert depuis /var/www/neofrag, et ses `.htaccess` y font la loi — c'est ce que
# promet un hébergement mutualisé. Rien de tout cela sous PHP-FPM.
RUN if command -v a2enmod >/dev/null; then \
        a2enmod rewrite headers \
        && sed -ri 's#/var/www/html#/var/www/neofrag#g' /etc/apache2/sites-available/*.conf \
        && sed -ri 's#<Directory /var/www/>#<Directory /var/www/neofrag/>#; s#AllowOverride None#AllowOverride All#g' /etc/apache2/apache2.conf; \
    fi
