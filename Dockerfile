FROM serversideup/php:8.2-fpm-nginx

USER root

# Настройка зеркал для APT (российские/европейские серверы)
RUN sed -i 's|http://archive.ubuntu.com|http://mirror.yandex.ru|g' /etc/apt/sources.list || true && \
    sed -i 's|http://security.ubuntu.com|http://mirror.yandex.ru|g' /etc/apt/sources.list || true && \
    sed -i 's|deb.debian.org|mirror.yandex.ru/debian|g' /etc/apt/sources.list || true

# Установка пакетов с использованием зеркал
RUN apt-get update && apt-get install -y supervisor nodejs npm && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
