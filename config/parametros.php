<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireRole('admin');

$pageTitle  = 'Parámetros del Sistema';
$activeMenu = 'cfg_params';
$extraJs    = ['config.js'];

$cargos = DB::fetchAll('SELECT id, nombre FROM cargos WHERE activo=1 ORDER BY nombre');
require_once ROOT_PATH . '/views/layout/header.php';
?>
<script>var CARGOS_CFG = <?= json_encode($cargos) ?>;</script>

<div class="space-y-6">

  <div class="tc-card">
    <div class="tc-card-header">
      <div>
        <h2 class="text-base font-semibold text-gray-800">Parámetros de tolerancia y descanso</h2>
        <p class="text-xs text-gray-400 mt-0.5">Configure tiempos por defecto o específicos por cargo</p>
      </div>
      <button onclick="Cfg.abrirParam()"
              class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
        <i class="fas fa-plus"></i> Nuevo parámetro
      </button>
    </div>
    <div class="tc-card-body overflow-x-auto">
      <table class="tc-table w-full">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left py-3 pr-4">Clave</th>
            <th class="text-left py-3 pr-4">Valor</th>
            <th class="text-left py-3 pr-4">Aplica a</th>
            <th class="text-left py-3 pr-4">Descripción</th>
            <th class="text-left py-3">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody-params">
          <tr><td colspan="5" class="py-8 text-center"><span class="tc-spinner"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Referencia de claves -->
  <div class="tc-card p-5">
    <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
      <i class="fas fa-circle-info text-indigo-400"></i> Claves disponibles y cómo funcionan
    </h3>
    <div class="space-y-3">
      <?php
      $claves = [
        'tolerancia_entrada_antes' => [
          'defecto' => '10',
          'tipo'    => 'Número (minutos)',
          'desc'    => 'Minutos antes del inicio del turno en que el sistema acepta una marcación de entrada. Si el turno empieza a las 8:00 y este valor es 10, el empleado puede marcar desde las 7:50. Si llega antes de esa ventana, la marcación se rechaza como "fuera de turno".',
        ],
        'tolerancia_entrada_despues' => [
          'defecto' => '15',
          'tipo'    => 'Número (minutos)',
          'desc'    => 'Minutos después del inicio del turno que todavía se consideran "puntual". Si el turno empieza a las 8:00 y este valor es 15, llegar a las 8:14 sigue siendo puntual. Llegar a las 8:16 genera estado "llegada tarde" y queda pendiente de aprobación.',
        ],
        'tolerancia_salida_antes' => [
          'defecto' => '10',
          'tipo'    => 'Número (minutos)',
          'desc'    => 'Minutos antes del fin del turno en que se acepta la marcación de salida sin generar alerta. Si el turno termina a las 17:00 y este valor es 10, el empleado puede salir desde las 16:50. Salir antes generará estado "salida temprana".',
        ],
        'tolerancia_salida_despues' => [
          'defecto' => '30',
          'tipo'    => 'Número (minutos)',
          'desc'    => 'Minutos después del fin del turno que aún se consideran salida normal. Si el turno termina a las 17:00 y este valor es 30, salir a las 17:29 es normal. Salir después generará estado "salida tarde" (puede indicar horas extra no autorizadas).',
        ],
        'minutos_descanso_global' => [
          'defecto' => '0',
          'tipo'    => 'Número (minutos)',
          'desc'    => 'Minutos de descanso (almuerzo/break) a descontar del tiempo trabajado cuando el turno asignado no tiene descanso configurado. Por ejemplo: 60 → descuenta 1 hora al calcular las horas netas del día. Si el turno ya tiene descanso definido, este parámetro no aplica.',
        ],
        'cerrar_turno_auto' => [
          'defecto' => '1',
          'tipo'    => '1 ó 0',
          'desc'    => 'Controla si el sistema cierra automáticamente los turnos de días anteriores que quedaron sin marcación de salida. Con valor 1, al iniciar sesión el empleado, el sistema registra una salida automática a la hora de fin de cada turno pendiente. Con valor 0, los turnos incompletos quedan sin cerrar hasta que un supervisor los gestione.',
        ],
        'requiere_aprobacion' => [
          'defecto' => '1',
          'tipo'    => '1 ó 0',
          'desc'    => 'Define si las marcaciones fuera de tolerancia (llegadas tarde, salidas tempranas, salidas tarde, fuera de turno) requieren revisión de un supervisor. Con valor 1, esas marcaciones quedan en estado "pendiente" hasta ser aprobadas o rechazadas. Con valor 0, todas las marcaciones se aprueban automáticamente sin revisión. Las marcaciones puntuales siempre se auto-aprueban.',
        ],
      ];
      foreach ($claves as $k => $info): ?>
      <div class="border border-gray-100 rounded-xl p-3 bg-gray-50">
        <div class="flex flex-wrap items-center gap-3 mb-1">
          <code class="text-xs text-indigo-700 font-mono font-bold"><?= $k ?></code>
          <span class="text-xs text-gray-400 bg-white border border-gray-200 rounded px-1.5 py-0.5">
            Tipo: <?= $info['tipo'] ?>
          </span>
          <span class="text-xs text-emerald-700 bg-emerald-50 border border-emerald-200 rounded px-1.5 py-0.5">
            Default: <?= $info['defecto'] ?>
          </span>
        </div>
        <p class="text-xs text-gray-500 leading-relaxed"><?= $info['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
