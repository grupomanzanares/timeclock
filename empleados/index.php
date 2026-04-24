<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireRole('admin', 'supervisor');

$pageTitle  = 'Empleados';
$activeMenu = 'empleados';
$extraJs    = ['empleados.js'];

// Necesitamos listas para el formulario
$cargos = DB::fetchAll('SELECT id, nombre FROM cargos WHERE activo=1 ORDER BY nombre');
$sedes  = DB::fetchAll('SELECT id, nombre FROM sedes WHERE activo=1 ORDER BY nombre');

require_once ROOT_PATH . '/views/layout/header.php';
?>
<script>
  var CARGOS = <?= json_encode($cargos) ?>;
  var SEDES  = <?= json_encode($sedes) ?>;
</script>

<div class="tc-card">
  <div class="tc-card-header">
    <h2 class="text-base font-semibold text-gray-800">Empleados</h2>
    <?php if (Auth::hasRole('admin')): ?>
    <button onclick="Empleados.abrirCrear()"
            class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
      <i class="fas fa-user-plus"></i> Nuevo empleado
    </button>
    <?php endif; ?>
  </div>
  <div class="tc-card-body">
    <div class="mb-4">
      <input id="buscar-emp" type="text" placeholder="Buscar por nombre, cédula o email..."
             class="tc-input max-w-sm" oninput="Empleados.filtrar(this.value)">
    </div>
    <div class="overflow-x-auto">
      <table class="tc-table w-full">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left py-3 pr-4">Nombre</th>
            <th class="text-left py-3 pr-4">Cédula</th>
            <th class="text-left py-3 pr-4">Cargo</th>
            <th class="text-left py-3 pr-4">Sede</th>
            <th class="text-left py-3 pr-4">Rol</th>
            <th class="text-left py-3 pr-4">Equipo</th>
            <th class="text-left py-3">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody-empleados">
          <tr><td colspan="7" class="py-8 text-center"><span class="tc-spinner"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
