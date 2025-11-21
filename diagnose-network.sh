#!/bin/bash
# Диагностика сетевого доступа из контейнера vverp_queue

echo "=== Диагностика сетевого доступа мониторинга ==="
echo ""

echo "1. Проверка наличия ping в контейнере vverp_queue:"
sudo docker exec vverp_queue which ping
if [ $? -eq 0 ]; then
    echo "   ✅ Команда ping найдена"
else
    echo "   ❌ Команда ping НЕ найдена! Установите: sudo docker exec -u root vverp_queue apt-get install -y iputils-ping"
    exit 1
fi
echo ""

echo "2. Получение списка IP хостов из БД:"
HOSTS=$(sudo docker exec vverp_app php artisan tinker --execute="echo App\Models\Host::where('is_active', true)->pluck('ip_address', 'name')->toJson();")
echo "$HOSTS" | jq '.' 2>/dev/null || echo "$HOSTS"
echo ""

echo "3. Тест ping из контейнера vverp_queue к первому хосту (10.193.67.1):"
echo "   Пингуем 10.193.67.1..."
sudo docker exec vverp_queue ping -c 4 -W 3 10.193.67.1
if [ $? -eq 0 ]; then
    echo "   ✅ Хост 10.193.67.1 ДОСТУПЕН"
else
    echo "   ❌ Хост 10.193.67.1 НЕДОСТУПЕН"
fi
echo ""

echo "4. Проверка маршрутизации из контейнера:"
sudo docker exec vverp_queue ip route
echo ""

echo "5. Проверка сетевого интерфейса контейнера:"
sudo docker exec vverp_queue ip addr show
echo ""

echo "6. Тест DNS (если используются hostname):"
sudo docker exec vverp_queue ping -c 2 8.8.8.8
if [ $? -eq 0 ]; then
    echo "   ✅ Интернет доступен"
else
    echo "   ⚠️  Интернет недоступен (это нормально для изолированной сети)"
fi
echo ""

echo "7. Последние 5 логов проверок из БД:"
sudo docker exec vverp_app php artisan tinker --execute="
\$logs = App\Models\HostAvailabilityLog::with('host')
    ->latest('checked_at')
    ->limit(5)
    ->get(['host_id', 'is_available', 'response_time', 'packet_loss', 'error_message', 'checked_at']);
foreach (\$logs as \$log) {
    echo \$log->checked_at . ' | Host #' . \$log->host_id . ' | ' .
         (\$log->is_available ? '✅ UP' : '❌ DOWN') .
         ' | RT: ' . (\$log->response_time ?? 'N/A') . 'ms' .
         ' | Loss: ' . \$log->packet_loss . '%' .
         (\$log->error_message ? ' | Error: ' . \$log->error_message : '') . PHP_EOL;
}
"
echo ""

echo "8. Последние записи в логах Laravel (фильтр по monitoring):"
tail -20 /home/erp/vverp/storage/logs/laravel.log | grep -i "monitoring\|host.*availability\|ping\|warning\|error"
echo ""

echo "9. Проверка прав на ping:"
sudo docker exec vverp_queue ls -la /bin/ping
echo ""

echo "10. Проверка capabilities для ping:"
sudo docker exec vverp_queue getcap /bin/ping 2>/dev/null || echo "   getcap не установлен (это нормально)"
echo ""

echo "=== Тест выполнения Job вручную ==="
echo "Запускаем синхронную проверку первого хоста..."
sudo docker exec vverp_app php artisan tinker --execute="
\$host = App\Models\Host::where('is_active', true)->first();
if (\$host) {
    echo 'Проверяем хост: ' . \$host->name . ' (' . \$host->ip_address . ')' . PHP_EOL;
    App\Jobs\CheckHostAvailability::dispatchSync(\$host->id);
    echo 'Проверка завершена. Смотрите результат выше.' . PHP_EOL;
} else {
    echo 'Нет активных хостов' . PHP_EOL;
}
"
echo ""

echo "=== Конец диагностики ==="
echo ""
echo "Если ping работает с хоста, но не работает из контейнера - возможные причины:"
echo "1. Firewall блокирует ICMP из docker сети"
echo "2. Docker network изолирован от хост-сети"
echo "3. Нужны дополнительные capabilities для контейнера"
echo ""
echo "Решения:"
echo "1. Добавить --cap-add=NET_RAW в docker-compose.yml для vverp_queue"
echo "2. Использовать network_mode: host для контейнера"
echo "3. Настроить iptables для разрешения ICMP из docker сети"
