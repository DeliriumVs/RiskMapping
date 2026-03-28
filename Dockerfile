# Utilisation de l'image officielle PHP avec Apache
FROM php:8.2-apache

# Installation des extensions nécessaires pour communiquer avec MariaDB
RUN docker-php-ext-install pdo pdo_mysql

# Activation du module rewrite d'Apache (pratique si tu veux de belles URLs plus tard)
RUN a2enmod rewrite

# On s'assure que les droits sont corrects pour le dossier web
RUN chown -R www-data:www-data /var/www/html
