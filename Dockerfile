FROM php:8.2-fpm-alpine

# Instalar Caddy e extensões PHP
RUN apk add --no-cache caddy
RUN docker-php-ext-install pdo pdo_mysql

# Copiar arquivos do projeto
COPY . /app

# Copiar Caddyfile
COPY Caddyfile /etc/caddy/Caddyfile

EXPOSE 80

# Iniciar PHP-FPM e Caddy
CMD php-fpm -D && caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
