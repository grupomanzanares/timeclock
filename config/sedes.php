<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireRole('admin');

$pageTitle  = 'Sedes';
$activeMenu = 'cfg_sedes';
$extraJs    = ['config.js'];

$supervisores = DB::fetchAll(
    "SELECT id, CONCAT(nombre,' ',apellido) AS nombre_completo
     FROM usuarios WHERE rol IN ('supervisor','admin') AND activo=1 ORDER BY nombre"
);
require_once ROOT_PATH . '/views/layout/header.php';
?>
<script>var SUPERVISORES = <?= json_encode($supervisores) ?>;</script>

<div class="tc-card">
  <div class="tc-card-header">
    <h2 class="text-base font-semibold text-gray-800">Sedes / Lugares de trabajo</h2>
    <button onclick="Cfg.abrirSede()"
            class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
      <i class="fas fa-plus"></i> Nueva sede
    </button>
  </div>
  <div class="tc-card-body overflow-x-auto">
    <table class="tc-table w-full">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left py-3 pr-4">Nombre</th>
          <th class="text-left py-3 pr-4">Dirección</th>
          <th class="text-left py-3 pr-4">Supervisor</th>
          <th class="text-left py-3">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody-sedes">
        <tr><td colspan="4" class="py-8 text-center"><span class="tc-spinner"></span></td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
