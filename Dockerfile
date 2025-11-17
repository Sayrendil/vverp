# Dockerfile для Laravel приложения
FROM php:8.2-fpm-alpine

# Настройка Alpine репозиториев для более быстрой установки
RUN echo "http://dl-cdn.alpinelinux.org/alpine/v3.19/main" > /etc/apk/repositories && \
    echo "http://dl-cdn.alpinelinux.org/alpine/v3.19/community" >> /etc/apk/repositories

# Установка системных зависимостей (Alpine) - разбито на этапы для кеширования
RUN apk update && \
    apk add --no-cache \
    bash \
    git \
    curl \
    wget \
    zip \
    unzip

# Установка библиотек для PHP расширений
RUN apk add --no-cache \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    libzip-dev \
    freetype-dev \
    libjpeg-turbo-dev

# Установка Node.js и npm отдельным слоем
RUN apk add --no-cache nodejs npm

# Установка PHP расширений
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip

# Установка Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Установка рабочей директории
WORKDIR /var/www

# Установка прав доступа
RUN chown -R www-data:www-data /var/www

# Expose порт 9000 для PHP-FPM
EXPOSE 9000

CMD ["php-fpm"]
