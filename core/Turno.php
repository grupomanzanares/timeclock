<?php
declare(strict_types=1);

class Turno
{
    /**
     * Obtiene el turno activo asignado al usuario para una fecha dada.
     */
    public static function obtenerAsignado(int $usuarioId, string $fecha): ?array
    {
        $dow = (int)date('w', strtotime($fecha)); // 0=Dom ... 6=Sab

        return DB::fetchOne(
            'SELECT at.*, t.nombre, t.hora_inicio, t.hora_fin, t.nocturno, t.minutos_descanso
             FROM asignacion_turnos at
             JOIN turnos t ON t.id = at.turno_id
             WHERE at.usuario_id = ?
               AND at.aprobado = 1
               AND at.fecha_inicio <= ?
               AND at.fecha_fin >= ?
               AND FIND_IN_SET(?, at.dias_semana)
             ORDER BY at.id DESC
             LIMIT 1',
            [$usuarioId, $fecha, $fecha, (string)$dow]
        );
    }

    /**
     * Calcula el estado de una marcación respecto al turno.
     * Retorna array con 'estado' y 'minutos_diferencia'.
     */
    public static function calcularEstado(
        string $tipo,
        string $fechaHora,
        array  $turno,
        int    $toleranciaAntes,
        int    $toleranciaDespues
    ): array {
        $dt = new \DateTime($fechaHora);

        // Construir DateTime del límite del turno para la comparación
        $fechaMarca = $dt->format('Y-m-d');

        if ($tipo === 'entrada') {
            // El inicio del turno puede ser el mismo día
            $inicioTurno = new \DateTime($fechaMarca . ' ' . $turno['hora_inicio']);

            // Ventana permitida: [inicio - toleranciaAntes, inicio + toleranciaDespues]
            $ventanaInicio = (clone $inicioTurno)->modify("-{$toleranciaAntes} minutes");
            $ventanaFin    = (clone $inicioTurno)->modify("+{$toleranciaDespues} minutes");

            $diffMin = Helpers::diffMinutos($inicioTurno, $dt); // positivo = tarde

            if ($dt < $ventanaInicio) {
                return ['estado' => 'fuera_turno', 'minutos_diferencia' => $diffMin];
            }
            if ($diffMin <= 0) {
                return ['estado' => 'puntual', 'minutos_diferencia' => $diffMin];
            }
            if ($diffMin <= $toleranciaDespues) {
                return ['estado' => 'puntual', 'minutos_diferencia' => $diffMin];
            }
            return ['estado' => 'llegada_tarde', 'minutos_diferencia' => $diffMin];
        }

        // Salida
        $finBase = $fechaMarca . ' ' . $turno['hora_fin'];
        // Si es nocturno, la hora de fin es al día siguiente
        if ($turno['nocturno']) {
            $finDt = new \DateTime($fechaMarca . ' ' . $turno['hora_fin']);
            $iniDt = new \DateTime($fechaMarca . ' ' . $turno['hora_inicio']);
            if ($finDt <= $iniDt) {
                $finDt->modify('+1 day');
            }
            $finTurno = $finDt;
        } else {
            $finTurno = new \DateTime($finBase);
        }

        $diffMin = Helpers::diffMinutos($finTurno, $dt); // positivo = salida tarde

        if ($diffMin < -$toleranciaAntes) {
            return ['estado' => 'salida_temprana', 'minutos_diferencia' => $diffMin];
        }
        if ($diffMin > $toleranciaDespues) {
            return ['estado' => 'salida_tarde', 'minutos_diferencia' => $diffMin];
        }
        return ['estado' => 'puntual', 'minutos_diferencia' => $diffMin];
    }

    /**
     * Cierre automático de días pendientes.
     * Se llama al inicio de sesión del empleado.
     * Retorna array de fechas cerradas automáticamente.
     */
    public static function cerrarPendientes(int $usuarioId): array
    {
        $cerradas = [];
        $hoy = date('Y-m-d');

        // Buscar entradas sin salida de días anteriores
        $pendientes = DB::fetchAll(
            'SELECT m.id AS entrada_id, m.fecha, m.hora AS hora_entrada,
                    at.turno_id, t.hora_fin, t.nocturno, t.hora_inicio
             FROM marcaciones m
             JOIN asignacion_turnos at ON at.usuario_id = m.usuario_id
                 AND at.fecha_inicio <= m.fecha AND at.fecha_fin >= m.fecha
                 AND at.aprobado = 1
                 AND FIND_IN_SET(DAYOFWEEK(m.fecha)-1, at.dias_semana)
             JOIN turnos t ON t.id = at.turno_id
             WHERE m.usuario_id = ?
               AND m.tipo = \'entrada\'
               AND m.fecha < ?
               AND NOT EXISTS (
                   SELECT 1 FROM marcaciones m2
                   WHERE m2.usuario_id = m.usuario_id
                     AND m2.tipo = \'salida\'
                     AND (
                         m2.fecha = m.fecha
                         OR (t.nocturno = 1 AND m2.fecha = DATE_ADD(m.fecha, INTERVAL 1 DAY))
                     )
               )
             ORDER BY m.fecha ASC',
            [$usuarioId, $hoy]
        );

        foreach ($pendientes as $p) {
            // Para turno nocturno: no cerrar si la fecha fin es "hoy" (aún no ha terminado)
            if ($p['nocturno']) {
                $fechaFinTurno = date('Y-m-d', strtotime($p['fecha'] . ' +1 day'));
                if ($fechaFinTurno >= $hoy) continue; // Esperar que termine
            }

            // Construir fecha-hora de cierre = fecha + hora_fin del turno
            if ($p['nocturno']) {
                $fechaCierre = date('Y-m-d', strtotime($p['fecha'] . ' +1 day'));
                $fechaHoraCierre = $fechaCierre . ' ' . $p['hora_fin'] . ':00';
            } else {
                $fechaHoraCierre = $p['fecha'] . ' ' . $p['hora_fin'] . ':00';
            }

            DB::insert(
                'INSERT INTO marcaciones
                    (usuario_id, tipo, fecha, hora, fecha_hora, observacion, estado, auto_cerrado, aprobado)
                 VALUES (?, \'salida\', ?, ?, ?, ?, \'cierre_automatico\', 1, \'aprobado\')',
                [
                    $usuarioId,
                    $p['nocturno'] ? $fechaCierre : $p['fecha'],
                    $p['hora_fin'],
                    $fechaHoraCierre,
                    'Cierre automático por falta de marcación de salida — ' . $p['hora_fin'],
                ]
            );

            self::recalcularResumenDia($usuarioId, $p['fecha']);
            $cerradas[] = $p['fecha'];
        }

        return $cerradas;
    }

    /**
     * Recalcula y guarda el resumen del día.
     */
    public static function recalcularResumenDia(int $usuarioId, string $fecha): void
    {
        $entrada = DB::fetchOne(
            'SELECT id, hora FROM marcaciones
             WHERE usuario_id = ? AND tipo = \'entrada\' AND fecha = ?
             ORDER BY hora ASC LIMIT 1',
            [$usuarioId, $fecha]
        );

        if (!$entrada) return;

        // Para turno nocturno la salida puede ser al día siguiente
        $salida = DB::fetchOne(
            'SELECT id, hora, fecha FROM marcaciones
             WHERE usuario_id = ? AND tipo = \'salida\'
               AND (fecha = ? OR fecha = DATE_ADD(?, INTERVAL 1 DAY))
             ORDER BY fecha ASC, hora ASC LIMIT 1',
            [$usuarioId, $fecha, $fecha]
        );

        $dtEntrada = new \DateTime($fecha . ' ' . $entrada['hora']);
        $dow       = (int)$dtEntrada->format('w');
        $esFestivo = Helpers::esFestivo($fecha) ? 1 : 0;

        // Minutos de descanso: del turno asignado al día
        $turnoDelDia = self::obtenerAsignado($usuarioId, $fecha);
        $minDesc     = (int)($turnoDelDia['minutos_descanso'] ?? 0);

        if ($salida) {
            $dtSalida  = new \DateTime($salida['fecha'] . ' ' . $salida['hora']);
            $minBrutos = Helpers::diffMinutos($dtEntrada, $dtSalida);
            $minNetos  = max(0, $minBrutos - $minDesc);
            $estado    = 'completo';
        } else {
            $minBrutos = 0;
            $minNetos  = 0;
            $estado    = 'pendiente_salida';
        }

        DB::execute(
            'INSERT INTO resumen_diario
                (usuario_id, fecha, entrada_id, salida_id, hora_entrada, hora_salida,
                 minutos_brutos, minutos_descanso, minutos_netos, horas_netas,
                 dia_semana, es_festivo, estado_dia)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                entrada_id=VALUES(entrada_id), salida_id=VALUES(salida_id),
                hora_entrada=VALUES(hora_entrada), hora_salida=VALUES(hora_salida),
                minutos_brutos=VALUES(minutos_brutos), minutos_descanso=VALUES(minutos_descanso),
                minutos_netos=VALUES(minutos_netos), horas_netas=VALUES(horas_netas),
                dia_semana=VALUES(dia_semana), es_festivo=VALUES(es_festivo),
                estado_dia=VALUES(estado_dia)',
            [
                $usuarioId, $fecha,
                $entrada['id'], $salida['id'] ?? null,
                $entrada['hora'], $salida['hora'] ?? null,
                $minBrutos, $minDesc, $minNetos,
                round($minNetos / 60, 2),
                $dow, $esFestivo, $estado,
            ]
        );
    }
}
