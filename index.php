<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';
Auth::requireLogin();

$pageTitle  = 'Dashboard';
$activeMenu = 'dashboard';
$extraJs    = ['dashboard.js'];
require_once ROOT_PATH . '/views/layout/header.php';
?>

<div class="space-y-6">

  <!-- Stat cards -->
  <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4" id="stat-cards">
    <div class="stat-card">
      <div class="stat-icon bg-indigo-100"><i class="fas fa-user-check text-indigo-600"></i></div>
      <div>
        <p class="text-sm text-gray-500">Marcaciones hoy</p>
        <p id="stat-hoy" class="text-2xl font-bold text-gray-900 mt-0.5">—</p>
      </div>
    </div>
    <?php if (Auth::hasRole('admin','supervisor')): ?>
    <div class="stat-card">
      <div class="stat-icon bg-amber-100"><i class="fas fa-clock text-amber-600"></i></div>
      <div>
        <p class="text-sm text-gray-500">Pendientes aprobación</p>
        <p id="stat-pendientes" class="text-2xl font-bold text-gray-900 mt-0.5">—</p>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon bg-red-100"><i class="fas fa-triangle-exclamation text-red-500"></i></div>
      <div>
        <p class="text-sm text-gray-500">Tardanzas hoy</p>
        <p id="stat-tardanzas" class="text-2xl font-bold text-gray-900 mt-0.5">—</p>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Tarjeta principal de marcación rápida -->
  <div class="tc-card p-6 max-w-sm">
    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
      <i class="fas fa-fingerprint text-indigo-500"></i> Marcación rápida
    </h2>
    <div id="marcacion-rapida">
      <div class="flex items-center justify-center py-6">
        <span class="tc-spinner"></span>
      </div>
    </div>
  </div>

  <!-- Últimas marcaciones del usuario -->
  <div class="tc-card">
    <div class="tc-card-header">
      <h2 class="text-base font-semibold text-gray-800">Últimas marcaciones</h2>
      <a href="<?= BASE_URL ?>/reportes/marcaciones.php"
         class="text-sm text-indigo-600 hover:underline">Ver todas</a>
    </div>
    <div class="tc-card-body overflow-x-auto">
      <table class="tc-table w-full">
        <thead>
          <tr class="border-b border-gray-100">
            <th class="text-left py-3 pr-4">Fecha</th>
            <th class="text-left py-3 pr-4">Día</th>
            <th class="text-left py-3 pr-4">Entrada</th>
            <th class="text-left py-3 pr-4">Salida</th>
            <th class="text-left py-3 pr-4">Horas</th>
            <th class="text-left py-3">Estado</th>
          </tr>
        </thead>
        <tbody id="tabla-ultimas"></tbody>
      </table>
    </div>
  </div>

</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
