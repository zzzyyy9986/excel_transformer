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
# Отредактируйте .env: APP_KEY, APP_URL

docker compose -f docker-compose.prod.yml up -d --build
```

Приложение будет доступно на порту **80** (или `HTTP_PORT` из `.env`).

| Переменная | Назначение |
|------------|------------|
| `APP_KEY` | Ключ Laravel (сгенерировать отдельно) |
| `APP_URL` | Публичный URL (`http://IP` или `https://domain.com`) |
| `VITE_API_URL` | Оставьте `/api` — nginx проксирует запросы на backend |

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
