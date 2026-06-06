FROM php:8.3-apache

# System deps + PHP extensions (intl, zip for Filament; pdo_pgsql for Postgres)
RUN apt-get update && apt-get install -y \
    git curl libpq-dev libonig-dev libxml2-dev libicu-dev libzip-dev zip unzip \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath intl zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Apache: enable rewrite, point docroot at Laravel's public/, listen on Render's port 10000
RUN a2enmod rewrite
RUN sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -ri 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf
RUN sed -ri 's/Listen 80/Listen 10000/' /etc/apache2/ports.conf \
    && sed -ri 's/:80>/:10000>/' /etc/apache2/sites-available/000-default.conf

# Allow .htaccess overrides in public/
RUN printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

RUN composer install --no-dev --optimize-autoloader

# Refresh Filament assets (also committed in repo as a fallback)
RUN php artisan filament:assets || true

EXPOSE 10000

CMD php artisan migrate --force || true; php artisan db:seed --class=StorefrontPromoSeeder --force || true; apache2-foreground
