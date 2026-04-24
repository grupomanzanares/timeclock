# TimeClock — Sistema de Control de Horas Laborales

## Requisitos
- PHP 8.0+
- MySQL 5.7+ / MariaDB 10.4+
- Extensiones PHP: `pdo`, `pdo_mysql`, `mbstring`, `json`, `openssl`
- Apache con `mod_rewrite` habilitado

---

## Instalación

### 1. Configurar base de datos
Edite `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'timeclock');
define('DB_USER', 'su_usuario');
define('DB_PASS', 'su_contraseña');
```

### 2. Importar esquema
```bash
mysql -u root -p < database/schema.sql
```

### 3. Verificar instalación
Abra en el navegador:
```
http://su-dominio/install/check.php
```

### 4. Primer acceso
- URL: `http://su-dominio/login.php`
- Email: `admin@timeclock.com`
- Contraseña: `Admin2025!`

> **Cambie la contraseña inmediatamente en Empleados → Editar**

### 5. Eliminar instalador
```bash
rm -rf install/
```

---

## Estructura de archivos

```
timeclock/
├── .htaccess                   # Seguridad Apache
├── bootstrap.php               # Punto de entrada
├── index.php                   # Dashboard
├── login.php                   # Login
│
├── api/
│   ├── auth.php                # Login/logout
│   ├── marcaciones.php         # Marcar entrada/salida, aprobar
│   ├── turnos.php              # CRUD turnos y asignaciones
│   ├── sedes.php               # CRUD sedes
│   ├── usuarios.php            # CRUD empleados
│   ├── config.php              # Parámetros, cargos, equipos, festivos
│   └── reportes.php            # Reportes y exportación Excel/CSV
│
├── config/
│   ├── app.php                 # Constantes de la app
│   └── database.php            # Credenciales DB
│
├── core/
│   ├── Auth.php                # Autenticación y roles
│   ├── DB.php                  # Singleton PDO
│   ├── Helpers.php             # Utilidades: CSRF, equipo, parámetros
│   ├── Logger.php              # Logs rotativos
│   ├── Preliquidacion.php      # Cálculo preliquidación nómina
│   ├── Response.php            # Respuestas JSON
│   └── Turno.php               # Lógica turnos, estados, cierre automático
│
├── marcacion/
│   ├── index.php               # Pantalla marcar asistencia
│   └── pendientes.php          # Aprobar marcaciones (supervisor)
│
├── turnos/
│   └── index.php               # Gestión y asignación de turnos
│
├── empleados/
│   └── index.php               # CRUD empleados
│
├── reportes/
│   ├── marcaciones.php         # Reporte detallado
│   └── preliquidacion.php      # Preliquidación semanal
│
├── config/
│   ├── parametros.php          # Tolerancias y tiempos
│   ├── sedes.php               # Sedes
│   ├── cargos.php              # Cargos y minutos descanso
│   ├── equipos.php             # Equipos autorizados por cargo
│   └── festivos.php            # Festivos Colombia
│
├── views/layout/
│   ├── header.php              # Sidebar + topbar
│   └── footer.php             # Toast, modal, JS
│
├── js/
│   ├── app.js                  # Utilidades globales TC
│   ├── dashboard.js
│   ├── marcacion.js
│   ├── pendientes.js
│   ├── turnos.js
│   ├── empleados.js
│   ├── reportes.js
│   ├── preliquidacion.js
│   └── config.js
│
├── css/
│   └── app.css
│
├── database/
│   └── schema.sql
│
├── install/
│   └── check.php               # Verificador (eliminar tras instalar)
│
└── logs/                       # Logs automáticos (creado en ejecución)
```

---

## Roles

| Rol | Permisos |
|-----|----------|
| `admin` | Todo: configurar, reportes, aprobar, gestionar usuarios |
| `supervisor` | Aprobar marcaciones, asignar turnos, ver reportes de su sede |
| `empleado` | Marcar entrada/salida, ver sus propios reportes |

---

## Lógica de turnos nocturnos

Los turnos que cruzan medianoche (ej: 22:00 → 06:00) se marcan con `nocturno = 1`.

- El sistema **no cierra automáticamente** un turno nocturno activo hasta que el turno finalice.
- El cierre automático solo aplica a turnos **completamente pasados** (la hora de fin ya ocurrió).
- Al marcar entrada del día siguiente, si hay un día pendiente **no nocturno** activo, se cierra automáticamente y se notifica al usuario.

---

## Preliquidación — Fórmula

```
Semana: domingo a sábado
Días normales (Lun–Sáb, no festivos) × 7.33 = horas que debió trabajar
Domingos y festivos = concepto aparte (no entran en la fórmula base)
```

---

## Tolerancias configurables (parámetros)

| Clave | Por defecto | Descripción |
|-------|-------------|-------------|
| `tolerancia_entrada_antes` | 10 min | Ventana antes del inicio del turno para marcar entrada |
| `tolerancia_entrada_despues` | 15 min | Máximo de retraso sin marcar como llegada tarde |
| `tolerancia_salida_antes` | 10 min | Puede salir hasta X minutos antes del fin del turno |
| `tolerancia_salida_despues` | 30 min | Hasta X minutos después del fin sin marcar como salida tarde |
| `minutos_descanso_global` | 60 min | Minutos de descanso a descontar por jornada (sobreescribible por cargo) |

Los parámetros pueden configurarse globalmente o por cargo específico.
