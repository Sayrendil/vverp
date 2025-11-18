#!/bin/bash
# Финальный скрипт развертывания на основе результатов тестов
# Используются только проверенные и доступные зеркала

set -e  # Остановка при ошибке

echo "=========================================="
echo "РАЗВЕРТЫВАНИЕ VVERP"
echo "=========================================="
echo ""

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Шаг 1: Настройка Docker Registry зеркал${NC}"
echo "Используем: Google GCR, Timeweb, Huecker (проверены тестами)"

if [ -f "/etc/docker/daemon.json" ]; then
    echo "Создаем резервную копию daemon.json..."
    sudo cp /etc/docker/daemon.json /etc/docker/daemon.json.backup
fi

echo "Применяем новую конфигурацию..."
sudo tee /etc/docker/daemon.json > /dev/null <<'EOF'
{
  "registry-mirrors": [
    "https://mirror.gcr.io",
    "https://dockerhub.timeweb.cloud",
    "https://huecker.io"
  ],
  "dns": ["8.8.8.8", "8.8.4.4", "1.1.1.1"],
  "max-concurrent-downloads": 3,
  "max-concurrent-uploads": 3,
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "10m",
    "max-file": "3"
  },
  "live-restore": true,
  "userland-proxy": false
}
EOF

echo ""
echo -e "${YELLOW}Шаг 2: Перезапуск Docker${NC}"
sudo systemctl restart docker
sleep 3

echo ""
echo -e "${YELLOW}Шаг 3: Проверка конфигурации${NC}"
sudo docker info | grep -A 5 "Registry Mirrors"

echo ""
echo -e "${YELLOW}Шаг 4: Предварительная загрузка базовых образов${NC}"
echo "Загружаем через настроенные зеркала..."

sudo docker pull serversideup/php:8.2-fpm-nginx || {
    echo "Ошибка загрузки PHP образа, но продолжаем..."
}
sudo docker pull nginx:alpine || {
    echo "Ошибка загрузки Nginx образа, но продолжаем..."
}
sudo docker pull mysql:8.0 || {
    echo "Ошибка загрузки MySQL образа, но продолжаем..."
}
sudo docker pull node:20-alpine || {
    echo "Ошибка загрузки Node образа, но продолжаем..."
}

echo ""
echo -e "${YELLOW}Шаг 5: Остановка старых контейнеров${NC}"
sudo docker compose down || true

echo ""
echo -e "${YELLOW}Шаг 6: Сборка приложения${NC}"
echo "Dockerfile использует зеркало Timeweb для APT (проверено тестами)"
sudo docker compose build --no-cache

echo ""
echo -e "${YELLOW}Шаг 7: Запуск контейнеров${NC}"
sudo docker compose up -d

echo ""
echo -e "${GREEN}=========================================="
echo "РАЗВЕРТЫВАНИЕ ЗАВЕРШЕНО!"
echo "==========================================${NC}"
echo ""
echo "Проверка статуса:"
sudo docker compose ps
echo ""
echo "Для просмотра логов:"
echo "sudo docker compose logs -f"
echo ""
echo "Для перезапуска:"
echo "sudo docker compose restart"
