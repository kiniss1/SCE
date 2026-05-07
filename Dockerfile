FROM dunglas/frankenphp:php8.4.20-bookworm

RUN install-php-extensions pdo_mysql mysqli
