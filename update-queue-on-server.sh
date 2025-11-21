#!/bin/bash
# Скрипт для обновления контейнера очереди на production сервере

set -e

echo "=== Обновление контейнера vverp_queue на production ==="

cd /home/erp/vverp

echo ""
echo "1. Получение нового образа из registry..."
sudo docker pull ghcr.io/sayrendil/vverp-queue:latest

echo ""
echo "2. Остановка старого контейнера..."
sudo docker-compose -f docker-compose.prod.yml stop queue

echo ""
echo "3. Удаление старого контейнера..."
sudo docker-compose -f docker-compose.prod.yml rm -f queue

echo ""
echo "4. Запуск нового контейнера..."
sudo docker-compose -f docker-compose.prod.yml up -d queue

echo ""
echo "5. Ожидание запуска (5 секунд)..."
sleep 5

echo ""
echo "6. Проверка статуса контейнера..."
sudo docker ps | grep vverp_queue

echo ""
echo "7. Проверка что ping доступен..."
sudo docker exec vverp_queue which ping

echo ""
echo "8. Тестовый ping..."
sudo docker exec vverp_queue ping -c 2 10.193.67.1

echo ""
echo "9. Просмотр логов контейнера..."
sudo docker logs vverp_queue --tail 20

echo ""
echo "✅ Контейнер успешно обновлен!"
echo ""
echo "Проверьте мониторинг: http://10.193.0.55/monitoring"
