#!/bin/bash
# Финальный скрипт развертывания (на основе рабочего проекта)

set -e

echo "=========================================="
echo "ФИНАЛЬНОЕ РАЗВЕРТЫВАНИЕ VVERP"
echo "=========================================="
echo ""

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo -e "${YELLOW}Шаг 1: Очистка Docker кэша${NC}"
sudo docker builder prune -af
echo ""

echo -e "${YELLOW}Шаг 2: Остановка старых контейнеров${NC}"
sudo docker compose down || true
echo ""

echo -e "${YELLOW}Шаг 3: Загрузка базового образа PHP${NC}"
sudo docker pull php:8.2-fpm
echo ""

echo -e "${YELLOW}Шаг 4: Сборка образов (может занять 5-10 минут)${NC}"
sudo docker compose build --no-cache
echo ""

echo -e "${YELLOW}Шаг 5: Запуск контейнеров${NC}"
sudo docker compose up -d
echo ""

echo -e "${GREEN}=========================================="
echo "РАЗВЕРТЫВАНИЕ ЗАВЕРШЕНО!"
echo "==========================================${NC}"
echo ""
echo "Проверка статуса:"
sudo docker compose ps
echo ""
echo -e "${GREEN}Приложение доступно на порту 8041${NC}"
echo "http://your-server-ip:8041"
echo ""
echo "Для просмотра логов:"
echo "  sudo docker compose logs -f app"
echo "  sudo docker compose logs -f queue"
echo "  sudo docker compose logs -f nginx"
