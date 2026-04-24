<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireRole('admin', 'supervisor');

$pageTitle  = 'Gestión de Turnos';
$activeMenu = 'turnos';
$extraJs    = ['turnos.js'];
require_once ROOT_PATH . '/views/layout/header.php';
?>

<div class="space-y-6">

  <!-- Turnos disponibles -->
  <?php if (Auth::hasRole('admin')): ?>
  <div class="tc-card">
    <div class="tc-card-header">
      <h2 class="text-base font-semibold text-gray-800">Turnos</h2>
      <button onclick="Turnos.abrirCrear()"
              class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
        <i class="fas fa-plus"></i> Nuevo turno
      </button>
    </div>
    <div class="tc-card-body overflow-x-auto">
      <table class="tc-table w-full">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left py-3 pr-4">Nombre</th>
            <th class="text-left py-3 pr-4">Entrada</th>
            <th class="text-left py-3 pr-4">Salida</th>
            <th class="text-left py-3 pr-4">Tipo</th>
            <th class="text-left py-3">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody-turnos">
          <tr><td colspan="5" class="py-6 text-center"><span class="tc-spinner"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <!-- Asignaciones -->
  <div class="tc-card">
    <div class="tc-card-header">
      <h2 class="text-base font-semibold text-gray-800">Asignación de turnos por empleado</h2>
      <button onclick="Turnos.abrirAsignar()"
              class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2">
        <i class="fas fa-calendar-plus"></i> Asignar turno
      </button>
    </div>
    <div class="tc-card-body">
      <div class="flex gap-3 mb-4 flex-wrap">
        <select id="filtro-empleado" class="tc-input max-w-xs">
          <option value="">Todos los empleados</option>
        </select>
      </div>
      <div class="overflow-x-auto">
        <table class="tc-table w-full">
          <thead>
            <tr class="border-b border-gray-100">
              <th class="text-left py-3 pr-4">Empleado</th>
              <th class="text-left py-3 pr-4">Turno</th>
              <th class="text-left py-3 pr-4">Desde</th>
              <th class="text-left py-3 pr-4">Hasta</th>
              <th class="text-left py-3 pr-4">Días</th>
              <th class="text-left py-3 pr-4">Estado</th>
              <th class="text-left py-3">Acciones</th>
            </tr>
          </thead>
          <tbody id="tbody-asignaciones">
            <tr><td colspan="7" class="py-6 text-center"><span class="tc-spinner"></span></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
