<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
Auth::requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    case 'listar':
        Auth::requireRole('admin', 'supervisor');
        $sedeId = Auth::hasRole('supervisor') ? (int)(Auth::user()['sede_id'] ?? 0) : null;

        $rows = DB::fetchAll(
            'SELECT u.id, u.nombre, u.apellido, u.email, u.cedula, u.rol,
                    u.activo, u.equipo_permitido,
                    c.nombre AS cargo_nombre, s.nombre AS sede_nombre
             FROM usuarios u
             LEFT JOIN cargos c ON c.id = u.cargo_id
             LEFT JOIN sedes s ON s.id = u.sede_id
             WHERE u.activo = 1
               AND (? IS NULL OR u.sede_id = ?)
             ORDER BY u.nombre, u.apellido',
            [$sedeId, $sedeId]
        );
        Response::success($rows);
        break;

    case 'crear':
        Auth::requireRole('admin');
        if ($method !== 'POST') Response::error('Método no permitido', 405);

        $body     = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre   = Helpers::clean($body['nombre'] ?? '');
        $apellido = Helpers::clean($body['apellido'] ?? '');
        $email    = trim($body['email'] ?? '') !== '' ? filter_var(trim($body['email']), FILTER_SANITIZE_EMAIL) : null;
        $cedula   = Helpers::clean($body['cedula'] ?? '');
        $pass     = $body['password'] ?? '';
        $rol      = in_array($body['rol'] ?? '', ROLES) ? $body['rol'] : 'empleado';
        $cargoId  = (int)($body['cargo_id'] ?? 0) ?: null;
        $sedeId   = (int)($body['sede_id'] ?? 0) ?: null;
        $equipo   = strtolower(Helpers::clean($body['equipo_permitido'] ?? '')) ?: null;

        if (!$nombre || !$apellido || !$cedula || !$pass) {
            Response::error('Nombre, apellido, cédula y contraseña son requeridos');
        }

        if (strlen($pass) < 8) Response::error('Contraseña mínimo 8 caracteres');

        $existsCedula = DB::fetchOne('SELECT id FROM usuarios WHERE cedula = ?', [$cedula]);
        if ($existsCedula) Response::error('La cédula ya está registrada');

        if ($email) {
            $existsEmail = DB::fetchOne('SELECT id FROM usuarios WHERE email = ?', [$email]);
            if ($existsEmail) Response::error('El email ya está registrado');
        }

        $id = DB::insert(
            'INSERT INTO usuarios (nombre, apellido, email, cedula, password_hash, rol, cargo_id, sede_id, equipo_permitido)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$nombre, $apellido, $email, $cedula, password_hash($pass, PASSWORD_BCRYPT), $rol, $cargoId, $sedeId, $equipo]
        );
        Response::success(['id' => $id], 'Usuario creado');
        break;

    case 'editar':
        Auth::requireRole('admin');
        if ($method !== 'POST') Response::error('Método no permitido', 405);

        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $id      = (int)($body['id'] ?? 0);
        if (!$id) Response::error('ID requerido');

        $fields = [];
        $params = [];

        foreach (['nombre','apellido','cedula'] as $f) {
            if (isset($body[$f])) { $fields[] = "$f=?"; $params[] = Helpers::clean($body[$f]); }
        }
        if (array_key_exists('email', $body)) {
            $fields[] = 'email=?';
            $emailVal = trim($body['email'] ?? '');
            $params[] = $emailVal !== '' ? filter_var($emailVal, FILTER_SANITIZE_EMAIL) : null;
        }
        if (isset($body['rol']) && in_array($body['rol'], ROLES)) {
            $fields[] = 'rol=?'; $params[] = $body['rol'];
        }
        foreach (['cargo_id','sede_id'] as $f) {
            if (isset($body[$f])) { $fields[] = "$f=?"; $params[] = (int)$body[$f] ?: null; }
        }
        if (array_key_exists('equipo_permitido', $body)) {
            $fields[] = 'equipo_permitido=?';
            $params[] = strtolower(Helpers::clean($body['equipo_permitido'])) ?: null;
        }
        if (!empty($body['password']) && strlen($body['password']) >= 8) {
            $fields[] = 'password_hash=?';
            $params[] = password_hash($body['password'], PASSWORD_BCRYPT);
        }

        if (empty($fields)) Response::error('Nada que actualizar');
        $params[] = $id;
        DB::execute('UPDATE usuarios SET ' . implode(',', $fields) . ' WHERE id=?', $params);
        Response::success(null, 'Usuario actualizado');
        break;

    case 'eliminar':
        Auth::requireRole('admin');
        $id = (int)($_GET['id'] ?? 0);
        if (!$id || $id === Auth::id()) Response::error('No permitido');
        DB::execute('UPDATE usuarios SET activo=0 WHERE id=?', [$id]);
        Response::success(null, 'Usuario desactivado');
        break;

    default:
        Response::error('Acción no válida', 404);
}
