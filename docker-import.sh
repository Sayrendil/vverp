#!/bin/bash
# Скрипт для импорта Docker образов на production сервере

echo "=== Импорт Docker образов ==="

if [ ! -f "base-images.tar" ] || [ ! -f "vverp-app-image.tar" ]; then
    echo "Ошибка: Файлы *.tar не найдены!"
    echo "Убедитесь, что вы находитесь в директории с tar файлами"
    exit 1
fi

echo "1. Загрузка базовых образов..."
sudo docker load -i base-images.tar

echo "2. Загрузка образов приложения..."
sudo docker load -i vverp-app-image.tar

echo "3. Список загруженных образов:"
sudo docker images

echo ""
echo "=== Готово! ==="
echo "Теперь можно запустить контейнеры:"
echo "sudo docker compose up -d"
