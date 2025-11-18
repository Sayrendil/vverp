FROM serversideup/php:8.2-fpm-nginx

USER root

# Настройка зеркал для APT (российские/европейские серверы)
RUN find /etc/apt/sources.list.d/ -type f -exec sed -i 's|deb.debian.org|mirror.yandex.ru/debian|g' {} \; 2>/dev/null || true && \
    sed -i 's|deb.debian.org|mirror.yandex.ru/debian|g' /etc/apt/sources.list 2>/dev/null || true && \
    sed -i 's|security.debian.org|mirror.yandex.ru/debian|g' /etc/apt/sources.list 2>/dev/null || true

# Установка пакетов с использованием зеркал
RUN apt-get update && apt-get install -y supervisor nodejs npm && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
