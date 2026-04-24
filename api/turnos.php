<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
Auth::requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    // ---- Listar turnos disponibles ----
    case 'listar':
        $rows = DB::fetchAll('SELECT * FROM turnos WHERE activo = 1 ORDER BY hora_inicio ASC');
        Response::success($rows);
        break;

    // ---- Crear turno (admin) ----
    case 'crear':
        Auth::requireRole('admin');
        if ($method !== 'POST') Response::error('Método no permitido', 405);

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $nombre    = Helpers::clean($body['nombre'] ?? '');
        $horaIni   = $body['hora_inicio'] ?? '';
        $horaFin   = $body['hora_fin'] ?? '';
        $nocturno  = (int)($body['nocturno'] ?? 0);

        if (!$nombre || !$horaIni || !$horaFin) Response::error('Datos incompletos.');

        // Auto-detectar nocturno si hora_fin < hora_inicio
        if (!$nocturno && $horaFin < $horaIni) $nocturno = 1;

        $id = DB::insert(
            'INSERT INTO turnos (nombre, hora_inicio, hora_fin, nocturno) VALUES (?, ?, ?, ?)',
            [$nombre, $horaIni, $horaFin, $nocturno]
        );
        Response::success(['id' => $id], 'Turno creado');
        break;

    // ---- Editar turno (admin) ----
    case 'editar':
        Auth::requireRole('admin');
        if ($method !== 'POST') Response::error('Método no permitido', 405);

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) Response::error('ID requerido');

        DB::execute(
            'UPDATE turnos SET nombre=?, hora_inicio=?, hora_fin=?, nocturno=? WHERE id=?',
            [
                Helpers::clean($body['nombre'] ?? ''),
                $body['hora_inicio'] ?? '',
                $body['hora_fin'] ?? '',
                (int)($body['nocturno'] ?? 0),
                $id,
            ]
        );
        Response::success(null, 'Turno actualizado');
        break;

    // ---- Eliminar (desactivar) turno ----
    case 'eliminar':
        Auth::requireRole('admin');
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) Response::error('ID requerido');
        DB::execute('UPDATE turnos SET activo = 0 WHERE id = ?', [$id]);
        Response::success(null, 'Turno desactivado');
        break;

    // ---- Asignaciones: listar por empleado ----
    case 'asignaciones':
        $uid = (int)($_GET['usuario_id'] ?? 0);

        if ($uid) {
            // Empleado específico — solo él mismo o supervisores/admin
            if ($uid !== Auth::id() && !Auth::hasRole('admin', 'supervisor')) {
                Response::error('Sin permiso', 403);
            }
            $rows = DB::fetchAll(
                'SELECT at.*,
                        t.nombre AS turno_nombre, t.hora_inicio, t.hora_fin, t.nocturno,
                        emp.nombre AS usuario_nombre, emp.apellido AS usuario_apellido,
                        sup.nombre AS supervisor_nombre
                 FROM asignacion_turnos at
                 JOIN turnos t   ON t.id  = at.turno_id
                 JOIN usuarios emp ON emp.id = at.usuario_id
                 LEFT JOIN usuarios sup ON sup.id = at.supervisor_id
                 WHERE at.usuario_id = ?
                 ORDER BY at.fecha_inicio DESC',
                [$uid]
            );
        } elseif (Auth::hasRole('admin', 'supervisor')) {
            // Admin/supervisor sin filtro: todos los empleados (supervisor filtra por su sede)
            $sedeId = Auth::hasRole('supervisor') ? ((int)(Auth::user()['sede_id'] ?? 0) ?: null) : null;
            $rows = DB::fetchAll(
                'SELECT at.*,
                        t.nombre AS turno_nombre, t.hora_inicio, t.hora_fin, t.nocturno,
                        emp.nombre AS usuario_nombre, emp.apellido AS usuario_apellido,
                        sup.nombre AS supervisor_nombre
                 FROM asignacion_turnos at
                 JOIN turnos t     ON t.id   = at.turno_id
                 JOIN usuarios emp ON emp.id = at.usuario_id
                 LEFT JOIN usuarios sup ON sup.id = at.supervisor_id
                 WHERE (? IS NULL OR emp.sede_id = ?)
                 ORDER BY at.fecha_inicio DESC',
                [$sedeId, $sedeId]
            );
        } else {
            // Empleado sin filtro: solo sus propias asignaciones
            $rows = DB::fetchAll(
                'SELECT at.*,
                        t.nombre AS turno_nombre, t.hora_inicio, t.hora_fin, t.nocturno,
                        emp.nombre AS usuario_nombre, emp.apellido AS usuario_apellido,
                        sup.nombre AS supervisor_nombre
                 FROM asignacion_turnos at
                 JOIN turnos t     ON t.id   = at.turno_id
                 JOIN usuarios emp ON emp.id = at.usuario_id
                 LEFT JOIN usuarios sup ON sup.id = at.supervisor_id
                 WHERE at.usuario_id = ?
                 ORDER BY at.fecha_inicio DESC',
                [Auth::id()]
            );
        }
        Response::success($rows);
        break;

    // ---- Asignar turno a empleado (supervisor / admin) ----
    case 'asignar':
        Auth::requireRole('admin', 'supervisor');
        if ($method !== 'POST') Response::error('Método no permitido', 405);

        $body      = json_decode(file_get_contents('php://input'), true) ?? [];
        $uid       = (int)($body['usuario_id'] ?? 0);
        $turnoId   = (int)($body['turno_id'] ?? 0);
        $fechaIni  = $body['fecha_inicio'] ?? '';
        $fechaFin  = $body['fecha_fin'] ?? '';
        $dias      = implode(',', array_map('intval', $body['dias_semana'] ?? [1,2,3,4,5]));
        $obs       = Helpers::clean($body['observacion'] ?? '');

        if (!$uid || !$turnoId || !$fechaIni || !$fechaFin) {
            Response::error('Datos incompletos.');
        }

        if ($fechaFin < $fechaIni) Response::error('La fecha fin debe ser >= fecha inicio.');

        $id = DB::insert(
            'INSERT INTO asignacion_turnos
                (usuario_id, turno_id, fecha_inicio, fecha_fin, dias_semana,
                 aprobado, supervisor_id, observacion)
             VALUES (?, ?, ?, ?, ?, 1, ?, ?)',
            [$uid, $turnoId, $fechaIni, $fechaFin, $dias, Auth::id(), $obs]
        );
        Response::success(['id' => $id], 'Turno asignado correctamente');
        break;

    // ---- Aprobar / rechazar asignación ----
    case 'aprobar_asignacion':
        Auth::requireRole('admin', 'supervisor');
        if ($method !== 'POST') Response::error('Método no permitido', 405);

        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $id    = (int)($body['id'] ?? 0);
        $ap    = (int)($body['aprobado'] ?? 0);
        $obs   = Helpers::clean($body['observacion'] ?? '');

        DB::execute(
            'UPDATE asignacion_turnos
             SET aprobado=?, supervisor_id=?, observacion=?
             WHERE id=?',
            [$ap, Auth::id(), $obs, $id]
        );
        Response::success(null, $ap ? 'Asignación aprobada' : 'Asignación rechazada');
        break;

    default:
        Response::error('Acción no válida', 404);
}
