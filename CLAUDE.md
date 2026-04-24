# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project overview

TimeClock is a PHP 8+ time-tracking system for Grupo Manzanares (Colombia). It manages employee clock-in/out, shift assignments, payroll pre-liquidation, and supervisor approvals. No framework — custom lightweight MVC-like structure.

## Running the project

Requires **Laragon** (or Apache/Nginx + MySQL + PHP 8.0+). Served at `http://localhost/timeclock`.

- **Import database**: run `database/schema.sql` in MySQL (phpMyAdmin, HeidiSQL, or `mysql -u root timeclock < database/schema.sql`)
- **Default admin login**: cédula `000000001`, password `Admin2025!`
- **Logs**: written to `logs/YYYY-MM.log`
- **Check install**: visit `/install/check.php` (delete after confirming all green)

No build step, no npm, no composer. All assets loaded from CDN.

## Architecture

### Request flow

Every page/API starts with `require_once __DIR__ . '/bootstrap.php'` which:
1. Defines `ROOT_PATH` and `BASE_URL` (computed dynamically from `$_SERVER`)
2. Autoloads classes from `core/` by filename (`core/Auth.php` → `Auth`)
3. Starts a secure PHP session

### Core classes (`core/`)

| Class | Purpose |
|-------|---------|
| `DB` | PDO singleton. Methods: `fetchAll`, `fetchOne`, `execute`, `insert`. Uses `ERRMODE_EXCEPTION` — **unhandled PDOExceptions crash the endpoint with no JSON response** |
| `Auth` | Session-based auth. Login now queries `WHERE u.cedula = ?` (changed from email). Stores user data in `$_SESSION['user']` |
| `Response` | `Response::success($data, $msg)` / `Response::error($msg, $code)` — both call `exit` after echoing JSON |
| `Helpers` | `clean()` (XSS), `getParam()` (reads `parametros` table), `equipoAutorizado()`, `esFestivo()` |
| `Turno` | Shift state calculation (`calcularEstado`), auto-close pending days (`cerrarPendientes`), daily summary recalc |
| `Preliquidacion` | Payroll pre-calculation logic |
| `Logger` | Writes to `logs/YYYY-MM.log`. Debug messages only when `APP_ENV !== 'production'` |

### API layer (`api/`)

All endpoints follow the same pattern:
```php
require_once dirname(__DIR__) . '/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
Auth::requireLogin(); // or requireRole(...)
$action = $_GET['action'] ?? '';
switch ($action) { ... }
```

Called from JS as `TC.get('/api/foo.php?action=bar')` or `TC.post('/api/foo.php?action=bar', body)`.

Response shape: `{ success: bool, message: string, data: any }`.

### Frontend

- **`APP` global** (set in `views/layout/header.php`): `{ baseUrl, rol, userId, csrfToken }`
- **`TC` global** (`js/app.js`): `TC.get()`, `TC.post()`, `TC.toast()`, `TC.openModal()`, `TC.closeModal()`, `TC.confirmar()`, `TC.fmtFecha()`, `TC.fmtMin()`, `TC.badgeEstado()`
- Pages declare `$extraJs = ['config.js']` etc.; footer.php loads them
- Tailwind CSS via CDN (config in each page/header), Font Awesome 6.4, Nunito font
- Page-specific JS modules: `config.js` (Cfg), `empleados.js` (Empleados), `marcacion.js`, `turnos.js`, `reportes.js`, `preliquidacion.js`, `dashboard.js`

### Page structure

PHP pages set `$pageTitle`, `$activeMenu`, `$extraJs`, optionally inline `<script>` with data from PHP (e.g. `const SUPERVISORES = <?= json_encode($supervisores) ?>;`), then `require header.php` / content / `require footer.php`.

### Roles & access

- `empleado` — can mark attendance, view own reports
- `supervisor` — above + approve marcaciones, assign shifts, view employees in their sede
- `admin` — full access including all config pages

`Auth::requireRole('admin')` / `Auth::requireRole('admin','supervisor')` at top of each endpoint.

## Known schema issue — sedes.supervisor_id

The `sedes` table was created without `supervisor_id`. The API and JS expect it. **Must run this migration before sedes features work:**

```sql
ALTER TABLE sedes
  ADD COLUMN supervisor_id INT UNSIGNED NULL,
  ADD CONSTRAINT fk_sedes_supervisor
      FOREIGN KEY (supervisor_id) REFERENCES usuarios(id) ON DELETE SET NULL;
```

`database/schema.sql` has already been updated with this column; this migration is only needed on existing installations.

## Important patterns

**Adding a new config entity** follows the pattern in `config.js` + `api/sedes.php`:
- PHP page: query data for dropdowns → `require header.php` → inline `<script>const DATA = <?= json_encode(...) ?>;` → table HTML with `id="tbody-xxx"` → `require footer.php`
- API: switch on `action` values `listar / crear / editar / eliminar`
- JS in `config.js`: `cargarXxx()` fetches list, `abrirXxx(id)` opens modal with `TC.openModal()`, `guardarXxx(id)` calls `TC.post()`

**PHP null safety pattern** — use `!empty()` or `?? null` when reading optional JSON body keys, never bare `$body['key'] ?`:
```php
// Correct
$supId = !empty($body['supervisor_id']) ? (int)$body['supervisor_id'] : null;
// Wrong (PHP 8 warning on undefined key)
$supId = $body['supervisor_id'] ? (int)$body['supervisor_id'] : null;
```

**Supervisor scope** — `api/usuarios.php listar` filters `u.sede_id` when the caller is a supervisor; admins see all.

## Login credential

Authentication uses `cedula` (national ID), not email. The `usuarios` table has both `email` (unique, optional) and `cedula` (unique) columns. `api/auth.php` sends `{ cedula, password }` and `Auth::login()` queries `WHERE u.cedula = ?`.
