FROM composer:2.6 as builder

COPY composer.lock .
COPY composer.json .

RUN composer install \
    --optimize-autoloader \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist

FROM php:8.3-fpm

RUN apt-get update -y \
    && apt-get install -y --no-install-recommends libpq-dev libzip-dev zip libicu-dev procps \
    && docker-php-ext-install pdo_pgsql zip intl bcmath \
    && pecl install redis && docker-php-ext-enable redis \
    && rm -rf /tmp/pear \
    && apt-get clean autoclean \
    && apt-get autoremove --yes \
    && rm -rf /var/lib/{apt,dpkg,cache,log}/

WORKDIR /var/www/html

COPY --from=builder /app/vendor/ ./vendor/

COPY .docker/start.sh /usr/local/bin/start
COPY . .

RUN chown -R www-data:www-data /var/www/ \
  && chmod +x /usr/local/bin/start \
  && chmod 755 -R /var/www/html/storage \
  && chmod 755 -R /var/www/html/bootstrap/cache

CMD ["/usr/local/bin/start"]
