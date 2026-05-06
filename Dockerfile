FROM php:8.2-fpm-alpine

RUN apk add --no-cache caddy

RUN docker-php-ext-install pdo pdo_mysql

# PHP-FPM escuta em TCP 9000
RUN echo '[www]' > /usr/local/etc/php-fpm.d/zz-docker.conf && \
    echo 'listen = 127.0.0.1:9000' >> /usr/local/etc/php-fpm.d/zz-docker.conf

COPY . /app
COPY Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

EXPOSE 80

COPY start.sh /start.sh
RUN chmod +x /start.sh

CMD ["/start.sh"]
