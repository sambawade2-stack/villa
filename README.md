# Petite Côte Villas

Marketplace de location de villas haut de gamme sur la Petite Côte sénégalaise
(Saly, Mbour, Ngaparou, Somone, Popenguine, Joal-Fadiouth).

L'administration est centralisée : un seul administrateur enregistre les propriétaires,
publie les villas et gère les réservations. Les propriétaires n'ont pas de compte en v1 —
voir `docs/architecture.html` pour la trajectoire vers un portail propriétaire.

## Pile

Laravel 13 · PHP 8.3 · PostgreSQL 16 · Redis 7 · Livewire 4 · Tailwind 4 · Alpine · Vite

## Installation

```bash
# Extensions PHP requises
sudo apt install -y php8.3-pgsql php8.3-bcmath php8.3-redis

# Base de données
sudo -u postgres psql -c "CREATE ROLE villa LOGIN PASSWORD '<mot_de_passe>' CREATEDB;"
sudo -u postgres psql -c "CREATE DATABASE petite_cote_villas OWNER villa ENCODING 'UTF8';"
sudo -u postgres psql -d petite_cote_villas -c "CREATE EXTENSION IF NOT EXISTS btree_gist;"

# Base de test (les tests tournent sur PostgreSQL, pas SQLite)
sudo -u postgres createdb -O villa petite_cote_villas_test
sudo -u postgres psql -d petite_cote_villas_test -c "CREATE EXTENSION IF NOT EXISTS btree_gist;"

composer install
npm install
cp .env.example .env && php artisan key:generate
# renseigner DB_PASSWORD dans .env
php artisan migrate
npm run build
```

`btree_gist` n'est pas optionnel : la contrainte `EXCLUDE USING gist` sur
`availability_blocks` en dépend, et c'est elle qui rend la double réservation impossible.

## Développement

```bash
php artisan serve      # http://localhost:8000
npm run dev            # Vite
php artisan horizon    # files d'attente Redis
./vendor/bin/pest      # tests
./vendor/bin/pint      # style
```

## Conventions

- **Montants** : le franc CFA (XOF) n'a pas de sous-unité. Tout montant est un entier
  de francs stocké en `bigint`. Jamais de `float`.
- **Disponibilité** : arbitrée par PostgreSQL (`daterange` + contrainte d'exclusion),
  jamais par une vérification PHP seule.
- **Prix** : toujours recalculés côté serveur. Un montant venu du navigateur n'est pas une source.
- **Traductions** : champs traduisibles en `jsonb` (`{"fr": …, "en": …}`), textes dans `lang/`.
