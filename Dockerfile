# Estructura monolítica: PHP + Nginx + Node
FROM php:8.2-fpm-alpine

# 1. Instalar dependencias del sistema
RUN apk add --no-cache \
    nginx \
    libpng-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    zip \
    unzip \
    curl \
    nodejs \
    npm \
    supervisor

# 2. Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql bcmath gd zip mbstring

# 3. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Configurar directorio de trabajo
WORKDIR /var/www

# 5. Copiar SOLO los archivos de dependencias primero (cache de capas)
COPY composer.json composer.lock package.json ./

# 6. Instalar dependencias PHP
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts \
    --prefer-dist

# 7. Copiar el resto del código
COPY . .

# 8. Ejecutar scripts post-install de Laravel (después de copiar todo el código)
RUN composer run-script post-autoload-dump || true

# 9. Instalar JS y compilar Frontend
RUN npm install && npm run build

# 10. Configurar Nginx
COPY ./docker/nginx.conf /etc/nginx/http.d/default.conf

# 11. Configurar Supervisor para gestionar PHP-FPM + Nginx
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# 12. Permisos de Laravel
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions \
        storage/framework/views bootstrap/cache && \
    chown -R www-data:www-data /var/www && \
    chmod -R 775 /var/www/storage /var/www/bootstrap/cache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]