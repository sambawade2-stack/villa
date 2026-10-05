# syntax=docker/dockerfile:1

# ------------------------------------------------------------------ Composer
# --ignore-platform-reqs : cette image ne fait que résoudre et télécharger
# les paquets, elle n'exécute jamais de code applicatif. Les extensions PHP
# réellement requises (pcntl pour Horizon, etc.) sont celles de l'image
# d'exécution ci-dessous, pas celles de composer:2.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist \
    --optimize-autoloader --ignore-platform-reqs

# ------------------------------------------------------------------ Assets
# Compile les assets Vite/Tailwind séparément : le résultat (public/build)
# est copié dans l'image finale, node n'y laisse aucune trace.
# Version exacte, pas "24-slim" : une image flottante embarque une version
# de npm plus récente et plus stricte, qui refuse "package-lock.json" comme
# désynchronisé alors qu'il ne l'est pas (constaté en pratique — npm 11.19
# échoue là où la version de l'hôte, 11.6.2, fonctionne).
FROM node:24.13.0-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources/ resources/
COPY vite.config.js ./
# resources/js/app.js importe Livewire directement depuis le paquet Composer
# (vendor/livewire/livewire/dist/) : sans ce dossier, la compilation échoue.
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ------------------------------------------------------------------ Image d'exécution
# nginx et php-fpm dans le même conteneur (supervisord) : plus simple à
# déployer sur un seul serveur qu'un couple de conteneurs qui doivent
# partager les assets compilés via un volume.
FROM php:8.3-fpm-alpine AS app

RUN apk add --no-cache \
        nginx supervisor postgresql-client \
        icu-libs libzip libpng libwebp freetype \
    && apk add --no-cache --virtual .build-deps \
        postgresql-dev icu-dev libzip-dev libpng-dev libwebp-dev freetype-dev $PHPIZE_DEPS

COPY --from=mlocati/php-extension-installer:latest /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql pgsql redis gd bcmath intl zip opcache pcntl \
    && apk del .build-deps

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

# bootstrap/cache/*.php n'est pas copié depuis l'hôte (.dockerignore) : il y
# référencerait des paquets de dev (laravel-debugbar...) absents ici. On le
# régénère pour ce vendor/ précis, en --no-dev.
RUN php artisan package:discover --ansi

COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# www-data (uid 82) : l'utilisateur déjà configuré par défaut dans le pool
# php-fpm de cette image (/usr/local/etc/php-fpm.d/www.conf). En créer un
# autre sans reconfigurer le pool laisserait php-fpm tourner sous www-data
# sans droit d'écriture sur storage/ — constaté en pratique (tempnam()
# échoue, 500 sur toute page).
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
