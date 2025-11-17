# Dockerfile для Laravel приложения
FROM php:8.2-fpm-alpine

# Установка системных зависимостей (Alpine)
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    nodejs \
    npm \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Установка Composer напрямую (без дополнительного образа)
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Установка рабочей директории
WORKDIR /var/www

# Установка прав доступа
RUN chown -R www-data:www-data /var/www

# Expose порт 9000 для PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
