<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
Auth::requireLogin();

$action = $_GET['action'] ?? '';

switch ($action) {

    // -------------------------------------------------------
    // Reporte de marcaciones por rango de fechas
    // -------------------------------------------------------
    case 'marcaciones':
        $uid   = (int)($_GET['usuario_id'] ?? Auth::id());
        if ($uid !== Auth::id() && !Auth::hasRole('admin', 'supervisor')) {
            Response::error('Sin permiso', 403);
        }

        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');

        $rows = DB::fetchAll(
            'SELECT rd.fecha,
                    DAYOFWEEK(rd.fecha) - 1 AS dow,
                    rd.hora_entrada, rd.hora_salida,
                    rd.minutos_brutos, rd.minutos_descanso, rd.minutos_netos,
                    rd.horas_netas, rd.es_festivo, rd.estado_dia,
                    f.nombre AS nombre_festivo,
                    me.estado AS estado_entrada, me.aprobado AS aprobado_entrada,
                    ms.estado AS estado_salida,  ms.aprobado AS aprobado_salida,
                    me.observacion AS obs_entrada, ms.observacion AS obs_salida,
                    me.auto_cerrado
             FROM resumen_diario rd
             LEFT JOIN festivos f ON f.fecha = rd.fecha
             LEFT JOIN marcaciones me ON me.id = rd.entrada_id
             LEFT JOIN marcaciones ms ON ms.id = rd.salida_id
             WHERE rd.usuario_id = ? AND rd.fecha BETWEEN ? AND ?
             ORDER BY rd.fecha ASC',
            [$uid, $desde, $hasta]
        );

        $diasSemana = DIAS_SEMANA;
        foreach ($rows as &$r) {
            $r['dia_nombre'] = $diasSemana[(int)$r['dow']];
        }
        unset($r);

        $usuario = DB::fetchOne(
            'SELECT u.nombre, u.apellido, c.nombre AS cargo_nombre, s.nombre AS sede_nombre
             FROM usuarios u
             LEFT JOIN cargos c ON c.id = u.cargo_id
             LEFT JOIN sedes s ON s.id = u.sede_id
             WHERE u.id = ?',
            [$uid]
        );

        Response::success(['usuario' => $usuario, 'registros' => $rows]);
        break;

    // -------------------------------------------------------
    // Preliquidación semanal
    // -------------------------------------------------------
    case 'preliquidacion':
        $uid  = (int)($_GET['usuario_id'] ?? Auth::id());
        if ($uid !== Auth::id() && !Auth::hasRole('admin', 'supervisor')) {
            Response::error('Sin permiso', 403);
        }

        $fecha   = $_GET['fecha'] ?? date('Y-m-d');
        $domingo = Preliquidacion::domingoDeSemanaDe($fecha);
        $data    = Preliquidacion::calcularSemana($uid, $domingo);

        $usuario = DB::fetchOne(
            'SELECT u.nombre, u.apellido, c.nombre AS cargo_nombre
             FROM usuarios u LEFT JOIN cargos c ON c.id = u.cargo_id WHERE u.id = ?',
            [$uid]
        );
        $data['usuario'] = $usuario;

        Response::success($data);
        break;

    // -------------------------------------------------------
    // Exportar a Excel (usa PhpSpreadsheet o CSV si no disponible)
    // -------------------------------------------------------
    case 'exportar_excel':
        Auth::requireRole('admin', 'supervisor');

        $uid   = (int)($_GET['usuario_id'] ?? 0);
        $desde = $_GET['desde'] ?? date('Y-m-01');
        $hasta = $_GET['hasta'] ?? date('Y-m-d');
        $tipo  = $_GET['tipo'] ?? 'marcaciones'; // marcaciones | preliquidacion

        if (!$uid) Response::error('usuario_id requerido');

        $usuario = DB::fetchOne(
            'SELECT u.nombre, u.apellido, c.nombre AS cargo, s.nombre AS sede
             FROM usuarios u
             LEFT JOIN cargos c ON c.id = u.cargo_id
             LEFT JOIN sedes s ON s.id = u.sede_id
             WHERE u.id = ?',
            [$uid]
        );

        if ($tipo === 'preliquidacion') {
            _exportarPreliquidacion($uid, $desde, $hasta, $usuario);
        } else {
            _exportarMarcaciones($uid, $desde, $hasta, $usuario);
        }
        exit;

    // -------------------------------------------------------
    // Resumen para dashboard (supervisor/admin)
    // -------------------------------------------------------
    case 'dashboard':
        Auth::requireRole('admin', 'supervisor');

        $sedeId = Auth::hasRole('supervisor') ? (int)(Auth::user()['sede_id'] ?? 0) : null;
        $hoy    = date('Y-m-d');

        $marcacionesHoy = (int)DB::fetchOne(
            'SELECT COUNT(*) AS c FROM marcaciones m
             JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.fecha = ? AND m.tipo = \'entrada\'
               AND (? IS NULL OR u.sede_id = ?)',
            [$hoy, $sedeId, $sedeId]
        )['c'];

        $pendientesAprobacion = (int)DB::fetchOne(
            'SELECT COUNT(*) AS c FROM marcaciones m
             JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.aprobado = \'pendiente\'
               AND m.estado NOT IN (\'puntual\',\'cierre_automatico\')
               AND (? IS NULL OR u.sede_id = ?)',
            [$sedeId, $sedeId]
        )['c'];

        $tardanzasHoy = (int)DB::fetchOne(
            'SELECT COUNT(*) AS c FROM marcaciones m
             JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.fecha = ? AND m.estado = \'llegada_tarde\'
               AND (? IS NULL OR u.sede_id = ?)',
            [$hoy, $sedeId, $sedeId]
        )['c'];

        Response::success([
            'marcaciones_hoy'       => $marcacionesHoy,
            'pendientes_aprobacion' => $pendientesAprobacion,
            'tardanzas_hoy'         => $tardanzasHoy,
        ]);
        break;

    default:
        Response::error('Acción no válida', 404);
}

// ============================================================
// Funciones internas de exportación
// ============================================================

function _exportarMarcaciones(int $uid, string $desde, string $hasta, array $usuario): void
{
    $rows = DB::fetchAll(
        'SELECT rd.fecha,
                DAYOFWEEK(rd.fecha)-1 AS dow,
                rd.hora_entrada, rd.hora_salida,
                rd.horas_netas, rd.minutos_descanso,
                rd.es_festivo, rd.estado_dia,
                f.nombre AS festivo,
                me.estado AS estado_entrada, me.observacion AS obs_entrada,
                ms.estado AS estado_salida, ms.observacion AS obs_salida,
                me.auto_cerrado
         FROM resumen_diario rd
         LEFT JOIN festivos f ON f.fecha = rd.fecha
         LEFT JOIN marcaciones me ON me.id = rd.entrada_id
         LEFT JOIN marcaciones ms ON ms.id = rd.salida_id
         WHERE rd.usuario_id = ? AND rd.fecha BETWEEN ? AND ?
         ORDER BY rd.fecha ASC',
        [$uid, $desde, $hasta]
    );

    $nombre = $usuario['nombre'] . ' ' . $usuario['apellido'];
    $filename = 'marcaciones_' . preg_replace('/\s+/', '_', $nombre) . '_' . $desde . '_' . $hasta . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $fh = fopen('php://output', 'w');
    fprintf($fh, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 para Excel

    fputcsv($fh, ['REPORTE DE MARCACIONES'], ';');
    fputcsv($fh, ['Empleado:', $nombre], ';');
    fputcsv($fh, ['Cargo:', $usuario['cargo'] ?? ''], ';');
    fputcsv($fh, ['Sede:', $usuario['sede'] ?? ''], ';');
    fputcsv($fh, ['Período:', $desde . ' al ' . $hasta], ';');
    fputcsv($fh, [], ';');

    fputcsv($fh, [
        'Fecha', 'Día', 'Festivo', 'Entrada', 'Salida',
        'H. Laboradas', 'Min. Descanso', 'Estado Entrada',
        'Estado Salida', 'Obs. Entrada', 'Obs. Salida', 'Auto-cerrado'
    ], ';');

    $totalHoras = 0.0;
    foreach ($rows as $r) {
        $dia     = DIAS_SEMANA[(int)$r['dow']] ?? '';
        $festivo = $r['es_festivo'] ? ($r['festivo'] ?? 'Sí') : 'No';
        $totalHoras += (float)$r['horas_netas'];

        fputcsv($fh, [
            $r['fecha'], $dia, $festivo,
            $r['hora_entrada'] ?? '', $r['hora_salida'] ?? '',
            number_format((float)$r['horas_netas'], 2, '.', ''),
            $r['minutos_descanso'],
            $r['estado_entrada'] ?? '', $r['estado_salida'] ?? '',
            $r['obs_entrada'] ?? '', $r['obs_salida'] ?? '',
            $r['auto_cerrado'] ? 'Sí' : 'No',
        ], ';');
    }

    fputcsv($fh, [], ';');
    fputcsv($fh, ['TOTAL HORAS:', number_format($totalHoras, 2, '.', '')], ';');
    fclose($fh);
}

function _exportarPreliquidacion(int $uid, string $desde, string $hasta, array $usuario): void
{
    // Obtener todos los domingos en el rango
    $dt       = new \DateTime(Preliquidacion::domingoDeSemanaDe($desde));
    $dtFin    = new \DateTime($hasta);
    $semanas  = [];
    while ($dt <= $dtFin) {
        $semanas[] = Preliquidacion::calcularSemana($uid, $dt->format('Y-m-d'));
        $dt->modify('+7 days');
    }

    $nombre   = $usuario['nombre'] . ' ' . $usuario['apellido'];
    $filename = 'preliquidacion_' . preg_replace('/\s+/', '_', $nombre) . '_' . $desde . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $fh = fopen('php://output', 'w');
    fprintf($fh, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($fh, ['PRELIQUIDACIÓN DE NÓMINA'], ';');
    fputcsv($fh, ['Empleado:', $nombre], ';');
    fputcsv($fh, ['Cargo:', $usuario['cargo'] ?? ''], ';');
    fputcsv($fh, [], ';');

    foreach ($semanas as $s) {
        fputcsv($fh, ['SEMANA: ' . $s['semana_inicio'] . ' al ' . $s['semana_fin']], ';');
        fputcsv($fh, [
            'Fecha', 'Día', 'Tipo', 'Festivo', 'Entrada', 'Salida', 'Horas'
        ], ';');

        foreach ($s['detalle'] as $d) {
            fputcsv($fh, [
                $d['fecha'], $d['dia'], strtoupper($d['tipo']),
                $d['es_festivo'] ? ($d['nombre_festivo'] ?? 'Sí') : 'No',
                $d['hora_entrada'] ?? '', $d['hora_salida'] ?? '',
                number_format((float)$d['horas_netas'], 2, '.', ''),
            ], ';');
        }

        fputcsv($fh, [], ';');
        fputcsv($fh, ['Días laborados (Lun-Sáb):', $s['dias_normales']], ';');
        fputcsv($fh, ['Fórmula horas debidas:', $s['dias_normales'] . ' × 7.33 = ' . $s['horas_debidas']], ';');
        fputcsv($fh, ['Horas laboradas (normales):', number_format($s['horas_normales'], 2, '.', '')], ';');
        fputcsv($fh, ['Horas festivos:', number_format($s['horas_festivos'], 2, '.', '')], ';');
        fputcsv($fh, ['Horas domingos:', number_format($s['horas_domingos'], 2, '.', '')], ';');
        fputcsv($fh, ['Diferencia (extra/faltante):', number_format($s['diferencia'], 2, '.', '')], ';');
        fputcsv($fh, [], ';');
        fputcsv($fh, ['---'], ';');
        fputcsv($fh, [], ';');
    }

    fclose($fh);
}
