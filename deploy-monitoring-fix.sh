#!/bin/bash
# Скрипт для обновления кода мониторинга в контейнерах

set -e  # Прервать при ошибке

echo "=== Обновление кода мониторинга в контейнерах ==="

cd /home/erp/vverp

echo ""
echo "1. Копирование обновленных файлов в контейнер vverp_app..."
sudo docker cp app/Jobs/CheckHostAvailability.php vverp_app:/var/www/app/Jobs/
sudo docker cp app/Services/MonitoringService.php vverp_app:/var/www/app/Services/
sudo docker cp app/Http/Controllers/MonitoringController.php vverp_app:/var/www/app/Http/Controllers/
sudo docker cp app/Models/HostAvailabilityLog.php vverp_app:/var/www/app/Models/
sudo docker cp app/Models/Host.php vverp_app:/var/www/app/Models/
sudo docker cp routes/web.php vverp_app:/var/www/routes/
sudo docker cp database/migrations/2025_11_19_175722_create_host_availability_logs_table.php vverp_app:/var/www/database/migrations/

echo ""
echo "2. Копирование обновленных файлов в контейнер vverp_queue..."
sudo docker cp app/Jobs/CheckHostAvailability.php vverp_queue:/var/www/app/Jobs/
sudo docker cp app/Services/MonitoringService.php vverp_queue:/var/www/app/Services/
sudo docker cp app/Models/HostAvailabilityLog.php vverp_queue:/var/www/app/Models/
sudo docker cp app/Models/Host.php vverp_queue:/var/www/app/Models/

echo ""
echo "3. Установка прав на файлы в vverp_app..."
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/app/Jobs/
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/app/Services/
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/app/Http/Controllers/
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/app/Models/
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/routes/

echo ""
echo "4. Установка прав на файлы в vverp_queue..."
sudo docker exec -u root vverp_queue chown -R appuser:appgroup /var/www/app/Jobs/
sudo docker exec -u root vverp_queue chown -R appuser:appgroup /var/www/app/Services/
sudo docker exec -u root vverp_queue chown -R appuser:appgroup /var/www/app/Models/

echo ""
echo "5. Установка iputils-ping в контейнер vverp_queue..."
sudo docker exec -u root vverp_queue bash -c "apt-get update && apt-get install -y iputils-ping" || echo "Ping уже установлен или ошибка установки"

echo ""
echo "6. Проверка доступности ping в контейнере vverp_queue..."
sudo docker exec vverp_queue which ping

echo ""
echo "7. Тестовый ping из контейнера vverp_queue..."
sudo docker exec vverp_queue ping -c 2 10.193.67.1

echo ""
echo "8. Очистка кеша в vverp_app..."
sudo docker exec vverp_app php artisan config:clear
sudo docker exec vverp_app php artisan cache:clear
sudo docker exec vverp_app php artisan route:clear

echo ""
echo "9. Перезапуск контейнера vverp_queue..."
sudo docker restart vverp_queue

echo ""
echo "10. Ожидание запуска контейнера (5 секунд)..."
sleep 5

echo ""
echo "11. Проверка статуса контейнера vverp_queue..."
sudo docker ps | grep vverp_queue

echo ""
echo "12. Проверка логов контейнера vverp_queue..."
sudo docker logs vverp_queue --tail 20

echo ""
echo "13. Проверка healthcheck..."
curl -s http://10.193.0.55/monitoring/healthcheck | jq '.' || curl http://10.193.0.55/monitoring/healthcheck

echo ""
echo "14. Запуск тестовой проверки хостов..."
sudo docker exec vverp_app php artisan monitoring:check-hosts --all

echo ""
echo "=== Готово! ==="
echo ""
echo "Теперь проверьте:"
echo "1. Откройте http://10.193.0.55/monitoring в браузере"
echo "2. Нажмите кнопку 'ПРОВЕРИТЬ СЕЙЧАС'"
echo "3. Обновите страницу через 10-15 секунд"
echo ""
echo "Для просмотра логов в реальном времени:"
echo "tail -f /home/erp/vverp/storage/logs/laravel.log | grep -i 'monitoring\\|host'"
