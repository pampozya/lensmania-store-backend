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

# Publish Filament + Livewire assets into public/ (must succeed, not swallowed)
RUN php artisan filament:assets \
    && php artisan vendor:publish --tag=livewire:assets --force \
    && php artisan config:clear

EXPOSE 10000

CMD ["frankenphp", "php-server", "--root", "/app/public", "--listen", ":10000", "-v"]
