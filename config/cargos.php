<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireRole('admin');

$pageTitle  = 'Cargos';
$activeMenu = 'cfg_cargos';
$extraJs    = ['config.js'];
require_once ROOT_PATH . '/views/layout/header.php';
?>

<div class="tc-card">
  <div class="tc-card-header">
    <h2 class="text-base font-semibold text-gray-800">Cargos</h2>
    <button onclick="Cfg.abrirCargo()"
            class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
      <i class="fas fa-plus"></i> Nuevo cargo
    </button>
  </div>
  <div class="tc-card-body overflow-x-auto">
    <table class="tc-table w-full">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left py-3 pr-4">Nombre</th>
          <th class="text-left py-3 pr-4">Descripción</th>
          <th class="text-left py-3">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody-cargos">
        <tr><td colspan="3" class="py-8 text-center"><span class="tc-spinner"></span></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
