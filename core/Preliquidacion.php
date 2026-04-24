<?php
declare(strict_types=1);

class Preliquidacion
{
    /**
     * Calcula la preliquidación semanal de un empleado.
     * Semana: domingo a sábado.
     *
     * Regla: (días_laborados_lunes_a_sábado * 7.33) = horas_que_debió_trabajar
     * Domingos y festivos se tratan como concepto aparte.
     */
    public static function calcularSemana(int $usuarioId, string $fechaDomingo): array
    {
        $domingo = new \DateTime($fechaDomingo);
        $sabado  = (clone $domingo)->modify('+6 days');

        $fechaIni = $domingo->format('Y-m-d');
        $fechaFin = $sabado->format('Y-m-d');

        $dias = DB::fetchAll(
            'SELECT rd.*, f.nombre AS nombre_festivo
             FROM resumen_diario rd
             LEFT JOIN festivos f ON f.fecha = rd.fecha
             WHERE rd.usuario_id = ?
               AND rd.fecha BETWEEN ? AND ?
             ORDER BY rd.fecha ASC',
            [$usuarioId, $fechaIni, $fechaFin]
        );

        $diasNormales  = 0; // lunes-sábado, no festivo
        $diasFestivos  = 0;
        $diasDomingo   = 0;
        $horasTotales  = 0.0;
        $horasFestivos = 0.0;
        $horasDomingos = 0.0;
        $detalle       = [];

        foreach ($dias as $d) {
            $dow       = (int)$d['dia_semana'];
            $festivo   = (bool)$d['es_festivo'];
            $horas     = (float)$d['horas_netas'];
            $nombreDia = DIAS_SEMANA[$dow];

            $tipo = 'normal';
            if ($dow === 0 || $festivo) {
                $tipo = $dow === 0 ? 'domingo' : 'festivo';
                if ($dow === 0) {
                    $diasDomingos++;
                    $horasDomingos += $horas;
                } else {
                    $diasFestivos++;
                    $horasFestivos += $horas;
                }
            } else {
                $diasNormales++;
                $horasTotales += $horas;
            }

            $detalle[] = [
                'fecha'         => $d['fecha'],
                'dia'           => $nombreDia,
                'es_festivo'    => $festivo,
                'nombre_festivo'=> $d['nombre_festivo'] ?? null,
                'tipo'          => $tipo,
                'hora_entrada'  => $d['hora_entrada'],
                'hora_salida'   => $d['hora_salida'],
                'horas_netas'   => $horas,
                'estado_dia'    => $d['estado_dia'],
            ];
        }

        // Horas que debió trabajar (solo días normales lunes-sábado)
        $horasDebidas = round($diasNormales * 7.33, 2);

        // Diferencia: positivo = horas extra; negativo = horas faltantes
        $diferencia = round($horasTotales - $horasDebidas, 2);

        return [
            'usuario_id'      => $usuarioId,
            'semana_inicio'   => $fechaIni,
            'semana_fin'      => $fechaFin,
            'dias_normales'   => $diasNormales,
            'dias_festivos'   => $diasFestivos,
            'dias_domingos'   => $diasDomingo,
            'horas_normales'  => round($horasTotales, 2),
            'horas_festivos'  => round($horasFestivos, 2),
            'horas_domingos'  => round($horasDomingos, 2),
            'horas_debidas'   => $horasDebidas,
            'diferencia'      => $diferencia,
            'detalle'         => $detalle,
        ];
    }

    /**
     * Devuelve el primer domingo de la semana que contiene $fecha.
     */
    public static function domingoDeSemanaDe(string $fecha): string
    {
        $dt  = new \DateTime($fecha);
        $dow = (int)$dt->format('w');
        $dt->modify("-{$dow} days");
        return $dt->format('Y-m-d');
    }
}
