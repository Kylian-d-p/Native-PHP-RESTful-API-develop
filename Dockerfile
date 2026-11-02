# Stack d'un hébergement mutualisé : Apache + PHP 8.4 (mod_php)
FROM php:8.4-apache

# - extension PDO MySQL (absente de l'image de base)
# - configuration PHP de production : les erreurs sont journalisées et non affichées dans les réponses JSON
# - racine web sur /public (équivalent de `php -S localhost:8000 -t public`)
RUN docker-php-ext-install pdo_mysql \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's#/var/www/html#/var/www/html/public#' /etc/apache2/sites-available/000-default.conf
