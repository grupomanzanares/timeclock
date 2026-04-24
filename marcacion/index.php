<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireLogin();

$pageTitle  = 'Marcar Asistencia';
$activeMenu = 'marcacion';
$extraJs    = ['marcacion.js'];
require_once ROOT_PATH . '/views/layout/header.php';
?>

<div class="max-w-lg mx-auto space-y-6">

  <!-- Reloj -->
  <div class="tc-card p-8 text-center">
    <p class="text-sm text-gray-400 mb-1"><?= date('l, d \d\e F \d\e Y') ?></p>
    <div id="reloj-marcacion" class="reloj-display text-6xl font-bold text-gray-900 tracking-tight">--:--:--</div>
  </div>

  <!-- Panel turno -->
  <div id="panel-turno" class="tc-card p-5">
    <div class="flex items-center justify-center py-4">
      <span class="tc-spinner"></span>
    </div>
  </div>

  <!-- Estado marcación -->
  <div id="panel-estado" class="tc-card p-5 hidden"></div>

  <!-- Formulario marcar -->
  <div id="panel-marcar" class="tc-card p-5 hidden">
    <h3 class="text-base font-semibold text-gray-800 mb-4" id="titulo-marcar">Marcar</h3>
    <div class="space-y-3">
      <div>
        <label class="tc-label">Observación (opcional)</label>
        <textarea id="observacion-marc" rows="3" placeholder="Puede añadir una nota..."
                  class="tc-input resize-none"></textarea>
      </div>
      <button id="btn-marcar"
              class="btn-marcar w-full py-3 rounded-xl text-white font-semibold text-base flex items-center justify-center gap-2">
        <i id="btn-icon" class="fas fa-sign-in-alt"></i>
        <span id="btn-label">Marcar Entrada</span>
      </button>
    </div>
  </div>

</div>

<?php require_once ROOT_PATH . '/views/layout/footer.php'; ?>
