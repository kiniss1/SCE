#!/bin/sh

# Inicia PHP-FPM em background
php-fpm -D

# Aguarda 3 segundos
sleep 3

# Inicia Caddy em foreground
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
