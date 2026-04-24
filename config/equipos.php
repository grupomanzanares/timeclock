<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireRole('admin');

$pageTitle  = 'Equipos Autorizados';
$activeMenu = 'cfg_equipos';
$extraJs    = ['config.js'];

$cargos = DB::fetchAll('SELECT id, nombre FROM cargos WHERE activo=1 ORDER BY nombre');
$sedes  = DB::fetchAll('SELECT id, nombre FROM sedes  WHERE activo=1 ORDER BY nombre');
require_once ROOT_PATH . '/views/layout/header.php';
?>
<script>
  var CARGOS_CFG = <?= json_encode($cargos) ?>;
  var SEDES_CFG  = <?= json_encode($sedes) ?>;
</script>

<div class="space-y-4">
  <div class="p-4 bg-blue-50 border border-blue-200 rounded-xl text-sm text-blue-700 flex gap-3">
    <i class="fas fa-circle-info mt-0.5 flex-shrink-0"></i>
    <div>
      <p class="font-medium">¿Cómo funciona la restricción por equipo?</p>
      <p class="mt-1">Si un cargo tiene equipos registrados aquí, los empleados con ese cargo <strong>solo</strong>
      podrán marcar desde esos equipos (identificados por hostname de red).
      Si el cargo <strong>no tiene equipos</strong> registrados, puede marcar desde cualquier equipo.</p>
      <p class="mt-1">Los supervisores y administradores siempre pueden marcar desde cualquier lugar.</p>
    </div>
  </div>

  <div class="tc-card">
    <div class="tc-card-header">
      <h2 class="text-base font-semibold text-gray-800">Equipos autorizados por cargo</h2>
      <button onclick="Cfg.abrirEquipo()"
              class="px-3 py-1.5 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
        <i class="fas fa-plus"></i> Agregar equipo
      </button>
    </div>
    <div class="tc-card-body overflow-x-auto">
      <table class="tc-table w-full">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left py-3 pr-4">Cargo</th>
            <th class="text-left py-3 pr-4">Sede</th>
            <th class="text-left py-3 pr-4">Nombre equipo (hostname)</th>
            <th class="text-left py-3 pr-4">Descripción</th>
            <th class="text-left py-3">Acciones</th>
          </tr>
        </thead>
        <tbody id="tbody-equipos">
          <tr><td colspan="5" class="py-8 text-center"><span class="tc-spinner"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Hostname actual -->
  <div class="tc-card p-4 flex items-center gap-3 text-sm">
    <i class="fas fa-laptop text-indigo-400 text-lg"></i>
    <div>
      <p class="font-medium text-gray-700">Equipo actual</p>
      <p class="text-gray-500">Hostname detectado: <code id="hostname-actual" class="text-indigo-700 font-mono">cargando...</code></p>
    </div>
    <button onclick="navigator.clipboard.writeText(document.getElementById('hostname-actual').textContent).then(()=>TC.toast('Copiado','success',2000))"
            class="ml-auto px-3 py-1.5 text-xs border border-gray-300 rounded-lg hover:bg-gray-50">
      <i class="fas fa-copy mr-1"></i> Copiar
    </button>
  </div>
</div>

<script>
// Mostrar el hostname del equipo actual via API
fetch(APP.baseUrl + '/api/config.php?action=hostname_actual', {
  headers: {'X-Requested-With':'XMLHttpRequest'}
}).then(r => r.json()).then(d => {
  if (d.data?.hostname) {
    document.getElementById('hostname-actual').textContent = d.data.hostname;
  }
}).catch(() => {});
</script>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
