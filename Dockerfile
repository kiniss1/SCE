FROM php:8.2-fpm-alpine

RUN apk add --no-cache caddy
RUN docker-php-ext-install pdo pdo_mysql

# Configurar PHP-FPM para escutar na porta 9000
RUN sed -i 's|listen = /var/run/php-fpm/php-fpm.sock|listen = 127.0.0.1:9000|' /usr/local/etc/php-fpm.d/www.conf

COPY . /app
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80

CMD php-fpm -D && sleep 2 && caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
