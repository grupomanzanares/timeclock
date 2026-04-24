<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireLogin();

$pageTitle  = 'Preliquidación de Nómina';
$activeMenu = 'rep_preliq';
$extraJs    = ['preliquidacion.js'];
require_once ROOT_PATH . '/views/layout/header.php';
?>

<div class="space-y-5">

  <!-- Filtros -->
  <div class="tc-card p-5">
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <?php if (Auth::hasRole('admin','supervisor')): ?>
      <div>
        <label class="tc-label">Empleado</label>
        <select id="p-usuario" class="tc-input">
          <option value="<?= Auth::id() ?>">— Mi reporte —</option>
        </select>
      </div>
      <?php endif; ?>
      <div>
        <label class="tc-label">Semana (cualquier día de la semana)</label>
        <input id="p-fecha" type="date" class="tc-input" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="flex items-end gap-2">
        <button onclick="Preliq.cargar()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
          <i class="fas fa-calculator mr-1"></i> Calcular
        </button>
        <button onclick="Preliq.exportar()" title="Exportar Excel"
                class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
          <i class="fas fa-file-excel"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Resumen semana -->
  <div id="resumen-preliq" class="hidden"></div>

  <!-- Tabla detalle -->
  <div class="tc-card" id="card-detalle-preliq" style="display:none">
    <div class="tc-card-header">
      <h2 class="text-base font-semibold text-gray-800" id="titulo-preliq">Detalle semana</h2>
    </div>
    <div class="tc-card-body overflow-x-auto">
      <table class="tc-table w-full">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left py-3 pr-3">Fecha</th>
            <th class="text-left py-3 pr-3">Día</th>
            <th class="text-left py-3 pr-3">Tipo</th>
            <th class="text-left py-3 pr-3">Festivo</th>
            <th class="text-left py-3 pr-3">Entrada</th>
            <th class="text-left py-3 pr-3">Salida</th>
            <th class="text-right py-3">Horas</th>
          </tr>
        </thead>
        <tbody id="tbody-preliq"></tbody>
        <tfoot id="tfoot-preliq" class="bg-gray-50 font-semibold"></tfoot>
      </table>
    </div>
  </div>

</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
