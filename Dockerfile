FROM php:8.2-fpm-alpine

RUN apk add --no-cache caddy supervisor

RUN docker-php-ext-install pdo pdo_mysql

COPY www.conf /usr/local/etc/php-fpm.d/www.conf
COPY supervisord.conf /etc/supervisord.conf
COPY Caddyfile /etc/caddy/Caddyfile
COPY . /app

WORKDIR /app
EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
