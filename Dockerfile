FROM php:8.2.25-fpm-bookworm

ARG UID=1000
ARG GID=1000

RUN groupadd -g ${GID} appgroup \
    && useradd -u ${UID} -g appgroup -m appuser

# Заменяем репозитории на российские зеркала
RUN sed -i 's|deb.debian.org|ftp.ru.debian.org|g' /etc/apt/sources.list.d/debian.sources 2>/dev/null || \
    (echo "deb http://ftp.ru.debian.org/debian bookworm main contrib non-free" > /etc/apt/sources.list && \
     echo "deb http://ftp.ru.debian.org/debian bookworm-updates main contrib non-free" >> /etc/apt/sources.list && \
     echo "deb http://security.debian.org/debian-security bookworm-security main contrib non-free" >> /etc/apt/sources.list)

RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libonig-dev git unzip curl default-mysql-client \
    && curl -sL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo_mysql zip mbstring

RUN npm install -g npm@8 && npm install -g yarn

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

COPY --chown=appuser:appgroup . /var/www

USER appuser

EXPOSE 9000

CMD ["php-fpm"]
