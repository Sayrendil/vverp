#!/bin/bash
# Скрипт для тестирования загрузки Docker образов через разные зеркала

echo "=========================================="
echo "ТЕСТ ЗАГРУЗКИ DOCKER ОБРАЗОВ"
echo "=========================================="
echo ""

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Функция для тестирования pull с таймаутом
test_docker_pull() {
    local image=$1
    local mirror=$2
    local timeout=30

    echo -e "${YELLOW}Тест: $mirror${NC}"
    echo "Образ: $image"
    echo -n "Попытка загрузки (timeout ${timeout}s)... "

    start_time=$(date +%s)

    if timeout $timeout docker pull "$image" > /dev/null 2>&1; then
        end_time=$(date +%s)
        duration=$((end_time - start_time))
        echo -e "${GREEN}✓ Успешно (${duration}s)${NC}"
        return 0
    else
        echo -e "${RED}✗ Не удалось${NC}"
        return 1
    fi
}

echo "1. ТЕСТ БЕЗ ЗЕРКАЛ (по умолчанию)"
echo "=========================================="
# Удаляем образ если есть
docker rmi alpine:latest > /dev/null 2>&1
test_docker_pull "alpine:latest" "Docker Hub (default)"
echo ""

echo "2. ТЕСТ ОСНОВНОГО ОБРАЗА ПРОЕКТА"
echo "=========================================="
docker rmi serversideup/php:8.2-fpm-nginx > /dev/null 2>&1
test_docker_pull "serversideup/php:8.2-fpm-nginx" "Docker Hub"
echo ""

echo "3. ТЕСТ ДРУГИХ НЕОБХОДИМЫХ ОБРАЗОВ"
echo "=========================================="
echo "Nginx Alpine..."
docker rmi nginx:alpine > /dev/null 2>&1
test_docker_pull "nginx:alpine" "Docker Hub"
echo ""

echo "MySQL 8.0..."
docker rmi mysql:8.0 > /dev/null 2>&1
test_docker_pull "mysql:8.0" "Docker Hub"
echo ""

echo "Node 20 Alpine..."
docker rmi node:20-alpine > /dev/null 2>&1
test_docker_pull "node:20-alpine" "Docker Hub"
echo ""

echo "=========================================="
echo "ПРОВЕРКА ТЕКУЩЕЙ КОНФИГУРАЦИИ DOCKER"
echo "=========================================="
echo ""
echo "Docker версия:"
docker --version
echo ""
echo "Registry зеркала (если настроены):"
docker info 2>/dev/null | grep -A 5 "Registry Mirrors" || echo "Зеркала не настроены"
echo ""
echo "DNS настройки Docker:"
docker info 2>/dev/null | grep -A 3 "DNS" || echo "Используется системный DNS"
echo ""

echo "=========================================="
echo "РЕКОМЕНДАЦИИ"
echo "=========================================="
if [ -f "/etc/docker/daemon.json" ]; then
    echo -e "${GREEN}✓ Файл daemon.json существует${NC}"
    echo "Содержимое:"
    cat /etc/docker/daemon.json
else
    echo -e "${YELLOW}⚠ Файл daemon.json не найден${NC}"
    echo "Создайте его для настройки зеркал:"
    echo "sudo nano /etc/docker/daemon.json"
fi
echo ""
