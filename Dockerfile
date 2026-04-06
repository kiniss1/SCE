FROM php:8.2-fpm-alpine

# Install required PHP extensions for MySQL (PDO)
RUN docker-php-ext-install pdo pdo_mysql

# Install Caddy
RUN apk add --no-cache caddy

# Copy application files to /app
COPY . /app

# Copy Caddyfile to the expected location
COPY Caddyfile /etc/caddy/Caddyfile

# Expose Railway's standard port
EXPOSE 8080

# Start PHP-FPM and Caddy together
CMD php-fpm -D && caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
