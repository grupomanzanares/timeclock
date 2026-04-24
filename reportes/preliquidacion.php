<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireLogin();

$pageTitle  = 'Preliquidación de Nómina';
$activeMenu = 'rep_preliq';
$extraJs    = ['preliquidacion.js'];

$esAdmin = Auth::hasRole('admin', 'supervisor');

if ($esAdmin) {
    $sedeIdAuth = Auth::user()['sede_id'];
    $empleados  = DB::fetchAll(
        'SELECT u.id, u.nombre, u.apellido, u.cargo_id, u.sede_id,
                c.nombre AS cargo_nombre, s.nombre AS sede_nombre
         FROM usuarios u
         LEFT JOIN cargos c ON c.id = u.cargo_id
         LEFT JOIN sedes  s ON s.id = u.sede_id
         WHERE u.activo = 1 AND u.rol = \'empleado\'
           AND (? IS NULL OR u.sede_id = ?)
         ORDER BY u.nombre, u.apellido',
        [$sedeIdAuth, $sedeIdAuth]
    );
    $sedes  = DB::fetchAll('SELECT id, nombre FROM sedes  WHERE activo = 1 ORDER BY nombre');
    $cargos = DB::fetchAll('SELECT id, nombre FROM cargos WHERE activo = 1 ORDER BY nombre');
}

require_once ROOT_PATH . '/views/layout/header.php';
?>

<div class="space-y-4">

  <!-- Barra superior: fecha + calcular -->
  <div class="tc-card">
    <div class="tc-card-body">
      <div class="flex flex-wrap items-end gap-3">
        <div>
          <label class="tc-label">Desde</label>
          <input type="date" id="p-desde" class="tc-input text-sm" value="<?= date('Y-m-01') ?>">
        </div>
        <div>
          <label class="tc-label">Hasta</label>
          <input type="date" id="p-hasta" class="tc-input text-sm" value="<?= date('Y-m-d') ?>">
        </div>
        <button onclick="Preliq.calcular()"
                class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 flex items-center gap-2">
          <i class="fas fa-calculator"></i> Calcular
        </button>
        <button onclick="Preliq.exportarCsv()" title="Exportar CSV"
                class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
          <i class="fas fa-file-excel"></i>
        </button>
        <?php if ($esAdmin): ?>
        <p class="text-xs text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg px-3 py-2 ml-auto" id="lbl-seleccionados">
          Calculará para todos los empleados
        </p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <?php if ($esAdmin): ?>
  <!-- Selector de empleados (admin/supervisor) -->
  <div class="tc-card">
    <div class="tc-card-header">
      <h2 class="text-sm font-semibold text-gray-800">
        Empleados
        <span id="cnt-selec" class="ml-1 text-indigo-600"></span>
      </h2>
      <div class="flex items-center gap-3">
        <label class="flex items-center gap-1.5 text-sm text-gray-600 cursor-pointer select-none">
          <input type="checkbox" id="chk-todos-emp" class="w-4 h-4 rounded text-indigo-600"
                 onchange="Preliq.toggleTodos(this.checked)">
          Selec. todos visibles
        </label>
        <button onclick="Preliq.limpiarSeleccion()" class="text-xs text-gray-400 hover:text-gray-600">
          Limpiar
        </button>
      </div>
    </div>
    <div class="tc-card-body pt-0">
      <!-- Filtros de la lista -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
        <select id="f-sede" class="tc-input text-sm" onchange="Preliq.filtrarLista()">
          <option value="">Todas las sedes</option>
          <?php foreach ($sedes as $s): ?>
            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <select id="f-cargo" class="tc-input text-sm" onchange="Preliq.filtrarLista()">
          <option value="">Todos los cargos</option>
          <?php foreach ($cargos as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" id="f-buscar" placeholder="Buscar empleado..." class="tc-input text-sm"
               oninput="Preliq.filtrarLista()">
      </div>
      <!-- Lista de checkboxes -->
      <div id="lista-empleados"
           class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-0.5 max-h-72 overflow-y-auto border border-gray-100 rounded-xl p-1">
        <?php foreach ($empleados as $e): ?>
          <label class="emp-item flex items-start gap-2 px-3 py-2 rounded-lg hover:bg-gray-50 cursor-pointer"
                 data-cargo-id="<?= (int)$e['cargo_id'] ?>"
                 data-sede-id="<?= (int)$e['sede_id'] ?>"
                 data-nombre="<?= strtolower($e['nombre'] . ' ' . $e['apellido']) ?>">
            <input type="checkbox" class="chk-emp mt-0.5 w-4 h-4 rounded border-gray-300 text-indigo-600 cursor-pointer"
                   value="<?= $e['id'] ?>" onchange="Preliq.onCheckEmp()">
            <span>
              <span class="block text-sm font-medium text-gray-900">
                <?= htmlspecialchars($e['nombre'] . ' ' . $e['apellido']) ?>
              </span>
              <span class="block text-xs text-gray-400">
                <?= htmlspecialchars($e['cargo_nombre'] ?? '—') ?>
              </span>
            </span>
          </label>
        <?php endforeach; ?>
      </div>
      <p class="text-xs text-gray-400 mt-2" id="cnt-visibles"></p>
    </div>
  </div>
  <?php endif; ?>

  <!-- Resultados -->
  <div id="resultados-preliq" class="hidden space-y-4"></div>

</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
