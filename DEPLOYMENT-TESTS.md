# Инструкция по тестированию развертывания

## Быстрый старт

### Шаг 1: Скопируйте тестовые скрипты на сервер

```bash
scp test-mirrors.sh test-docker-pull.sh user@SupportVM:/home/erp/vverp/
```

### Шаг 2: На сервере запустите тесты

```bash
cd /home/erp/vverp

# Тест 1: Проверка доступности зеркал
chmod +x test-mirrors.sh
./test-mirrors.sh > test-results.txt
cat test-results.txt

# Тест 2: Проверка загрузки Docker образов
chmod +x test-docker-pull.sh
sudo ./test-docker-pull.sh >> test-results.txt
cat test-results.txt
```

### Шаг 3: Проанализируйте результаты

Посмотрите файл `test-results.txt` и определите:
- ✓ Какие зеркала доступны
- ✓ Какие образы загружаются
- ✗ Где возникают проблемы

---

## Варианты решения после тестов

### Вариант A: Зеркала работают

Если тесты показали доступные зеркала:

```bash
# Создайте daemon.json
sudo nano /etc/docker/daemon.json
```

Вставьте (используйте доступные зеркала из теста):

```json
{
  "registry-mirrors": [
    "https://mirror.gcr.io",
    "https://dockerhub.timeweb.cloud"
  ],
  "dns": ["8.8.8.8", "8.8.4.4"]
}
```

Перезапустите Docker:

```bash
sudo systemctl restart docker
sudo docker compose up -d --build
```

### Вариант B: Зеркала не работают, но pull работает

Если обычный `docker pull` работает:

```bash
# Предварительно загрузите все образы
sudo docker pull serversideup/php:8.2-fpm-nginx
sudo docker pull nginx:alpine
sudo docker pull mysql:8.0
sudo docker pull node:20-alpine

# Затем запустите сборку
sudo docker compose up -d --build
```

### Вариант C: Ничего не работает на сервере

Соберите образы локально и перенесите:

**На локальной машине:**

```bash
chmod +x docker-export.sh
./docker-export.sh

# Скопируйте tar файлы на сервер
scp *.tar user@SupportVM:/home/erp/vverp/
```

**На сервере:**

```bash
cd /home/erp/vverp
chmod +x docker-import.sh
./docker-import.sh
sudo docker compose up -d
```

---

## Дополнительные проверки

### Проверка DNS

```bash
# Проверьте текущий DNS
cat /etc/resolv.conf

# Проверьте резолвинг Docker Hub
nslookup registry-1.docker.io
nslookup production.cloudflare.docker.com

# Если проблемы с DNS, добавьте Google DNS
sudo bash -c 'echo "nameserver 8.8.8.8" > /etc/resolv.conf'
sudo bash -c 'echo "nameserver 8.8.4.4" >> /etc/resolv.conf'
```

### Проверка сети

```bash
# Проверьте доступ к Docker Hub
curl -v https://registry-1.docker.io/v2/

# Проверьте firewall
sudo iptables -L -n | grep DROP
sudo ufw status

# Проверьте proxy настройки
echo $HTTP_PROXY
echo $HTTPS_PROXY
```

### Проверка Docker

```bash
# Проверьте статус Docker
sudo systemctl status docker

# Проверьте логи Docker
sudo journalctl -u docker -n 50

# Проверьте информацию
sudo docker info
```

---

## Troubleshooting

### Ошибка: "dial tcp: i/o timeout"

**Причины:**
- Проблемы с DNS
- Firewall блокирует исходящие подключения
- Проблемы с маршрутизацией

**Решение:**
```bash
# Настройте DNS
sudo nano /etc/docker/daemon.json
# Добавьте: "dns": ["8.8.8.8", "8.8.4.4"]

sudo systemctl restart docker
```

### Ошибка: "404 Not Found"

**Причины:**
- Зеркало не поддерживает нужную версию Debian/Ubuntu

**Решение:**
- Используйте оригинальные репозитории
- Уберите настройку зеркал из Dockerfile

### Ошибка: "failed to resolve source metadata"

**Причины:**
- Cloudflare CDN недоступен

**Решение:**
```bash
# Загрузите образ напрямую
sudo docker pull serversideup/php:8.2-fpm-nginx --platform linux/amd64
```

---

## Итоговая команда развертывания

После всех тестов и настроек:

```bash
cd /home/erp/vverp

# Чистая пересборка
sudo docker compose down
sudo docker compose build --no-cache
sudo docker compose up -d

# Проверьте статус
sudo docker compose ps
sudo docker compose logs -f
```
