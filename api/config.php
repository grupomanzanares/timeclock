<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
Auth::requireRole('admin');

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    // ---- Parámetros globales ----
    case 'parametros_listar':
        $rows = DB::fetchAll(
            'SELECT p.*, c.nombre AS cargo_nombre
             FROM parametros p
             LEFT JOIN cargos c ON c.id = p.cargo_id
             ORDER BY p.cargo_id ASC, p.clave ASC'
        );
        Response::success($rows);
        break;

    case 'parametros_guardar':
        if ($method !== 'POST') Response::error('Método no permitido', 405);
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $cargoId = isset($body['cargo_id']) ? (int)$body['cargo_id'] : null;
        $clave   = Helpers::clean($body['clave'] ?? '');
        $valor   = Helpers::clean($body['valor'] ?? '');
        $desc    = Helpers::clean($body['descripcion'] ?? '');

        if (!$clave || $valor === '') Response::error('Clave y valor requeridos');

        DB::execute(
            'INSERT INTO parametros (cargo_id, clave, valor, descripcion)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE valor=VALUES(valor), descripcion=VALUES(descripcion)',
            [$cargoId, $clave, $valor, $desc]
        );
        Response::success(null, 'Parámetro guardado');
        break;

    case 'parametros_eliminar':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) Response::error('ID requerido');
        DB::execute('DELETE FROM parametros WHERE id = ?', [$id]);
        Response::success(null, 'Parámetro eliminado');
        break;

    // ---- Cargos ----
    case 'cargos_listar':
        Response::success(DB::fetchAll('SELECT * FROM cargos WHERE activo = 1 ORDER BY nombre'));
        break;

    case 'cargos_guardar':
        if ($method !== 'POST') Response::error('Método no permitido', 405);
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $id      = (int)($body['id'] ?? 0);
        $nombre  = Helpers::clean($body['nombre'] ?? '');
        $minDesc = (int)($body['minutos_descanso'] ?? 60);
        $desc    = Helpers::clean($body['descripcion'] ?? '');

        if (!$nombre) Response::error('Nombre requerido');

        $isUpdate = $id > 0;
        if ($isUpdate) {
            DB::execute(
                'UPDATE cargos SET nombre=?, descripcion=?, minutos_descanso=? WHERE id=?',
                [$nombre, $desc, $minDesc, $id]
            );
        } else {
            $id = DB::insert(
                'INSERT INTO cargos (nombre, descripcion, minutos_descanso) VALUES (?, ?, ?)',
                [$nombre, $desc, $minDesc]
            );
        }
        Response::success(['id' => (int)$id], $isUpdate ? 'Cargo actualizado' : 'Cargo creado');
        break;

    // ---- Equipos autorizados ----
    case 'equipos_listar':
        $rows = DB::fetchAll(
            'SELECT ea.*, c.nombre AS cargo_nombre, s.nombre AS sede_nombre
             FROM equipos_autorizados ea
             JOIN cargos c ON c.id = ea.cargo_id
             LEFT JOIN sedes s ON s.id = ea.sede_id
             WHERE ea.activo = 1
             ORDER BY c.nombre, ea.nombre_equipo'
        );
        Response::success($rows);
        break;

    case 'equipos_guardar':
        if ($method !== 'POST') Response::error('Método no permitido', 405);
        $body       = json_decode(file_get_contents('php://input'), true) ?? [];
        $id         = (int)($body['id'] ?? 0);
        $cargoId    = (int)($body['cargo_id'] ?? 0);
        $sedeId     = !empty($body['sede_id']) ? (int)$body['sede_id'] : null;
        $equipo     = strtolower(Helpers::clean($body['nombre_equipo'] ?? ''));
        $descripcion= Helpers::clean($body['descripcion'] ?? '');

        if (!$cargoId || !$equipo) Response::error('Cargo y nombre de equipo requeridos');

        if ($id) {
            DB::execute(
                'UPDATE equipos_autorizados
                 SET cargo_id=?, sede_id=?, nombre_equipo=?, descripcion=?
                 WHERE id=?',
                [$cargoId, $sedeId, $equipo, $descripcion, $id]
            );
        } else {
            $id = DB::insert(
                'INSERT INTO equipos_autorizados (cargo_id, sede_id, nombre_equipo, descripcion)
                 VALUES (?, ?, ?, ?)',
                [$cargoId, $sedeId, $equipo, $descripcion]
            );
        }
        Response::success(['id' => $id], 'Equipo guardado');
        break;

    case 'equipos_eliminar':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) Response::error('ID requerido');
        DB::execute('UPDATE equipos_autorizados SET activo=0 WHERE id=?', [$id]);
        Response::success(null, 'Equipo eliminado');
        break;

    // ---- Festivos ----
    case 'festivos_listar':
        $anio = (int)($_GET['anio'] ?? date('Y'));
        $rows = DB::fetchAll(
            'SELECT * FROM festivos WHERE YEAR(fecha) = ? ORDER BY fecha',
            [$anio]
        );
        Response::success($rows);
        break;

    case 'festivos_guardar':
        if ($method !== 'POST') Response::error('Método no permitido', 405);
        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $fecha = $body['fecha'] ?? '';
        $nom   = Helpers::clean($body['nombre'] ?? '');
        $tipo  = in_array($body['tipo'] ?? '', ['fijo','trasladable','especial'])
                    ? $body['tipo'] : 'fijo';

        if (!$fecha || !$nom) Response::error('Fecha y nombre requeridos');

        DB::execute(
            'INSERT INTO festivos (fecha, nombre, tipo) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE nombre=VALUES(nombre), tipo=VALUES(tipo)',
            [$fecha, $nom, $tipo]
        );
        Response::success(null, 'Festivo guardado');
        break;

    case 'festivos_eliminar':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) Response::error('ID requerido');
        DB::execute('DELETE FROM festivos WHERE id=?', [$id]);
        Response::success(null, 'Festivo eliminado');
        break;

    case 'hostname_actual':
        Response::success(['hostname' => Helpers::getClientHostname()]);
        break;

    default:
        Response::error('Acción no válida', 404);
}
