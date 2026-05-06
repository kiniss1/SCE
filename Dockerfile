FROM php:8.2-fpm-alpine

RUN apk add --no-cache caddy

RUN docker-php-ext-install pdo pdo_mysql

COPY . /app

COPY Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

EXPOSE 80

CMD sh -c "php-fpm && caddy run --config /etc/caddy/Caddyfile --adapter caddyfile"
