<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireRole('admin');

$pageTitle  = 'Días Festivos';
$activeMenu = 'cfg_festivos';
$extraJs    = ['config.js'];
require_once ROOT_PATH . '/views/layout/header.php';
?>

<div class="tc-card">
  <div class="tc-card-header">
    <div>
      <h2 class="text-base font-semibold text-gray-800">Festivos de Colombia</h2>
      <p class="text-xs text-gray-400 mt-0.5">Los festivos se usan en reportes y preliquidación</p>
    </div>
    <div class="flex items-center gap-3">
      <select id="filtro-anio" class="tc-input text-sm py-1.5"
              onchange="Cfg.cargarFestivos(this.value)">
        <?php for ($y = date('Y')-1; $y <= date('Y')+2; $y++): ?>
          <option value="<?= $y ?>" <?= $y == date('Y') ? 'selected' : '' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
      <button onclick="Cfg.abrirFestivo()"
              class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
        <i class="fas fa-plus"></i> Agregar
      </button>
    </div>
  </div>
  <div class="tc-card-body overflow-x-auto">
    <table class="tc-table w-full">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left py-3 pr-4">Fecha</th>
          <th class="text-left py-3 pr-4">Día</th>
          <th class="text-left py-3 pr-4">Nombre</th>
          <th class="text-left py-3 pr-4">Tipo</th>
          <th class="text-left py-3">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody-festivos">
        <tr><td colspan="5" class="py-8 text-center"><span class="tc-spinner"></span></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
