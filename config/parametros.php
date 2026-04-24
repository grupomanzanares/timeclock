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
<script>const CARGOS_CFG = <?= json_encode($cargos) ?>;</script>

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
    <h3 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2">
      <i class="fas fa-circle-info text-indigo-400"></i> Claves disponibles
    </h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
      <?php
      $claves = [
        'tolerancia_entrada_antes'   => 'Min. antes del turno para marcar entrada',
        'tolerancia_entrada_despues' => 'Min. después del inicio para considerar puntual',
        'tolerancia_salida_antes'    => 'Min. antes de fin de turno para marcar salida',
        'tolerancia_salida_despues'  => 'Min. después del fin para salida normal',
        'minutos_descanso_global'    => 'Min. de descanso a descontar por día',
        'cerrar_turno_auto'          => '1 = activar cierre automático de turno',
        'requiere_aprobacion'        => '1 = marcaciones requieren aprobación',
      ];
      foreach ($claves as $k => $desc): ?>
      <div class="flex gap-2 p-2 bg-gray-50 rounded-lg">
        <code class="text-xs text-indigo-700 font-mono flex-shrink-0"><?= $k ?></code>
        <span class="text-xs text-gray-500">— <?= $desc ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
