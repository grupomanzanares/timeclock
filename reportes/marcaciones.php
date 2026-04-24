<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireLogin();

$pageTitle  = 'Reporte de Marcaciones';
$activeMenu = 'rep_marcaciones';
$extraJs    = ['reportes.js'];
require_once ROOT_PATH . '/views/layout/header.php';
?>

<div class="space-y-5">

  <!-- Filtros -->
  <div class="tc-card p-5">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <?php if (Auth::hasRole('admin','supervisor')): ?>
      <div>
        <label class="tc-label">Empleado</label>
        <select id="f-usuario" class="tc-input">
          <option value="<?= Auth::id() ?>">— Mi reporte —</option>
        </select>
      </div>
      <?php endif; ?>
      <div>
        <label class="tc-label">Desde</label>
        <input id="f-desde" type="date" class="tc-input" value="<?= date('Y-m-01') ?>">
      </div>
      <div>
        <label class="tc-label">Hasta</label>
        <input id="f-hasta" type="date" class="tc-input" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="flex items-end gap-2">
        <button onclick="Reportes.cargar()" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700">
          <i class="fas fa-search mr-1"></i> Consultar
        </button>
        <button onclick="Reportes.exportar()" title="Exportar Excel"
                class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
          <i class="fas fa-file-excel"></i>
        </button>
      </div>
    </div>
  </div>

  <!-- Resumen rápido -->
  <div id="resumen-cards" class="hidden grid grid-cols-2 sm:grid-cols-4 gap-4">
    <div class="tc-card p-4 text-center">
      <p class="text-xs text-gray-400">Días registrados</p>
      <p id="r-dias" class="text-2xl font-bold text-gray-900 mt-1">—</p>
    </div>
    <div class="tc-card p-4 text-center">
      <p class="text-xs text-gray-400">Total horas</p>
      <p id="r-horas" class="text-2xl font-bold text-indigo-700 mt-1">—</p>
    </div>
    <div class="tc-card p-4 text-center">
      <p class="text-xs text-gray-400">Tardanzas</p>
      <p id="r-tardanzas" class="text-2xl font-bold text-amber-600 mt-1">—</p>
    </div>
    <div class="tc-card p-4 text-center">
      <p class="text-xs text-gray-400">Festivos laborados</p>
      <p id="r-festivos" class="text-2xl font-bold text-purple-600 mt-1">—</p>
    </div>
  </div>

  <!-- Tabla -->
  <div class="tc-card">
    <div class="tc-card-header">
      <h2 class="text-base font-semibold text-gray-800" id="titulo-reporte">Resultados</h2>
    </div>
    <div class="tc-card-body overflow-x-auto">
      <table class="tc-table w-full">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left py-3 pr-3">Fecha</th>
            <th class="text-left py-3 pr-3">Día</th>
            <th class="text-left py-3 pr-3">Festivo</th>
            <th class="text-left py-3 pr-3">Entrada</th>
            <th class="text-left py-3 pr-3">Salida</th>
            <th class="text-left py-3 pr-3">Horas</th>
            <th class="text-left py-3 pr-3">Estado entrada</th>
            <th class="text-left py-3">Observaciones</th>
          </tr>
        </thead>
        <tbody id="tbody-reporte">
          <tr><td colspan="8" class="py-8 text-center text-sm text-gray-400">
            Use los filtros para consultar el reporte
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
