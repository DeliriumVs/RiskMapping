# Dockerfile

# 1. IMAGE PINNING (Immuabilité)
# On fixe la version exacte via son empreinte SHA-256 (Ici une empreinte récente de php:8.2-apache)
# Pour trouver le SHA actuel : docker inspect --format='{{index .RepoDigests 0}}' php:8.2-apache
FROM php:8.2-apache@sha256:f74ec0b41f6c853ce206ee7f15cde27c83638426abe66baf922d4df18868f67b

# 2. OPTIMISATION DES COUCHES (Layers)
# On regroupe l'update, l'installation des extensions et le nettoyage dans un seul "RUN"
# Cela évite que les fichiers temporaires ne gonflent le poids final de l'image.
RUN apt-get update && apt-get install -y \
    libmariadb-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# 3. CONFIGURATION APACHE
# Activation du module rewrite pour de futures URL propres si besoin
RUN a2enmod rewrite

# 4. DURCISSEMENT PHP (Security Headers & Masquage de la version)
# On désaffiche la version de PHP dans les requêtes réseau (X-Powered-By)
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini

# 5. COPIE DU CODE SOURCE
COPY src/ /var/www/html/

# 6. SÉCURISATION DES DROITS (Moindre Privilège)
# On s'assure que le serveur web (www-data) est propriétaire des fichiers
# mais qu'il ne tourne pas avec les droits "root"
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
