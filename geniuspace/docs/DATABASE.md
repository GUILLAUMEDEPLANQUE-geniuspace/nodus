# Base de données — MySQL, puis Postgres

Eloquent ne parle pas à un moteur. Changer `.env` suffit.

## Démo sandbox
```
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```

## o2switch / VPS (MySQL) — cible prod
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=geniuspace
DB_USERNAME=...
DB_PASSWORD=...
```
```
php artisan migrate --force
php artisan db:seed --force
```

## Migration vers PostgreSQL
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=geniuspace
DB_USERNAME=...
DB_PASSWORD=...
```
```
php artisan migrate --force
```
Les migrations n’utilisent pas de types SQLite-only. Dump SQL (`mysqldump` / `pg_dump`) + import, ou un outil type `pgloader` mysql→pgsql.

Le God Canvas écrit `nodes`, `edges`, `cck_fields`, `spatial_nodes` — même schéma sur les trois moteurs.
