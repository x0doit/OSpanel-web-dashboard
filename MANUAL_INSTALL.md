# Manual Installation — OSPanel Dashboard

**Developer:** [x0doit](https://github.com/x0doit)

## Requirements

- Open Server Panel v6.5+
- PHP 8.x (included in OSPanel modules)

## Installation

Copy the following files into `C:\OSPanel\system\public_html\`:

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

## Usage

The dashboard is available at **http://ospanel/** via OSPanel's HTTP module.

The backend API (`api/backend.php`) runs through Apache+PHP automatically — no separate server needed. It handles project CRUD and live state refresh.

## Architecture

| Component | Technology |
|---|---|
| Frontend | Vanilla HTML / CSS / JS (single `index.html`) |
| Backend API | PHP (`backend.php` + `generate.php`) |
| HTTP server | OSPanel Apache + PHP |
| UI libs | jQuery, Toastr, SweetAlert2 |

### API Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `?action=state` | Regenerate and return `state.json` |
| POST | `?action=project` | Create a new project |
| PUT | `?action=project` | Update project settings |
| DELETE | `?action=project&domain=...&folder=...` | Delete a project |

Module start/stop is handled via the native OSPanel API:
```
/api/cmd/{TOKEN}/{on|off}/{MODULE_NAME}
```
