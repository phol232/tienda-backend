FROM php:8.2-fpm

# Instala dependencias y extensiones necesarias
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql zip

# Instala y configura Opcache para mejorar rendimiento
RUN docker-php-ext-install opcache
COPY docker/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Instala Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuración para mejor rendimiento
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory-limit.ini

# Directorio de trabajo
WORKDIR /var/www/html

# Copia los archivos de composer primero para aprovechar la caché
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader

# Copia el resto de la aplicación
COPY . .

# Finaliza la instalación de Composer
RUN composer dump-autoload --optimize

# Configura permisos
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Optimiza Laravel
RUN php artisan optimize
RUN php artisan config:cache
RUN php artisan route:cache

EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
