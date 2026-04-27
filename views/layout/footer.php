<?php defined('ROOT_PATH') or die('Acceso denegado'); ?>
    </main><!-- /main-content -->
</div><!-- /área principal -->
</div><!-- /wrapper -->

<!-- Toast container -->
<div id="toast-container"
     style="position:fixed;top:1.1rem;right:1.1rem;z-index:9999;
            display:flex;flex-direction:column;gap:.5rem;pointer-events:none;
            max-width:360px;width:100%;">
</div>

<!-- Modal genérico -->
<div id="modal-overlay"
     style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);
            z-index:999;align-items:center;justify-content:center;padding:1rem;
            backdrop-filter:blur(3px);">
    <div id="modal-box"
         style="background:#fff;border-radius:20px;width:100%;max-width:480px;
                max-height:90vh;overflow-y:auto;
                box-shadow:0 25px 60px rgba(0,0,0,.18),0 0 0 1px rgba(0,0,0,.06);
                animation:modal-in .28s cubic-bezier(.34,1.56,.64,1) both;">
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:1.1rem 1.4rem;border-bottom:1px solid #f1f4f9;">
            <h3 id="modal-title"
                style="font-size:.95rem;font-weight:900;color:#1e293b;"></h3>
            <button onclick="TC.closeModal()"
                    style="width:30px;height:30px;border-radius:8px;border:none;
                           background:#f1f5f9;color:#64748b;cursor:pointer;font-size:.9rem;
                           display:flex;align-items:center;justify-content:center;"
                    aria-label="Cerrar">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div id="modal-body" style="padding:1.25rem 1.4rem;"></div>
        <div id="modal-footer"
             style="padding:1rem 1.4rem;border-top:1px solid #f1f4f9;
                    display:flex;justify-content:flex-end;gap:.65rem;"></div>
    </div>
</div>

<style>
@keyframes modal-in {
    from { transform: scale(.93) translateY(12px); opacity: 0; }
    to   { transform: scale(1)   translateY(0);    opacity: 1; }
}

/* Mobile: mostrar hamburger, ocultar reloj */
@media (max-width: 1023px) {
    #btn-sidebar-toggle { display:inline-flex !important; }
    #btn-close-sidebar  { display:flex !important; }
}
@media (min-width: 640px) {
    #reloj-header { display:block !important; }
}
</style>

<script src="<?= BASE_URL ?>/js/app.js?v=<?= filemtime(ROOT_PATH.'/js/app.js') ?>"></script>
<?php if (!empty($extraJs)): ?>
  <?php foreach ((array)$extraJs as $js): ?>
    <script src="<?= BASE_URL ?>/js/<?= htmlspecialchars($js) ?>?v=<?= filemtime(ROOT_PATH.'/js/'.htmlspecialchars($js)) ?>"></script>
  <?php endforeach; ?>
<?php endif; ?>
</body>
</html>
