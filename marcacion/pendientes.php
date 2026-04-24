<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireRole('admin', 'supervisor');

$pageTitle  = 'Aprobar Marcaciones';
$activeMenu = 'pendientes';
$extraJs    = ['pendientes.js'];

$empleados = DB::fetchAll(
    'SELECT u.id, u.nombre, u.apellido
     FROM usuarios u
     WHERE u.activo = 1 AND u.rol = \'empleado\'
       AND (? IS NULL OR u.sede_id = ?)
     ORDER BY u.nombre, u.apellido',
    [Auth::user()['sede_id'], Auth::user()['sede_id']]
);
$cargos = DB::fetchAll('SELECT id, nombre FROM cargos WHERE activo = 1 ORDER BY nombre');

require_once ROOT_PATH . '/views/layout/header.php';
?>

<!-- Filtros -->
<div class="tc-card mb-4">
  <div class="tc-card-body">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
      <div>
        <label class="tc-label">Empleado</label>
        <select id="f-empleado" class="tc-input text-sm">
          <option value="">Todos</option>
          <?php foreach ($empleados as $e): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="tc-label">Cargo</label>
        <select id="f-cargo" class="tc-input text-sm">
          <option value="">Todos</option>
          <?php foreach ($cargos as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="tc-label">Desde</label>
        <input type="date" id="f-desde" class="tc-input text-sm">
      </div>
      <div>
        <label class="tc-label">Hasta</label>
        <input type="date" id="f-hasta" class="tc-input text-sm">
      </div>
      <div class="flex items-end gap-2">
        <button onclick="Pend.filtrar()"
                class="flex-1 px-3 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-medium">
          <i class="fas fa-filter mr-1"></i>Filtrar
        </button>
        <button onclick="Pend.limpiar()"
                class="px-3 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 text-gray-600">
          <i class="fas fa-xmark"></i>
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Barra de acciones masivas -->
<div id="barra-masiva" class="hidden tc-card mb-4 border-indigo-200 bg-indigo-50">
  <div class="tc-card-body py-3">
    <div class="flex flex-wrap items-center gap-3">
      <label class="flex items-center gap-2 cursor-pointer select-none">
        <input type="checkbox" id="chk-todos" onchange="Pend.toggleTodos(this.checked)"
               class="w-4 h-4 rounded border-gray-300 text-indigo-600 cursor-pointer">
        <span class="text-sm font-medium text-indigo-800">Seleccionar todos</span>
      </label>
      <span class="text-sm text-indigo-700" id="lbl-seleccionadas" style="display:none">
        — <strong id="cnt-seleccionadas">0</strong> seleccionada(s)
      </span>

      <div class="flex flex-wrap gap-2 ml-auto">
        <!-- Acciones sobre selección -->
        <div id="btns-seleccion" class="flex gap-2 hidden">
          <button onclick="Pend.aprobarSeleccion()"
                  class="px-3 py-1.5 text-xs font-semibold bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-1">
            <i class="fas fa-check"></i> Aprobar seleccionadas
          </button>
          <button onclick="Pend.rechazarSeleccion()"
                  class="px-3 py-1.5 text-xs font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-1">
            <i class="fas fa-xmark"></i> Rechazar seleccionadas
          </button>
        </div>
        <!-- Acciones sobre el filtro completo -->
        <button onclick="Pend.aprobarTodas()"
                class="px-3 py-1.5 text-xs font-semibold border-2 border-green-600 text-green-700 bg-white rounded-lg hover:bg-green-50 flex items-center gap-1">
          <i class="fas fa-check-double"></i>
          Aprobar todas del filtro (<span id="cnt-total">0</span>)
        </button>
        <button onclick="Pend.rechazarTodas()"
                class="px-3 py-1.5 text-xs font-semibold border-2 border-red-500 text-red-600 bg-white rounded-lg hover:bg-red-50 flex items-center gap-1">
          <i class="fas fa-ban"></i>
          Rechazar todas del filtro (<span id="cnt-total2">0</span>)
        </button>
        <button onclick="Pend.filtrar()"
                class="px-3 py-1.5 text-xs text-indigo-600 hover:underline flex items-center gap-1">
          <i class="fas fa-rotate-right"></i> Actualizar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Tabla -->
<div class="tc-card">
  <div class="tc-card-header">
    <h2 class="text-base font-semibold text-gray-800">
      Marcaciones pendientes de aprobación
      <span id="badge-total" class="ml-2 px-2 py-0.5 text-xs bg-amber-100 text-amber-700 rounded-full font-normal hidden"></span>
    </h2>
    <button onclick="Pend.filtrar()" class="text-sm text-indigo-600 hover:underline flex items-center gap-1">
      <i class="fas fa-rotate-right"></i> Actualizar
    </button>
  </div>
  <div class="tc-card-body overflow-x-auto">
    <table class="tc-table w-full">
      <thead>
        <tr class="border-b border-gray-100">
          <th class="py-3 pr-3 w-8"></th>
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
        <tr><td colspan="9" class="py-8 text-center text-gray-400">
          <span class="tc-spinner"></span>
        </td></tr>
      </tbody>
    </table>
  </div>
</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
