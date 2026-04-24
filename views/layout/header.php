<?php
defined('ROOT_PATH') or die('Acceso denegado');
$pageTitle  = $pageTitle  ?? APP_NAME;
$activeMenu = $activeMenu ?? '';
$user       = Auth::user();
$rol        = $user['rol'] ?? '';

$iniciales = '';
if (!empty($user['nombre'])) {
    $partes    = explode(' ', trim($user['nombre']));
    $iniciales = strtoupper(substr($partes[0], 0, 1));
    if (isset($partes[1])) $iniciales .= strtoupper(substr($partes[1], 0, 1));
}

$hora   = (int)date('H');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 18 ? 'Buenas tardes' : 'Buenas noches');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#1e3a5f">
<meta name="csrf-token" content="<?= Helpers::csrfToken() ?>">
<title><?= htmlspecialchars($pageTitle) ?> — <?= APP_NAME ?></title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/css/app.css">

<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: { sans: ['Nunito', 'system-ui', 'sans-serif'] }
        }
    }
};
const APP = {
    baseUrl:    '<?= BASE_URL ?>',
    rol:        '<?= $rol ?>',
    userId:     <?= $user['id'] ?? 0 ?>,
    csrfToken:  document.querySelector?.('meta[name="csrf-token"]')?.content ?? ''
};
</script>
</head>

<body class="bg-slate-50 antialiased" style="font-family:'Nunito',system-ui,sans-serif;">

<!-- Overlay mobile -->
<div id="sidebar-overlay" onclick="TC.closeSidebar()"></div>

<!-- ══ WRAPPER ═══════════════════════════════════════════ -->
<div style="display:flex; min-height:100vh;">

<!-- ══ SIDEBAR ═══════════════════════════════════════════ -->
<aside id="sidebar">

    <!-- Logo + cierre mobile -->
    <div style="padding:1.2rem 1rem 1rem; border-bottom:1px solid rgba(255,255,255,.08);
                display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
        <div style="display:flex; align-items:center; gap:.7rem;">
            <div style="width:40px;height:40px;background:rgba(255,255,255,.13);border-radius:13px;
                        border:1px solid rgba(255,255,255,.18);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas fa-clock" style="color:#fff;font-size:1rem;"></i>
            </div>
            <div>
                <p style="color:#fff;font-weight:900;font-size:.95rem;line-height:1.1;"><?= APP_NAME ?></p>
                <p style="color:rgba(255,255,255,.45);font-size:.68rem;font-weight:700;margin-top:.1rem;">Grupo Manzanares</p>
            </div>
        </div>
        <button onclick="TC.closeSidebar()" id="btn-close-sidebar"
                style="display:none;background:rgba(255,255,255,.1);border:none;border-radius:8px;
                       width:32px;height:32px;color:#fff;cursor:pointer;font-size:.9rem;"
                aria-label="Cerrar menú">
            <i class="fas fa-xmark"></i>
        </button>
    </div>

    <!-- Usuario -->
    <div style="padding:.9rem 1rem; border-bottom:1px solid rgba(255,255,255,.06); flex-shrink:0;">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <div style="width:38px;height:38px;border-radius:50%;background:rgba(187,209,48,.25);
                        border:2px solid rgba(187,209,48,.4);display:flex;align-items:center;
                        justify-content:center;font-weight:900;color:#bbd130;font-size:.85rem;flex-shrink:0;">
                <?= $iniciales ?>
            </div>
            <div style="overflow:hidden;">
                <p style="color:#fff;font-weight:800;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= htmlspecialchars($user['nombre'] ?? '') ?>
                </p>
                <p style="color:rgba(255,255,255,.42);font-size:.7rem;font-weight:700;margin-top:.1rem;text-transform:capitalize;">
                    <?= $rol ?> <?= $user['sede_nombre'] ? '· ' . htmlspecialchars($user['sede_nombre']) : '' ?>
                </p>
            </div>
        </div>
    </div>

    <!-- Navegación -->
    <nav style="flex:1;overflow-y:auto;padding:.6rem .7rem;" id="sidebar-nav">
        <?php
        $item = function (string $href, string $icon, string $label, string $key) use ($activeMenu): void {
            $active = ($activeMenu === $key);
            $cls    = $active ? 'nav-item active' : 'nav-item';
            echo "<a href=\"{$href}\" class=\"{$cls}\">
                    <i class=\"fas fa-{$icon} nav-icon\"></i>
                    <span>{$label}</span>
                  </a>";
        };
        $sep = function (string $label): void {
            echo "<div class=\"nav-section-label\">{$label}</div>";
        };
        ?>

        <?= $item(BASE_URL.'/index.php',              'house',               'Inicio',              'dashboard') ?>
        <?= $item(BASE_URL.'/marcacion/index.php',    'fingerprint',         'Marcar Asistencia',   'marcacion') ?>

        <?php if (in_array($rol, ['supervisor','admin'])): ?>
            <?php $sep('Supervisión') ?>
            <?= $item(BASE_URL.'/marcacion/pendientes.php', 'circle-check',   'Aprobar Marcaciones', 'pendientes') ?>
            <?= $item(BASE_URL.'/turnos/index.php',         'calendar-week',  'Turnos',              'turnos') ?>
            <?= $item(BASE_URL.'/empleados/index.php',      'users',          'Empleados',           'empleados') ?>
        <?php endif; ?>

        <?php $sep('Reportes') ?>
        <?= $item(BASE_URL.'/reportes/marcaciones.php',    'table-list',             'Marcaciones',      'rep_marcaciones') ?>
        <?= $item(BASE_URL.'/reportes/preliquidacion.php', 'file-invoice-dollar',    'Preliquidación',   'rep_preliq') ?>

        <?php if ($rol === 'admin'): ?>
            <?php $sep('Configuración') ?>
            <?= $item(BASE_URL.'/config/parametros.php', 'sliders',       'Parámetros',  'cfg_params') ?>
            <?= $item(BASE_URL.'/config/sedes.php',      'building',      'Sedes',       'cfg_sedes') ?>
            <?= $item(BASE_URL.'/config/cargos.php',     'briefcase',     'Cargos',      'cfg_cargos') ?>
            <?= $item(BASE_URL.'/config/equipos.php',    'laptop',        'Equipos',     'cfg_equipos') ?>
            <?= $item(BASE_URL.'/config/festivos.php',   'calendar-xmark','Festivos',    'cfg_festivos') ?>
        <?php endif; ?>
    </nav>

    <!-- Logout -->
    <div style="padding:.7rem;border-top:1px solid rgba(255,255,255,.06);flex-shrink:0;">
        <button onclick="TC.logout()"
                style="width:100%;display:flex;align-items:center;gap:.75rem;padding:.65rem .85rem;
                       border-radius:12px;border:none;background:transparent;color:rgba(255,255,255,.55);
                       font-family:inherit;font-size:.875rem;font-weight:700;cursor:pointer;
                       transition:background .18s,color .18s;"
                onmouseover="this.style.background='rgba(220,38,38,.25)';this.style.color='#fca5a5';"
                onmouseout="this.style.background='transparent';this.style.color='rgba(255,255,255,.55)';">
            <i class="fas fa-right-from-bracket" style="width:18px;text-align:center;font-size:.9rem;"></i>
            <span>Cerrar sesión</span>
        </button>
    </div>
</aside>

<!-- ══ ÁREA PRINCIPAL ════════════════════════════════════ -->
<div style="flex:1;display:flex;flex-direction:column;overflow:hidden;min-width:0;">

    <!-- TOPBAR -->
    <header id="topbar">
        <div style="display:flex;align-items:center;gap:.75rem;">
            <!-- Hamburger (mobile) -->
            <button id="btn-sidebar-toggle" onclick="TC.openSidebar()"
                    aria-label="Abrir menú"
                    style="width:38px;height:38px;border-radius:10px;border:1.5px solid #e2e8f0;
                           background:#fff;color:#64748b;cursor:pointer;font-size:.95rem;
                           display:none;align-items:center;justify-content:center;
                           transition:background .18s;"
                    onmouseover="this.style.background='#f8fafc';"
                    onmouseout="this.style.background='#fff';">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Título página -->
            <div>
                <h1 style="font-size:1rem;font-weight:900;color:#1e293b;line-height:1.2;">
                    <?= htmlspecialchars($pageTitle) ?>
                </h1>
                <p style="font-size:.72rem;color:#94a3b8;font-weight:600;margin-top:.1rem;">
                    <?= $saludo ?>, <?= htmlspecialchars(explode(' ', $user['nombre'] ?? 'usuario')[0]) ?>
                </p>
            </div>
        </div>

        <!-- Derecha topbar -->
        <div style="display:flex;align-items:center;gap:.75rem;">
            <!-- Reloj -->
            <div id="reloj-header"
                 style="font-size:.82rem;color:#64748b;font-weight:700;font-variant-numeric:tabular-nums;
                        background:#f8fafc;border:1.5px solid #e9edf3;padding:.35rem .85rem;
                        border-radius:10px;white-space:nowrap;display:none;">
            </div>

            <!-- Badge rol -->
            <?php
            $rolColors = [
                'admin'      => 'background:#dbeafe;color:#1e40af;',
                'supervisor' => 'background:#e0e7ff;color:#3730a3;',
                'empleado'   => 'background:#dcfce7;color:#166534;',
            ];
            $rc = $rolColors[$rol] ?? 'background:#f1f5f9;color:#475569;';
            ?>
            <span style="<?= $rc ?> font-size:.7rem;font-weight:800;text-transform:uppercase;
                          letter-spacing:.06em;padding:.3rem .75rem;border-radius:99px;">
                <?= ucfirst($rol) ?>
            </span>

            <!-- Avatar -->
            <div style="width:36px;height:36px;border-radius:50%;
                        background:linear-gradient(135deg,#1e3a5f,#2563eb);
                        border:2px solid #e2e8f0;display:flex;align-items:center;
                        justify-content:center;font-weight:900;color:#fff;font-size:.78rem;
                        flex-shrink:0;">
                <?= $iniciales ?>
            </div>
        </div>
    </header>

    <!-- CONTENIDO -->
    <main id="main-content" style="flex:1;overflow-y:auto;padding:1.5rem;">
