# Dockerfile для Laravel приложения - используем готовый образ Sail
FROM laravelsail/php83-composer:latest

# Установка supervisor для управления процессами
RUN apt-get update && apt-get install -y supervisor && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

# Установка рабочей директории
WORKDIR /var/www/html

# Установка прав доступа
RUN chown -R sail:sail /var/www/html

# Expose порт 9000 для PHP-FPM
EXPOSE 9000

USER sail

CMD ["php-fpm"]
