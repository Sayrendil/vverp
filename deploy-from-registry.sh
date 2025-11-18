#!/bin/bash
# Скрипт для развертывания на сервере из GitHub Container Registry

set -e

echo "=========================================="
echo "РАЗВЕРТЫВАНИЕ ИЗ GITHUB CONTAINER REGISTRY"
echo "=========================================="
echo ""

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Шаг 1: Логин в GitHub Container Registry${NC}"
echo "Для публичных образов логин не обязателен"
echo "Для приватных репозиториев введите токен:"
read -p "Нужен логин? (y/n): " need_login

if [ "$need_login" = "y" ]; then
    echo "GitHub Username:"
    read GITHUB_USERNAME
    echo "GitHub Token:"
    read -s GITHUB_TOKEN
    echo "$GITHUB_TOKEN" | sudo docker login ghcr.io -u $GITHUB_USERNAME --password-stdin
    echo -e "${GREEN}✓ Авторизация успешна${NC}"
fi
echo ""

echo -e "${YELLOW}Шаг 2: Остановка старых контейнеров${NC}"
sudo docker compose down || true
echo ""

echo -e "${YELLOW}Шаг 3: Загрузка новых образов${NC}"
sudo docker compose -f docker-compose.prod.yml pull
echo ""

echo -e "${YELLOW}Шаг 4: Запуск контейнеров${NC}"
sudo docker compose -f docker-compose.prod.yml up -d
echo ""

echo -e "${GREEN}=========================================="
echo "РАЗВЕРТЫВАНИЕ ЗАВЕРШЕНО!"
echo "==========================================${NC}"
echo ""
echo "Проверка статуса:"
sudo docker compose -f docker-compose.prod.yml ps
echo ""
echo "Приложение доступно на порту 8041"
