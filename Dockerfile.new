FROM serversideup/php:8.2-fpm-nginx

USER root

RUN apt-get update && apt-get install -y supervisor nodejs npm && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
