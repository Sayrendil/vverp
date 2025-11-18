#!/bin/bash
# Скрипт для локальной сборки и пуша образов в GitHub Container Registry

set -e

echo "=========================================="
echo "ЛОКАЛЬНАЯ СБОРКА И ПУБЛИКАЦИЯ ОБРАЗОВ"
echo "=========================================="
echo ""

# Цвета
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

# Переменные
GITHUB_USERNAME="sayrendil"
REPO_NAME="vverp"
REGISTRY="ghcr.io"

echo -e "${YELLOW}Шаг 1: Проверка Docker${NC}"
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Docker не установлен!${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Docker найден${NC}"
echo ""

echo -e "${YELLOW}Шаг 2: Логин в GitHub Container Registry${NC}"
echo "Введите ваш GitHub Personal Access Token (с правами write:packages):"
echo "Создать можно здесь: https://github.com/settings/tokens/new?scopes=write:packages"
echo ""
echo "Username: $GITHUB_USERNAME"
read -sp "Token: " GITHUB_TOKEN
echo ""

echo "$GITHUB_TOKEN" | docker login $REGISTRY -u $GITHUB_USERNAME --password-stdin

if [ $? -ne 0 ]; then
    echo -e "${RED}Ошибка авторизации!${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Авторизация успешна${NC}"
echo ""

echo -e "${YELLOW}Шаг 3: Сборка образа APP${NC}"
docker build -t $REGISTRY/$GITHUB_USERNAME/$REPO_NAME-app:latest -f Dockerfile .
echo -e "${GREEN}✓ Образ APP собран${NC}"
echo ""

echo -e "${YELLOW}Шаг 4: Сборка образа QUEUE${NC}"
docker build -t $REGISTRY/$GITHUB_USERNAME/$REPO_NAME-queue:latest -f Dockerfile.queue .
echo -e "${GREEN}✓ Образ QUEUE собран${NC}"
echo ""

echo -e "${YELLOW}Шаг 5: Публикация образа APP${NC}"
docker push $REGISTRY/$GITHUB_USERNAME/$REPO_NAME-app:latest
echo -e "${GREEN}✓ Образ APP опубликован${NC}"
echo ""

echo -e "${YELLOW}Шаг 6: Публикация образа QUEUE${NC}"
docker push $REGISTRY/$GITHUB_USERNAME/$REPO_NAME-queue:latest
echo -e "${GREEN}✓ Образ QUEUE опубликован${NC}"
echo ""

echo -e "${GREEN}=========================================="
echo "УСПЕШНО!"
echo "==========================================${NC}"
echo ""
echo "Образы опубликованы:"
echo "  - $REGISTRY/$GITHUB_USERNAME/$REPO_NAME-app:latest"
echo "  - $REGISTRY/$GITHUB_USERNAME/$REPO_NAME-queue:latest"
echo ""
echo "На сервере выполните:"
echo "  cd /home/erp/vverp"
echo "  git pull"
echo "  sudo docker compose -f docker-compose.prod.yml pull"
echo "  sudo docker compose -f docker-compose.prod.yml up -d"
