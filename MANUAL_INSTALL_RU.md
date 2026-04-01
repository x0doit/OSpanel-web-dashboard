# Ручная установка OSPanel Dashboard

**Разработчик:** [x0doit](https://github.com/x0doit)

## Требования

- Open Server Panel v6.5+
- PHP 8.x (входит в модули OSPanel)

## Установка

Скопируйте следующие файлы в `C:\OSPanel\system\public_html\`:

```
index.html
favicon.ico
x16.png
x32.png
api\backend.php
api\generate.php
plugins\jquery\jquery.min.js
plugins\toastr\toastr.min.css
plugins\toastr\toastr.min.js
plugins\sweetalert2\sweetalert2.all.min.js
plugins\sweetalert2\sweetalert2.min.css
```

## Использование

Дэшборд доступен по адресу **http://ospanel/** через HTTP-модуль OSPanel.

Backend API (`api/backend.php`) работает через Apache+PHP автоматически — отдельный сервер не нужен. Обрабатывает CRUD проектов и обновление состояния.

## Архитектура

| Компонент | Технология |
|---|---|
| Фронтенд | Vanilla HTML / CSS / JS (один файл `index.html`) |
| Backend API | PHP (`backend.php` + `generate.php`) |
| HTTP-сервер | OSPanel Apache + PHP |
| UI-библиотеки | jQuery, Toastr, SweetAlert2 |

### API-эндпоинты

| Метод | Путь | Описание |
|---|---|---|
| GET | `?action=state` | Перегенерировать и вернуть `state.json` |
| POST | `?action=project` | Создать новый проект |
| PUT | `?action=project` | Обновить настройки проекта |
| DELETE | `?action=project&domain=...&folder=...` | Удалить проект |

Запуск/остановка модулей через нативный API OSPanel:
```
/api/cmd/{TOKEN}/{on|off}/{MODULE_NAME}
```
