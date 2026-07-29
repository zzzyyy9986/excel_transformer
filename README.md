# Excel Transformer

MVP-приложение: загрузка Excel-шаблона прайс-листа и оформление заказа через веб-форму (аналог [miamia.ru form](https://miamia.ru/api/form.php?select_xls=./xls/diora_a.xls&header=0&header_show=1&email=1&client=1&manager=1&key=1)).

Архитектура повторяет [scooter_crm](https://github.com/zzzyyy9986/scooter_crm): **Laravel API + React (Vite, MobX) + Docker Compose**.

## Стек

| Слой | Технология |
|------|-----------|
| Backend | Laravel 13, PHP 8.4, PhpSpreadsheet |
| Frontend | React 18, TypeScript, Vite, MobX, Bootstrap |
| БД | SQLite |
| Инфраструктура | Docker Compose |

## Быстрый запуск

```bash
cd excel_transformer
docker compose up --build
```

| Сервис | URL |
|--------|-----|
| Frontend | http://localhost:5174 |
| Backend API | http://localhost:8001/api |

## Использование

1. **Вкладка «Загрузить шаблон»** — загрузите `.xls` / `.xlsx` с колонками: модель, размер, цвет, цена, сумма, описание.
2. **Вкладка «Заказ по шаблону»** — выберите прайс-уровень, укажите количество в колонке «Заказ», отправьте заказ.
3. **Вкладка «Заказы»** — список оформленных заказов с позициями.

Пример шаблона: `samples/diora_a.xls`

## API

- `GET /api/template` — активный шаблон и разобранная форма
- `POST /api/template/upload` — загрузка Excel (`multipart/form-data`, поле `file`)
- `POST /api/orders` — отправка заказа
- `GET /api/orders` — последние заказы

## Структура Excel

Парсер ожидает формат как у Mia-Amore:

- Строка заголовков: **модель**, **размер**, **цвет**, **цена**, **сумма**, **описание**
- Прайс-уровни в колонках L–M (Первый, Базовый, Бронзовый…)
- Группы товаров: первая строка группы — код модели (`4990`), последующие — с префиксом `_` (`_4992`)
- Название коллекции — в первой колонке второй строки

## Деплой на сервер (production)

На Ubuntu-сервере с Docker:

```bash
git clone https://github.com/zzzyyy9986/excel_transformer.git
cd excel_transformer

cp .env.prod.example .env
# Отредактируйте .env: APP_KEY, порты, URL

docker compose -f docker-compose.prod.yml up -d --build
```

| Сервис | URL по умолчанию |
|--------|------------------|
| Frontend | http://your-server-ip:8080 |
| Backend API | http://your-server-ip:8001/api |

| Переменная | Назначение |
|------------|------------|
| `APP_KEY` | Ключ Laravel (сгенерировать отдельно) |
| `FRONTEND_PORT` | Внешний порт frontend (по умолчанию `8080`) |
| `BACKEND_PORT` | Внешний порт backend API (по умолчанию `8001`) |
| `APP_URL` | Публичный URL frontend (`http://IP:8080` или домен через nginx) |
| `VITE_API_URL` | `/api` — через frontend; или полный URL backend при прямом доступе |

После смены `VITE_API_URL` или портов пересоберите frontend: `docker compose -f docker-compose.prod.yml up -d --build`.

### Nginx на сервере (домен)

Готовый конфиг: `deploy/nginx/excel-transformer.djazavi.ru.conf`

```bash
sudo cp deploy/nginx/excel-transformer.djazavi.ru.conf /etc/nginx/sites-available/
sudo ln -sf /etc/nginx/sites-available/excel-transformer.djazavi.ru.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# HTTPS
sudo certbot --nginx -d excel-transformer.djazavi.ru
```

Пример `.env` для этого домена: `deploy/nginx/.env.production`

| Переменная | Значение |
|------------|----------|
| `APP_URL` | `http://excel-transformer.djazavi.ru` (после certbot — `https://...`) |
| `FRONTEND_PORT` | `8082` |
| `BACKEND_PORT` | `8001` |
| `VITE_API_URL` | `/api` |

Обновление после push:

```bash
git pull origin main
docker compose -f docker-compose.prod.yml up -d --build
```

Данные (SQLite и загруженные файлы) хранятся в Docker volume `backend_storage`.

## Локальная разработка без Docker

```bash
# Backend
cd backend
composer install
touch database/database.sqlite
php artisan migrate
php artisan serve

# Frontend
cd frontend
cp .env.example .env.development
npm install
npm run dev
```
