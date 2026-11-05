# Contenedor "app" — servidor de aplicaciones de SiGeRU
FROM php:8.2-apache

# PDO para MySQL y mod_rewrite (lo usan los .htaccess de las APIs)
RUN docker-php-ext-install pdo_mysql \
 && a2enmod rewrite \
 && sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

COPY docker/app/sigeru.conf /etc/apache2/conf-enabled/sigeru.conf
COPY frontend/        /var/www/html/sigeru/frontend/
COPY api-usuarios/    /var/www/html/sigeru/api-usuarios/
COPY api-gestion/     /var/www/html/sigeru/api-gestion/
COPY api-recoleccion/ /var/www/html/sigeru/api-recoleccion/

EXPOSE 80

