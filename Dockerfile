FROM php:8.3-fpm-alpine

# Zależności systemowe
RUN apk add --no-cache nginx nodejs npm postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql opcache

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Node dependencies + build
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build && composer run-script post-autoload-dump

# Uprawnienia
RUN chown -R www-data:www-data storage bootstrap/cache

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

EXPOSE 10000

CMD php artisan migrate --force && \
    php artisan db:seed --force && \
    php-fpm -D && \
    nginx -g "daemon off;"
