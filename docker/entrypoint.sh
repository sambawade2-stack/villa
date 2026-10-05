#!/bin/sh
set -e

# Attend que Postgres accepte les connexions avant de démarrer quoi que ce
# soit : au premier "docker compose up", la base met quelques secondes à
# s'initialiser, et php-fpm/horizon/le planificateur démarreraient sur une
# connexion refusée.
if [ -n "$DB_HOST" ]; then
    echo "En attente de PostgreSQL (${DB_HOST}:${DB_PORT:-5432})..."
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" -d "${DB_DATABASE:-postgres}" >/dev/null 2>&1; do
        sleep 2
    done
fi

# Idempotent : recrée le lien s'il manque, ne touche à rien s'il existe déjà.
php artisan storage:link --force >/dev/null 2>&1 || true

# Les migrations ne sont volontairement PAS lancées ici : les exécuter à
# chaque démarrage de conteneur (donc potentiellement en parallèle sur
# plusieurs réplicas) est risqué. À faire à la main, une fois par déploiement :
#   docker compose exec app php artisan migrate --force

exec "$@"
