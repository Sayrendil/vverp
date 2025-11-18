#!/bin/bash
# Скрипт для экспорта Docker образов для переноса на production сервер

echo "=== Экспорт Docker образов ==="

# Собираем образы локально
echo "1. Сборка образов..."
docker compose build app queue

# Предварительно загружаем все необходимые образы
echo "2. Загрузка базовых образов..."
docker pull serversideup/php:8.2-fpm-nginx
docker pull nginx:alpine
docker pull mysql:8.0
docker pull node:20-alpine

# Сохраняем образы в tar файлы
echo "3. Экспорт образов в tar файлы..."
docker save -o vverp-app-image.tar vverp-app:latest vverp-queue:latest 2>/dev/null || \
docker save -o vverp-app-image.tar vverp_app:latest vverp_queue:latest 2>/dev/null || \
docker save -o vverp-app-image.tar $(docker images --filter=reference='vverp*' -q)

docker save -o base-images.tar \
  serversideup/php:8.2-fpm-nginx \
  nginx:alpine \
  mysql:8.0 \
  node:20-alpine

echo "4. Готово! Файлы созданы:"
ls -lh *.tar

echo ""
echo "=== Инструкция по переносу ==="
echo "1. Скопируйте файлы *.tar на production сервер:"
echo "   scp *.tar user@server:/home/erp/vverp/"
echo ""
echo "2. На сервере выполните:"
echo "   cd /home/erp/vverp"
echo "   sudo docker load -i base-images.tar"
echo "   sudo docker load -i vverp-app-image.tar"
echo "   sudo docker compose up -d"
