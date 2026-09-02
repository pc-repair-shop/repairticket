FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-req=php

FROM php:8.4-fpm-alpine
RUN apk add --no-cache nginx cups-client libxml2 oniguruma \
    && apk add --no-cache --virtual .build-deps libxml2-dev oniguruma-dev \
    && docker-php-ext-install dom mbstring \
    && apk del .build-deps \
    && mkdir -p /run/nginx /var/www/html /var/www/vendor /data/tickets
COPY --from=vendor /app/vendor/ /var/www/vendor/
COPY public/ /var/www/html/
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh && chown -R www-data:www-data /var/www/html /var/www/vendor /data
EXPOSE 80
VOLUME ["/data"]
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
