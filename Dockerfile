# PHP + Apache image: gives you a real web server with PDO MySQL enabled.
# The app routes via index.php?route=... so no URL rewriting is required.
FROM php:8.2-apache

# Enable the MySQL PDO driver and mod_rewrite (handy for cleaner URLs later).
RUN docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite

COPY . /var/www/html/

# Ensure the upload directory exists and is writable by the web server.
RUN mkdir -p /var/www/html/uploads \
    && chown -R www-data:www-data /var/www/html

EXPOSE 80
