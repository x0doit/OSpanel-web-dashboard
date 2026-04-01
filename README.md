<div align="center">

# OSPanel Dashboard

Современная веб-панель управления для [Open Server Panel](https://ospanel.io/)

[![OSPanel](https://img.shields.io/badge/OSPanel-v6.5+-9f3b46?style=flat-square)](https://ospanel.io/)
[![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](#license)

<br>

<img src="https://raw.githubusercontent.com/x0doit/OSpanel-web-dashboard/main/preview.png" alt="Dashboard Preview" width="700">

</div>

---

## Возможности

- **Dashboard** — статистика в реальном времени: запущенные модули, активные проекты, категории
- **Modules** — запуск / остановка любого модуля, фильтр по категории, поиск по имени
- **Projects** — создание, редактирование и удаление доменов (HTTP-движок, версия PHP, TLS)
- **Logs** — просмотр системного лога с подсветкой ошибок и предупреждений
- **Установщик в один клик** — автоматическая установка через `install.bat`

## Быстрый старт

### 1. Скачать

```
git clone https://github.com/x0doit/OSpanel-web-dashboard.git
```

Или скачайте [ZIP](https://github.com/x0doit/OSpanel-web-dashboard/archive/refs/heads/main.zip) и распакуйте в любую папку.

### 2. Установить

Запустите **`install.bat`** двойным кликом.

Установщик автоматически:
- найдёт OSPanel и PHP на вашем компьютере
- откроет веб-установщик в браузере
- позволит проверить окружение и установить дэшборд одним кликом

<div align="center">

| Шаг | Что происходит |
|:---:|---|
| **1** | `install.bat` находит PHP в модулях OSPanel |
| **2** | Запускает временный сервер, открывает браузер |
| **3** | Вы проверяете путь к OSPanel и нажимаете **Install** |
| **4** | Файлы копируются, генерируется state.json |
| **5** | По желанию удаляете установщик из OSPanel |

</div>

### 3. Открыть

Перейдите на **http://ospanel/** — дэшборд готов к работе.

## Требования

| | |
|---|---|
| **ОС** | Windows 10 / 11 |
| **OSPanel** | v6.5+ |
| **Модули** | Apache + любой PHP (рекомендуется 8.x) |

## Архитектура

```
ospanel-dashboard/
  index.html              # SPA-фронтенд (HTML + CSS + JS)
  api/
    backend.php           # REST API — CRUD проектов, обновление состояния
    generate.php          # Генерация state.json из конфигов OSPanel
  plugins/                # jQuery, Toastr, SweetAlert2
  install.bat             # Точка входа автоустановщика
  install.html            # Веб-интерфейс установщика
```

| Компонент | Стек |
|---|---|
| Фронтенд | Vanilla JS, CSS Variables, SVG-иконки |
| Бэкенд | PHP (работает через Apache, без отдельного сервера) |
| UI-библиотеки | jQuery, Toastr, SweetAlert2 |

### API

| Метод | Эндпоинт | Описание |
|---|---|---|
| `GET` | `?action=state` | Перегенерировать и вернуть состояние системы |
| `POST` | `?action=project` | Создать новый проект |
| `PUT` | `?action=project` | Обновить настройки проекта |
| `DELETE` | `?action=project&domain=...` | Удалить проект |

Запуск/остановка модулей через нативный API OSPanel: `/api/cmd/{TOKEN}/{on|off}/{MODULE}`

## Ручная установка

См. [MANUAL_INSTALL_RU.md](MANUAL_INSTALL_RU.md) | [MANUAL_INSTALL.md](MANUAL_INSTALL.md)

## Поддержать проект

Если дэшборд оказался полезен — поставьте звёздочку на [GitHub](https://github.com/x0doit/OSpanel-web-dashboard)!

## Автор

**[x0doit](https://github.com/x0doit)**

## Лицензия

MIT

---

<details>
<summary><strong>English</strong></summary>

## Features

- **Dashboard** — real-time stats: running modules, active projects, categories
- **Modules** — start / stop any module, filter by category, search by name
- **Projects** — create, edit and delete domains with full config (HTTP engine, PHP version, TLS)
- **Logs** — system log viewer with error/warning highlighting
- **One-click installer** — automated setup via `install.bat`

## Quick Start

### 1. Download

```
git clone https://github.com/x0doit/OSpanel-web-dashboard.git
```

Or download [ZIP](https://github.com/x0doit/OSpanel-web-dashboard/archive/refs/heads/main.zip) and extract anywhere.

### 2. Install

Double-click **`install.bat`** in the downloaded folder.

The installer will:
- automatically find OSPanel and PHP on your system
- open the web installer in your browser
- let you verify the environment and install with one click

| Step | What happens |
|:---:|---|
| **1** | `install.bat` finds PHP in OSPanel modules |
| **2** | Starts a temporary server, opens the browser |
| **3** | You verify the OSPanel path and click **Install** |
| **4** | Dashboard files are copied, state is generated |
| **5** | Optionally remove installer files from OSPanel |

### 3. Open

Go to **http://ospanel/** — the dashboard is ready.

## Requirements

| | |
|---|---|
| **OS** | Windows 10 / 11 |
| **OSPanel** | v6.5+ |
| **Modules** | Apache + any PHP (8.x recommended) |

## Architecture

```
ospanel-dashboard/
  index.html              # Single-page frontend (HTML + CSS + JS)
  api/
    backend.php           # REST API — project CRUD, state refresh
    generate.php          # Generates state.json from OSPanel config
  plugins/                # jQuery, Toastr, SweetAlert2
  install.bat             # Automated installer entry point
  install.html            # Web installer UI
```

| Component | Stack |
|---|---|
| Frontend | Vanilla JS, CSS Variables, SVG icons |
| Backend | PHP (runs through Apache, no extra server) |
| UI Libraries | jQuery, Toastr, SweetAlert2 |

### API

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `?action=state` | Regenerate and return system state |
| `POST` | `?action=project` | Create a new project |
| `PUT` | `?action=project` | Update project settings |
| `DELETE` | `?action=project&domain=...` | Delete a project |

Module start/stop uses the native OSPanel API: `/api/cmd/{TOKEN}/{on|off}/{MODULE}`

## Manual Installation

See [MANUAL_INSTALL.md](MANUAL_INSTALL.md) | [MANUAL_INSTALL_RU.md](MANUAL_INSTALL_RU.md)

## Support

If you find this dashboard useful — give it a star on [GitHub](https://github.com/x0doit/OSpanel-web-dashboard)!

## Author

**[x0doit](https://github.com/x0doit)**

## License

MIT

</details>
