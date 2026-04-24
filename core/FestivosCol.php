<?php
declare(strict_types=1);

/**
 * Festivos de Colombia calculados algorítmicamente.
 * Ley 51 de 1983 y Ley 43 de 1987 — no requiere parametrización manual.
 *
 * Tipos:
 *  - fijo:        misma fecha cada año
 *  - trasladable: si no cae en lunes, se corre al lunes siguiente (Ley 51/83)
 *  - semana_santa: relativo a Pascua, no se traslada
 *  - pascua_lunes: relativo a Pascua, se traslada al lunes siguiente
 */
class FestivosCol
{
    /** Devuelve todos los festivos del año como array [fecha, nombre, tipo] ordenado por fecha */
    public static function generarAnio(int $anio): array
    {
        $festivos = [];

        // ── Fijos ─────────────────────────────────────────────
        foreach ([
            ['01-01', 'Año Nuevo',                   'fijo'],
            ['05-01', 'Día del Trabajo',              'fijo'],
            ['07-20', 'Independencia de Colombia',   'fijo'],
            ['08-07', 'Batalla de Boyacá',            'fijo'],
            ['12-08', 'Inmaculada Concepción',        'fijo'],
            ['12-25', 'Navidad',                      'fijo'],
        ] as [$dia, $nombre, $tipo]) {
            $festivos[] = ['fecha' => "$anio-$dia", 'nombre' => $nombre, 'tipo' => $tipo];
        }

        // ── Trasladables (se mueven al lunes siguiente si no caen en lunes) ──
        foreach ([
            ['01-06', 'Reyes Magos'],
            ['03-19', 'San José'],
            ['06-29', 'San Pedro y San Pablo'],
            ['08-15', 'Asunción de la Virgen'],
            ['10-12', 'Día de la Raza'],
            ['11-01', 'Todos los Santos'],
            ['11-11', 'Independencia de Cartagena'],
        ] as [$dia, $nombre]) {
            $dt = new DateTime("$anio-$dia");
            $festivos[] = [
                'fecha'  => self::moverALunes($dt)->format('Y-m-d'),
                'nombre' => $nombre,
                'tipo'   => 'trasladable',
            ];
        }

        // ── Semana Santa y festivos relativos a Pascua ────────
        $pascua = self::calcularPascua($anio);

        $festivos[] = [
            'fecha'  => (clone $pascua)->modify('-3 days')->format('Y-m-d'),
            'nombre' => 'Jueves Santo',
            'tipo'   => 'semana_santa',
        ];
        $festivos[] = [
            'fecha'  => (clone $pascua)->modify('-2 days')->format('Y-m-d'),
            'nombre' => 'Viernes Santo',
            'tipo'   => 'semana_santa',
        ];
        $festivos[] = [
            'fecha'  => self::moverALunes((clone $pascua)->modify('+39 days'))->format('Y-m-d'),
            'nombre' => 'Ascensión del Señor',
            'tipo'   => 'trasladable',
        ];
        $festivos[] = [
            'fecha'  => self::moverALunes((clone $pascua)->modify('+60 days'))->format('Y-m-d'),
            'nombre' => 'Corpus Christi',
            'tipo'   => 'trasladable',
        ];
        $festivos[] = [
            'fecha'  => self::moverALunes((clone $pascua)->modify('+68 days'))->format('Y-m-d'),
            'nombre' => 'Sagrado Corazón de Jesús',
            'tipo'   => 'trasladable',
        ];

        usort($festivos, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
        return $festivos;
    }

    /** Devuelve true si la fecha es festivo en Colombia */
    public static function esFestivo(string $fecha): bool
    {
        $anio     = (int) substr($fecha, 0, 4);
        $festivos = self::generarAnio($anio);
        foreach ($festivos as $f) {
            if ($f['fecha'] === $fecha) {
                return true;
            }
        }
        return false;
    }

    // ── Privados ────────────────────────────────────────────

    /** Algoritmo de Butcher para Domingo de Pascua */
    private static function calcularPascua(int $anio): DateTime
    {
        $a = $anio % 19;
        $b = (int) ($anio / 100);
        $c = $anio % 100;
        $d = (int) ($b / 4);
        $e = $b % 4;
        $f = (int) (($b + 8) / 25);
        $g = (int) (($b - $f + 1) / 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = (int) ($c / 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = (int) (($a + 11 * $h + 22 * $l) / 451);
        $mes = (int) (($h + $l - 7 * $m + 114) / 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;
        return new DateTime(sprintf('%04d-%02d-%02d', $anio, $mes, $dia));
    }

    /** Si el DateTime ya es lunes lo devuelve igual; si no, avanza al lunes siguiente */
    private static function moverALunes(DateTime $dt): DateTime
    {
        $dow = (int) $dt->format('w'); // 0=Dom … 6=Sáb
        if ($dow === 1) {
            return $dt;
        }
        $dias = $dow === 0 ? 1 : (8 - $dow);
        return $dt->modify("+{$dias} days");
    }
}
