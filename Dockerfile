FROM dunglas/frankenphp:php8.3

RUN install-php-extensions pdo_pgsql intl zip bcmath pcntl

COPY --chown=www-data:www-data . /app

WORKDIR /app

RUN composer install --no-dev --optimize-autoloader

RUN chmod -R 775 storage bootstrap/cache

EXPOSE 10000

CMD ["php", "-S", "0.0.0.0:10000", "-t", "public"]
