FROM dunglas/frankenphp:php8.3

RUN install-php-extensions pdo_pgsql intl zip bcmath pcntl opcache

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

COPY --chown=www-data:www-data . /app

WORKDIR /app

RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

RUN composer install --no-dev --optimize-autoloader

# Assets are committed in repo (public/css, public/js, public/vendor); refresh them
# but never fail the build if these maintenance commands error.
RUN php artisan filament:assets || true

EXPOSE 10000

ENV SERVER_NAME=:10000

CMD ["frankenphp", "php-server", "--root", "/app/public", "--listen", "0.0.0.0:10000"]
