<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireRole('admin', 'supervisor');

$pageTitle  = 'Aprobar Marcaciones';
$activeMenu = 'pendientes';
$extraJs    = ['pendientes.js'];
require_once ROOT_PATH . '/views/layout/header.php';
?>

<div class="tc-card">
  <div class="tc-card-header">
    <h2 class="text-base font-semibold text-gray-800">Marcaciones pendientes de aprobación</h2>
    <button onclick="cargarPendientes()" class="text-sm text-indigo-600 hover:underline flex items-center gap-1">
      <i class="fas fa-rotate-right"></i> Actualizar
    </button>
  </div>
  <div class="tc-card-body overflow-x-auto">
    <table class="tc-table w-full" id="tabla-pendientes">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="text-left py-3 pr-4">Empleado</th>
          <th class="text-left py-3 pr-4">Cargo</th>
          <th class="text-left py-3 pr-4">Sede</th>
          <th class="text-left py-3 pr-4">Fecha / Hora</th>
          <th class="text-left py-3 pr-4">Tipo</th>
          <th class="text-left py-3 pr-4">Estado</th>
          <th class="text-left py-3 pr-4">Diferencia</th>
          <th class="text-left py-3">Acciones</th>
        </tr>
      </thead>
      <tbody id="tbody-pendientes">
        <tr><td colspan="8" class="py-8 text-center text-gray-400">
          <span class="tc-spinner"></span>
        </td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
