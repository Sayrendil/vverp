# Инструкция по сборке и развертыванию фронтенда

## Проблема

На production сервере npm registry (registry.npmjs.org) заблокирован на уровне сети/firewall, поэтому невозможно выполнить `npm install` внутри Docker контейнеров.

## Решение

Фронтенд собирается **локально** на машине разработчика, где есть доступ к npm registry, затем собранные файлы переносятся на production сервер.

---

## Процесс развертывания фронтенда

### Шаг 1: Сборка на локальной машине

```bash
# Перейти в директорию проекта
cd ~/vkusvill/vverp

# Убедиться что изменения закоммичены
git status

# Обновить зависимости (если изменился package.json)
npm install

# Собрать фронтенд для production
npm run build

# Проверить что файлы собрались
ls -la public/build/assets | head -20

# Создать архив для переноса
tar -czf build.tar.gz -C public build/

# Проверить размер архива (~150-200 KB)
ls -lh build.tar.gz
```

**Ожидаемый результат:**
- В `public/build/assets/` должно быть ~50+ файлов JS/CSS
- Файл `build.tar.gz` размером ~150-200 KB

---

### Шаг 2: Копирование на сервер

```bash
# Скопировать архив на production сервер
scp -i ~/.ssh/id_rsa_global build.tar.gz user@10.193.0.55:/home/erp/vverp/
```

**Примечание:** Замените IP адрес если сервер находится на другом адресе.

---

### Шаг 3: Развертывание на сервере

```bash
# Подключиться к серверу
ssh -i ~/.ssh/id_rsa_global user@10.193.0.55

# Перейти в директорию проекта
cd /home/erp/vverp

# Распаковать архив
tar -xzf build.tar.gz

# Проверить что файлы распаковались
ls -la build/assets | head -20

# Удалить старые файлы в контейнере
sudo docker exec -u root vverp_app rm -rf /var/www/public/build

# Скопировать новые файлы в контейнер
sudo docker cp build/. vverp_app:/var/www/public/build/

# Исправить права доступа
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/public/build

# Проверить что файлы в контейнере
sudo docker exec vverp_app ls -la /var/www/public/build/assets | head -20

# Очистить временные файлы
rm -rf build/ build.tar.gz
```

---

### Шаг 4: Проверка

1. Откройте сайт в браузере: `http://10.193.0.55:8041`
2. Нажмите `Ctrl+Shift+R` для жесткой перезагрузки кэша
3. Убедитесь что:
   - Страница загружается без ошибок 404
   - Стили применяются корректно
   - JavaScript работает

**Проверка в консоли браузера:**
- Не должно быть ошибок `GET /build/assets/...` 404

**Проверка на сервере:**
```bash
# Посмотреть логи nginx (не должно быть 404 для /build/assets/)
sudo docker logs --tail 50 vverp_nginx | grep "GET /build"
```

---

## Полная команда одной строкой

### На локальной машине:
```bash
cd ~/vkusvill/vverp && npm run build && tar -czf build.tar.gz -C public build/ && scp -i ~/.ssh/id_rsa_global build.tar.gz user@10.193.0.55:/home/erp/vverp/
```

### На сервере:
```bash
cd /home/erp/vverp && tar -xzf build.tar.gz && sudo docker exec -u root vverp_app rm -rf /var/www/public/build && sudo docker cp build/. vverp_app:/var/www/public/build/ && sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/public/build && rm -rf build/ build.tar.gz
```

---

## Когда нужно пересобирать фронтенд?

Фронтенд нужно пересобрать и развернуть **каждый раз** когда изменяются:

1. **Vue компоненты**: `resources/js/Pages/**/*.vue`
2. **Vue layouts**: `resources/js/Layouts/**/*.vue`
3. **JavaScript код**: `resources/js/**/*.js`
4. **CSS стили**: `resources/css/**/*.css`
5. **Конфигурация Vite**: `vite.config.js`
6. **Зависимости**: `package.json`

**Не требуется пересборка** при изменении:
- PHP файлов (контроллеры, модели, сервисы)
- Blade шаблонов (если используются)
- Конфигурационных файлов Laravel
- Миграций базы данных

---

## Устранение неполадок

### Проблема: `vite: not found` при `npm run build`

**Решение:**
```bash
# Удалить node_modules и переустановить
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Проблема: Ошибка прав доступа при `npm run build`

**Решение:**
```bash
# Исправить права на директорию build
sudo chown -R $USER:$USER public/build
npm run build
```

### Проблема: 404 ошибки после развертывания

**Проверка 1:** Убедитесь что файлы в контейнере:
```bash
sudo docker exec vverp_app ls -la /var/www/public/build/assets
```

**Проверка 2:** Проверьте права доступа:
```bash
sudo docker exec vverp_app ls -la /var/www/public/build/
# Должен быть owner: appuser:appgroup
```

**Решение:** Повторите копирование и исправление прав:
```bash
sudo docker cp build/. vverp_app:/var/www/public/build/
sudo docker exec -u root vverp_app chown -R appuser:appgroup /var/www/public/build
```

### Проблема: Старые файлы кэшируются в браузере

**Решение:**
- Нажмите `Ctrl+Shift+R` (жесткая перезагрузка)
- Или очистите кэш браузера вручную

---

## Альтернативные решения (для будущего)

### Вариант 1: Настройка npm proxy на сервере

Настроить прокси сервер для доступа к npm registry из Docker контейнеров.

### Вариант 2: GitHub Actions автосборка

Настроить автоматическую сборку фронтенда в CI/CD и включение его в Docker образ:

```yaml
# .github/workflows/build.yml
- name: Build frontend
  run: |
    npm ci
    npm run build

- name: Build Docker with assets
  run: docker build -t app:latest .
```

### Вариант 3: npm зеркало

Использовать корпоративное зеркало npm registry или Verdaccio.

---

## Файлы которые НЕ должны быть в Git

Добавьте в `.gitignore`:

```
# Собранные фронтенд файлы
/public/build
/public/hot

# Временные архивы
build.tar.gz

# Node modules
/node_modules
```

---

## Структура проекта

```
vverp/
├── resources/
│   ├── js/              # Vue компоненты и JS
│   │   ├── Pages/       # Страницы приложения
│   │   ├── Layouts/     # Layouts
│   │   └── app.js       # Точка входа
│   └── css/             # Стили
├── public/
│   └── build/           # ← Сюда собирается фронтенд
│       ├── manifest.json
│       └── assets/      # JS и CSS файлы
├── package.json         # npm зависимости
├── vite.config.js       # Конфигурация сборщика
└── docker-compose.prod.yml
```

---

## Контакты и поддержка

При возникновении проблем:
1. Проверьте логи nginx: `sudo docker logs vverp_nginx`
2. Проверьте логи приложения: `sudo docker logs vverp_app`
3. Проверьте консоль браузера (F12 → Console)

**Последнее обновление:** 18 ноября 2025
