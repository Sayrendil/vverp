#!/bin/bash
# Скрипт для проверки доступности Docker Registry зеркал и APT репозиториев

echo "=========================================="
echo "ТЕСТ ДОСТУПНОСТИ ЗЕРКАЛ И РЕПОЗИТОРИЕВ"
echo "=========================================="
echo ""

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Функция для проверки HTTP доступности
check_url() {
    local url=$1
    local name=$2
    local timeout=5

    echo -n "Проверка $name... "
    if curl -s -m $timeout -o /dev/null -w "%{http_code}" "$url" | grep -q "200\|302\|301"; then
        echo -e "${GREEN}✓ Доступен${NC}"
        return 0
    else
        echo -e "${RED}✗ Недоступен${NC}"
        return 1
    fi
}

# Функция для проверки DNS
check_dns() {
    local host=$1
    local name=$2

    echo -n "DNS резолв $name... "
    if host "$host" > /dev/null 2>&1 || nslookup "$host" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ OK${NC}"
        return 0
    else
        echo -e "${RED}✗ Не резолвится${NC}"
        return 1
    fi
}

# Функция для проверки пинга
check_ping() {
    local host=$1
    local name=$2

    echo -n "Ping $name... "
    if ping -c 1 -W 2 "$host" > /dev/null 2>&1; then
        echo -e "${GREEN}✓ Отвечает${NC}"
        return 0
    else
        echo -e "${RED}✗ Не отвечает${NC}"
        return 1
    fi
}

echo "1. ПРОВЕРКА СЕТЕВОГО ПОДКЛЮЧЕНИЯ"
echo "=========================================="
check_dns "google.com" "Google DNS"
check_ping "8.8.8.8" "Google DNS IP"
check_dns "docker.io" "Docker Hub"
check_dns "registry-1.docker.io" "Docker Registry"
echo ""

echo "2. ПРОВЕРКА DOCKER REGISTRY ЗЕРКАЛ"
echo "=========================================="
check_url "https://registry-1.docker.io/v2/" "Docker Hub Official"
check_url "https://mirror.gcr.io" "Google Container Registry Mirror"
check_url "https://dockerhub.timeweb.cloud" "Timeweb Docker Mirror"
check_url "https://huecker.io" "Huecker Mirror"
check_url "https://docker.mirrors.ustc.edu.cn" "USTC China Mirror"
echo ""

echo "3. ПРОВЕРКА APT ЗЕРКАЛ (Debian)"
echo "=========================================="
check_url "http://deb.debian.org/debian/" "Debian Official"
check_url "http://mirror.yandex.ru/debian/" "Yandex Mirror"
check_url "http://ftp.ru.debian.org/debian/" "Debian Russia"
check_url "http://ftp.de.debian.org/debian/" "Debian Germany"
check_url "http://mirror.timeweb.ru/debian/" "Timeweb Mirror"
check_url "http://mirror.docker.ru/debian/" "Docker.ru Mirror"
echo ""

echo "4. ПРОВЕРКА CLOUDFLARE CDN"
echo "=========================================="
check_dns "production.cloudflare.docker.com" "Cloudflare Docker CDN"
check_url "https://cloudflare.docker.com" "Cloudflare Docker"
echo ""

echo "5. ПРОВЕРКА ПОПУЛЯРНЫХ CDN"
echo "=========================================="
check_url "https://cdn.jsdelivr.net" "jsDelivr CDN"
check_url "https://unpkg.com" "unpkg CDN"
check_url "https://cdnjs.cloudflare.com" "cdnjs"
echo ""

echo "6. ПРОВЕРКА СКОРОСТИ ЗАГРУЗКИ"
echo "=========================================="
echo "Тест скорости с Docker Hub (10 сек)..."
timeout 10 curl -o /dev/null https://registry-1.docker.io/v2/ 2>&1 | grep -E "speed|time" || echo "Docker Hub доступен"

echo ""
echo "Тест скорости с Debian репо (10 сек)..."
timeout 10 curl -o /dev/null http://deb.debian.org/debian/dists/trixie/Release 2>&1 | grep -E "speed|time" || echo "Debian репо доступен"

echo ""
echo "=========================================="
echo "РЕКОМЕНДАЦИИ"
echo "=========================================="
echo ""
echo "На основе результатов выше выберите:"
echo "1. Для Docker Registry - используйте зеркала, которые показали ✓"
echo "2. Для APT - используйте зеркало с лучшей доступностью"
echo ""
echo "Если все зеркала недоступны, рассмотрите:"
echo "• Настройку HTTP/HTTPS proxy"
echo "• Перенос готовых образов через tar файлы"
echo "• Использование альтернативных DNS серверов"
echo ""
