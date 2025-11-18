# 🔥 Критическая проблема с сетью сервера

## Диагностика

Ваш production сервер **не может подключиться** к:
- ❌ Docker Hub Official (production.cloudflare.docker.com)
- ❌ Fastly CDN (151.101.x.x) - используется deb.debian.org
- ❌ nginx.org репозиторий

**Причина:** Вероятно сервер находится за строгим firewall или в изолированной сети.

---

## ✅ РЕКОМЕНДУЕМОЕ РЕШЕНИЕ: Перенос готовых образов

Так как сетевой доступ сервера сильно ограничен, соберите образы локально и перенесите их.

### Шаг 1: На вашей локальной машине (WSL)

```bash
cd /home/sayrendil/vkusvill/vverp

# Соберите образы локально
docker compose build

# Получите имена образов
docker images | grep vverp

# Сохраните образы в tar файлы
docker save -o vverp-images.tar \
  vverp-app:latest \
  vverp-queue:latest \
  vverp_app:latest \
  vverp_queue:latest \
  2>/dev/null || docker save -o vverp-images.tar $(docker images --filter=reference='*vverp*' --format '{{.Repository}}:{{.Tag}}')

# Сохраните базовые образы
docker save -o base-images.tar \
  serversideup/php:8.2-fpm-nginx \
  nginx:alpine \
  mysql:8.0 \
  node:20-alpine

# Проверьте размеры
ls -lh *.tar
```

### Шаг 2: Перенесите на сервер

```bash
# Из WSL на production сервер
scp vverp-images.tar base-images.tar user@SupportVM:/home/erp/vverp/
```

### Шаг 3: На production сервере

```bash
cd /home/erp/vverp

# Загрузите базовые образы
sudo docker load -i base-images.tar

# Загрузите образы приложения
sudo docker load -i vverp-images.tar

# Проверьте загруженные образы
sudo docker images

# Запустите контейнеры БЕЗ сборки
sudo docker compose up -d
```

---

## 🔄 АЛЬТЕРНАТИВА: Использовать Docker Registry внутри сети

Если у вас есть доступ к внутреннему Docker Registry:

1. На локальной машине:
```bash
docker tag vverp-app:latest your-registry.local/vverp-app:latest
docker push your-registry.local/vverp-app:latest
```

2. В docker-compose.yml укажите внутренний registry

---

## 🛠️ АЛЬТЕРНАТИВА 2: HTTP Proxy для APT

Если на сервере доступен HTTP proxy:

```dockerfile
FROM serversideup/php:8.2-fpm-nginx

USER root

# Настройка proxy для APT
RUN echo 'Acquire::http::Proxy "http://your-proxy:3128";' > /etc/apt/apt.conf.d/proxy.conf

# Установка пакетов через proxy
RUN apt-get update && apt-get install -y supervisor nodejs npm && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
RUN chown -R www-data:www-data /var/www/html

USER www-data
EXPOSE 9000
CMD ["php-fpm"]
```

---

## ⚠️ Текущая ситуация

- ✅ Docker Registry зеркала настроены и работают
- ❌ APT репозитории недоступны (Fastly CDN блокируется)
- ❌ Сборка образов на сервере невозможна без доступа к APT

**Вывод:** Без изменения сетевой политики сервера, единственный надежный способ - перенос готовых образов.
