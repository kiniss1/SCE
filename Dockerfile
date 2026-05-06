FROM php:8.2-fpm-alpine

RUN apk add --no-cache caddy

RUN docker-php-ext-install pdo pdo_mysql

# Garantir que PHP-FPM escuta em TCP 9000
COPY www.conf /usr/local/etc/php-fpm.d/www.conf

COPY . /app

COPY Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

EXPOSE 80

CMD sh -c "php-fpm -D && caddy run --config /etc/caddy/Caddyfile --adapter caddyfile"
