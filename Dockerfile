FROM php:8.2-fpm

ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} appgroup \
    && useradd -u ${UID} -g appgroup -m appuser

RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libonig-dev \
    git unzip curl default-mysql-client ca-certificates \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql zip mbstring exif pcntl bcmath

RUN npm install -g npm@latest

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/html

RUN chown -R appuser:appgroup /var/www/html

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
