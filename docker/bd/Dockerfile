# Contenedor "bd" — servidor de base de datos (MySQL 8)
FROM mysql:8.0

COPY base-de-datos/base.sql        /docker-entrypoint-initdb.d/01-base.sql
COPY docker/bd/02-usuario-app.sh   /docker-entrypoint-initdb.d/02-usuario-app.sh
