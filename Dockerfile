FROM composer:2 AS dependencies
WORKDIR /project
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
COPY . /var/www/html
COPY --from=dependencies /project/vendor /var/www/html/vendor
RUN mkdir -p /var/www/html/var/otp /var/www/html/var/identity /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html/var/otp /var/www/html/var/identity /var/www/html/public/uploads
