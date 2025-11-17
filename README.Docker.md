# Docker Setup для Laravel

## Быстрый старт

### 1. Настройте .env файл

```bash
cp .env.example .env
```

Измените настройки БД в `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=secret
```

### 2. Запустите контейнеры

```bash
docker-compose up -d
```

### 3. Установите зависимости

```bash
# Composer
docker-compose exec app composer install

# NPM
docker-compose exec node npm install

# Сгенерировать ключ
docker-compose exec app php artisan key:generate

# Миграции
docker-compose exec app php artisan migrate

# Storage link
docker-compose exec app php artisan storage:link
```

### 4. Соберите фронтенд

```bash
docker-compose exec node npm run build
```

## Доступ к сервисам

- **Laravel**: http://localhost:8081
- **MySQL**: localhost:33061 (laravel/secret)
- **Vite HMR**: http://localhost:5175

## Полезные команды

```bash
# Запустить контейнеры
docker-compose up -d

# Остановить контейнеры
docker-compose down

# Логи
docker-compose logs -f

# Войти в контейнер
docker-compose exec app bash

# Artisan команды
docker-compose exec app php artisan migrate
docker-compose exec app php artisan cache:clear

# NPM команды
docker-compose exec node npm run dev
docker-compose exec node npm run build
```

## Структура

```
docker/
├── nginx/
│   └── default.conf    # Nginx конфигурация
├── php/
│   └── php.ini         # PHP настройки
└── mysql/
    └── my.cnf          # MySQL конфигурация
```

## Установка Jetstream

```bash
docker-compose exec app composer require laravel/jetstream
docker-compose exec app php artisan jetstream:install inertia
docker-compose exec node npm install
docker-compose exec node npm run build
docker-compose exec app php artisan migrate
```
