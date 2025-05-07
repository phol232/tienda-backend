# Dockerfile
FROM php:8.2-cli

# 1) Instala extensiones necesarias
RUN apt-get update -qq \
 && apt-get install -y --no-install-recommends \
      zip unzip git libonig-dev libzip-dev \
 && docker-php-ext-install pdo_mysql mbstring bcmath \
 && rm -rf /var/lib/apt/lists/*

# 2) Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# 3) Copia solo composer.json y composer.lock para cachear deps
COPY composer.json composer.lock ./

# 4) Instala dependencias SIN disparar scripts (principalmente para no romper por falta de artisan)
RUN composer install \
    --no-dev \
    --no-scripts \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist

# 5) Copia TODO el resto del proyecto (incluye artisan, rutas, public, etc)
COPY . .

# 6) Asegúrate de que haya un .env
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# 7) Ahora sí dispara los scripts de Composer y el package:discover
RUN composer dump-autoload --optimize \
 && php artisan package:discover --ansi

# 8) Ajusta permisos de carpetas de cache y logs
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
