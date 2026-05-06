FROM php:8.2-fpm-alpine

RUN apk add --no-cache caddy

RUN docker-php-ext-install pdo pdo_mysql

# Sobrescrever configuração do PHP-FPM com arquivo novo
RUN echo '[global]' > /usr/local/etc/php-fpm.conf && \
    echo 'daemonize = yes' >> /usr/local/etc/php-fpm.conf && \
    echo '[www]' >> /usr/local/etc/php-fpm.conf && \
    echo 'user = nobody' >> /usr/local/etc/php-fpm.conf && \
    echo 'group = nobody' >> /usr/local/etc/php-fpm.conf && \
    echo 'listen = 127.0.0.1:9000' >> /usr/local/etc/php-fpm.conf && \
    echo 'pm = dynamic' >> /usr/local/etc/php-fpm.conf && \
    echo 'pm.max_children = 5' >> /usr/local/etc/php-fpm.conf && \
    echo 'pm.start_servers = 2' >> /usr/local/etc/php-fpm.conf && \
    echo 'pm.min_spare_servers = 1' >> /usr/local/etc/php-fpm.conf && \
    echo 'pm.max_spare_servers = 3' >> /usr/local/etc/php-fpm.conf

COPY . /app
COPY Caddyfile /etc/caddy/Caddyfile

WORKDIR /app

EXPOSE 80

COPY start.sh /start.sh
RUN chmod +x /start.sh
CMD ["/start.sh"]
