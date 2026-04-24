<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
Auth::requireLogin();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {

    // -------------------------------------------------------
    // GET: estado actual del empleado (¿hay entrada abierta?)
    // -------------------------------------------------------
    case 'estado':
        $uid   = (int)($_GET['usuario_id'] ?? Auth::id());
        if ($uid !== Auth::id() && !Auth::hasRole('admin', 'supervisor')) {
            Response::error('Sin permiso', 403);
        }

        $hoy     = date('Y-m-d');
        $entrada = DB::fetchOne(
            'SELECT m.*, s.nombre AS sede_nombre
             FROM marcaciones m
             LEFT JOIN sedes s ON s.id = m.sede_id
             WHERE m.usuario_id = ? AND m.tipo = \'entrada\' AND m.fecha = ?
             ORDER BY m.hora ASC LIMIT 1',
            [$uid, $hoy]
        );

        $salida = null;
        if ($entrada) {
            $salida = DB::fetchOne(
                'SELECT id, hora, estado FROM marcaciones
                 WHERE usuario_id = ? AND tipo = \'salida\'
                   AND (fecha = ? OR fecha = DATE_ADD(?, INTERVAL 1 DAY))
                 ORDER BY fecha ASC, hora ASC LIMIT 1',
                [$uid, $hoy, $hoy]
            );
        }

        $turno = Turno::obtenerAsignado($uid, $hoy);

        Response::success([
            'turno'       => $turno,
            'entrada'     => $entrada,
            'salida'      => $salida,
            'puede_marcar'=> !$entrada || ($entrada && !$salida),
            'tipo_proximo'=> !$entrada ? 'entrada' : 'salida',
        ]);
        break;

    // -------------------------------------------------------
    // POST: registrar marcación
    // -------------------------------------------------------
    case 'marcar':
        if ($method !== 'POST') Response::error('Método no permitido', 405);

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $uid  = Auth::id();
        $user = Auth::user();

        $tipo        = $body['tipo'] ?? '';
        $observacion = Helpers::clean($body['observacion'] ?? '');

        if (!in_array($tipo, ['entrada', 'salida'], true)) {
            Response::error('Tipo inválido. Use entrada o salida.');
        }

        $ahora    = new \DateTime();
        $fecha    = $ahora->format('Y-m-d');
        $hora     = $ahora->format('H:i:s');
        $fechaHora= $ahora->format('Y-m-d H:i:s');

        // Verificar equipo si el cargo tiene restricción
        if (!Auth::hasRole('admin', 'supervisor')) {
            if (!Helpers::equipoAutorizado((int)$user['cargo_id'], (int)$user['sede_id'])) {
                Response::error('No autorizado desde este equipo.');
            }
        }

        // Verificar que no haya doble entrada o doble salida
        if ($tipo === 'entrada') {
            $existing = DB::fetchOne(
                'SELECT id FROM marcaciones
                 WHERE usuario_id = ? AND tipo = \'entrada\' AND fecha = ?',
                [$uid, $fecha]
            );
            if ($existing) Response::error('Ya registró entrada hoy.');
        }

        if ($tipo === 'salida') {
            $entradaHoy = DB::fetchOne(
                'SELECT m.fecha AS fecha_entrada FROM marcaciones m
                 JOIN turnos t ON t.id = (
                     SELECT turno_id FROM asignacion_turnos
                     WHERE usuario_id = ? AND aprobado = 1
                       AND fecha_inicio <= ? AND fecha_fin >= ?
                     ORDER BY id DESC LIMIT 1
                 )
                 WHERE m.usuario_id = ? AND m.tipo = \'entrada\'
                   AND (m.fecha = ? OR (t.nocturno = 1 AND m.fecha = DATE_SUB(?, INTERVAL 1 DAY)))
                 ORDER BY m.fecha ASC, m.hora ASC LIMIT 1',
                [$uid, $fecha, $fecha, $uid, $fecha, $fecha]
            );

            if (!$entradaHoy) {
                // Fallback simple: buscar entrada de hoy o ayer
                $entradaHoy = DB::fetchOne(
                    'SELECT id FROM marcaciones
                     WHERE usuario_id = ? AND tipo = \'entrada\'
                       AND fecha >= DATE_SUB(?, INTERVAL 1 DAY)
                       AND fecha <= ?
                     ORDER BY fecha DESC, hora DESC LIMIT 1',
                    [$uid, $fecha, $fecha]
                );
            }
            if (!$entradaHoy) Response::error('No hay entrada registrada para cerrar.');

            // Verificar que no haya salida ya registrada
            $salidaExiste = DB::fetchOne(
                'SELECT id FROM marcaciones
                 WHERE usuario_id = ? AND tipo = \'salida\'
                   AND (fecha = ? OR fecha = DATE_SUB(?, INTERVAL 1 DAY))',
                [$uid, $fecha, $fecha]
            );
            if ($salidaExiste) Response::error('Ya registró salida en este período.');
        }

        // Obtener turno asignado
        $turno = Turno::obtenerAsignado($uid, $fecha);

        // Tolerancias
        $cargoId         = (int)$user['cargo_id'];
        $tolAntes        = (int)(Helpers::getParam('tolerancia_entrada_antes', $cargoId) ?? 10);
        $tolDespues      = (int)(Helpers::getParam('tolerancia_entrada_despues', $cargoId) ?? 15);
        $tolSalidaAntes  = (int)(Helpers::getParam('tolerancia_salida_antes', $cargoId) ?? 10);
        $tolSalidaDespues= (int)(Helpers::getParam('tolerancia_salida_despues', $cargoId) ?? 30);

        $estado         = 'sin_turno';
        $minDiferencia  = 0;

        if ($turno) {
            $toleranciaAntes  = $tipo === 'entrada' ? $tolAntes : $tolSalidaAntes;
            $toleranciaDespues= $tipo === 'entrada' ? $tolDespues : $tolSalidaDespues;
            $calc = Turno::calcularEstado($tipo, $fechaHora, $turno, $toleranciaAntes, $toleranciaDespues);
            $estado        = $calc['estado'];
            $minDiferencia = $calc['minutos_diferencia'];
        }

        $hostname = Helpers::getClientHostname();

        $id = DB::insert(
            'INSERT INTO marcaciones
                (usuario_id, tipo, fecha, hora, fecha_hora, sede_id,
                 nombre_equipo, observacion, estado, minutos_diferencia)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $uid, $tipo, $fecha, $hora, $fechaHora,
                $user['sede_id'], $hostname, $observacion,
                $estado, $minDiferencia,
            ]
        );

        // Recalcular resumen del día
        $fechaResumen = $fecha;
        if ($turno && $turno['nocturno'] && $tipo === 'salida') {
            // El resumen corresponde al día de la entrada
            $entradaDia = DB::fetchOne(
                'SELECT fecha FROM marcaciones
                 WHERE usuario_id = ? AND tipo = \'entrada\'
                   AND fecha >= DATE_SUB(?, INTERVAL 1 DAY)
                 ORDER BY fecha ASC, hora ASC LIMIT 1',
                [$uid, $fecha]
            );
            if ($entradaDia) $fechaResumen = $entradaDia['fecha'];
        }
        Turno::recalcularResumenDia($uid, $fechaResumen);

        $mensajes = [
            'puntual'          => '✓ Marcación registrada correctamente.',
            'llegada_tarde'    => "⚠ Llegada tarde ({$minDiferencia} min). Requiere aprobación.",
            'salida_temprana'  => "⚠ Salida antes del turno ({$minDiferencia} min).",
            'salida_tarde'     => "⚠ Salida después del turno ({$minDiferencia} min).",
            'fuera_turno'      => '⚠ Marcación fuera del horario de turno.',
            'sin_turno'        => '⚠ No tiene turno asignado para hoy.',
        ];

        Response::success([
            'id'     => $id,
            'estado' => $estado,
            'hora'   => $hora,
        ], $mensajes[$estado] ?? 'Marcación registrada.');
        break;

    // -------------------------------------------------------
    // GET: historial de marcaciones
    // -------------------------------------------------------
    case 'historial':
        $uid      = (int)($_GET['usuario_id'] ?? Auth::id());
        if ($uid !== Auth::id() && !Auth::hasRole('admin', 'supervisor')) {
            Response::error('Sin permiso', 403);
        }
        $desde    = $_GET['desde'] ?? date('Y-m-01');
        $hasta    = $_GET['hasta'] ?? date('Y-m-d');
        $page     = max(1, (int)($_GET['page'] ?? 1));
        $perPage  = 50;
        $offset   = ($page - 1) * $perPage;

        $total = (int)DB::fetchOne(
            'SELECT COUNT(*) AS c FROM marcaciones
             WHERE usuario_id = ? AND fecha BETWEEN ? AND ?',
            [$uid, $desde, $hasta]
        )['c'];

        $rows = DB::fetchAll(
            'SELECT m.*, s.nombre AS sede_nombre
             FROM marcaciones m
             LEFT JOIN sedes s ON s.id = m.sede_id
             WHERE m.usuario_id = ? AND m.fecha BETWEEN ? AND ?
             ORDER BY m.fecha DESC, m.hora DESC
             LIMIT ? OFFSET ?',
            [$uid, $desde, $hasta, $perPage, $offset]
        );

        Response::paginated($rows, $total, $page, $perPage);
        break;

    // -------------------------------------------------------
    // POST: aprobar / rechazar (supervisor / admin)
    // -------------------------------------------------------
    case 'aprobar':
        if ($method !== 'POST') Response::error('Método no permitido', 405);
        Auth::requireRole('admin', 'supervisor');

        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $mId   = (int)($body['id'] ?? 0);
        $dec   = $body['decision'] ?? ''; // aprobado | rechazado
        $obs   = Helpers::clean($body['observacion'] ?? '');

        if (!$mId || !in_array($dec, ['aprobado', 'rechazado'], true)) {
            Response::error('Parámetros inválidos.');
        }

        DB::execute(
            'UPDATE marcaciones
             SET aprobado = ?, supervisor_id = ?, supervisor_observacion = ?, aprobado_at = NOW()
             WHERE id = ?',
            [$dec, Auth::id(), $obs, $mId]
        );

        Response::success(null, 'Marcación ' . $dec);
        break;

    // -------------------------------------------------------
    // GET: pendientes de aprobación
    // -------------------------------------------------------
    case 'pendientes':
        Auth::requireRole('admin', 'supervisor');
        $sedeId = Auth::user()['sede_id'];

        $rows = DB::fetchAll(
            'SELECT m.*, u.nombre, u.apellido, c.nombre AS cargo_nombre, s.nombre AS sede_nombre
             FROM marcaciones m
             JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN cargos c ON c.id = u.cargo_id
             LEFT JOIN sedes s ON s.id = m.sede_id
             WHERE m.aprobado = \'pendiente\'
               AND m.estado NOT IN (\'puntual\', \'cierre_automatico\')
               AND (? IS NULL OR m.sede_id = ?)
             ORDER BY m.fecha_hora DESC',
            [$sedeId, $sedeId]
        );

        Response::success($rows);
        break;

    default:
        Response::error('Acción no válida', 404);
}
