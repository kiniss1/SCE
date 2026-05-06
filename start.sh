#!/bin/sh
set -e

# Inicia PHP-FPM em background
php-fpm -D

# Aguarda PHP-FPM estar pronto
echo "Aguardando PHP-FPM..."
for i in $(seq 1 10); do
    if nc -z 127.0.0.1 9000 2>/dev/null; then
        echo "PHP-FPM pronto!"
        break
    fi
    sleep 1
done

# Inicia Caddy em foreground (mantém o container vivo)
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
