<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

if (Auth::check()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
<meta name="theme-color" content="#1e3a5f">
<title>Iniciar Sesión — <?= APP_NAME ?></title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<script>
tailwind.config = {
    theme: {
        extend: {
            fontFamily: { sans: ['Nunito', 'system-ui', 'sans-serif'] },
        }
    }
};
</script>

<style>
*, *::before, *::after { box-sizing: border-box; }
body { font-family: 'Nunito', system-ui, sans-serif; }

.login-bg {
    background: #eef2f7;
    background-image:
        radial-gradient(ellipse at 15% 60%, rgba(37,99,235,.09) 0%, transparent 55%),
        radial-gradient(ellipse at 85% 15%, rgba(30,58,95,.07) 0%, transparent 45%);
    min-height: 100vh;
}

/* Panel izquierdo */
.panel-left {
    background: linear-gradient(150deg, #0c2340 0%, #1e3a5f 45%, #1d4ed8 100%);
    position: relative;
    overflow: hidden;
}
.panel-left::before {
    content: '';
    position: absolute;
    inset: 0;
    background: url("data:image/svg+xml,%3Csvg width='70' height='70' viewBox='0 0 70 70' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23fff' fill-opacity='0.03'%3E%3Cpath d='M0 0h35v35H0zM35 35h35v35H35z'/%3E%3C/g%3E%3C/svg%3E");
}
.orbe {
    position: absolute;
    border-radius: 50%;
    filter: blur(70px);
    opacity: .15;
    pointer-events: none;
}
.empresa-img {
    position: absolute; inset: 0;
    width: 100%; height: 100%;
    object-fit: cover; object-position: center;
    opacity: .18;
}

/* Card */
.login-card {
    background: #ffffff;
    border-radius: 28px;
    box-shadow:
        0 25px 60px rgba(0,0,0,.11),
        0 8px 24px rgba(0,0,0,.07),
        0 0 0 1px rgba(0,0,0,.04);
}

/* Input */
.tc-input {
    width: 100%;
    padding: .8rem 1rem .8rem 2.8rem;
    border: 1.5px solid #e2e8f0;
    border-radius: 13px;
    font-size: .9rem;
    font-weight: 600;
    color: #1e293b;
    background: #f8fafc;
    font-family: inherit;
    transition: border-color .18s, box-shadow .18s, background .18s;
    -webkit-appearance: none;
}
.tc-input:focus {
    outline: none;
    border-color: #2563eb;
    background: #fff;
    box-shadow: 0 0 0 3.5px rgba(37,99,235,.14);
}
.tc-input.err {
    border-color: #ef4444;
    box-shadow: 0 0 0 3px rgba(239,68,68,.13);
}
.tc-input::placeholder { color: #94a3b8; font-weight: 500; }

/* Botón */
.btn-submit {
    width: 100%;
    padding: .9rem 1rem;
    border-radius: 14px;
    border: none;
    background: linear-gradient(135deg, #bbd130 0%, #a3c41a 100%);
    color: #1a2a00;
    font-family: inherit;
    font-weight: 900;
    font-size: .875rem;
    letter-spacing: .07em;
    text-transform: uppercase;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
    box-shadow: 0 4px 18px rgba(163,196,26,.38);
    transition: transform .15s, box-shadow .2s, filter .15s;
}
.btn-submit:hover    { filter: brightness(1.05); transform: translateY(-1px); box-shadow: 0 8px 24px rgba(163,196,26,.44); }
.btn-submit:active   { transform: translateY(0); box-shadow: 0 2px 10px rgba(163,196,26,.28); }
.btn-submit:disabled { opacity: .62; cursor: not-allowed; transform: none; filter: none; }

/* Spinner */
.spin-ring {
    width: 17px; height: 17px;
    border: 2.5px solid rgba(26,42,0,.25);
    border-top-color: #1a2a00;
    border-radius: 50%;
    animation: spin .7s linear infinite;
    display: none;
    flex-shrink: 0;
}

@keyframes spin   { to { transform: rotate(360deg); } }
@keyframes fadeUp { from { transform: translateY(-10px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
@keyframes shake  {
    0%,100%{ transform: translateX(0); }
    20%,60%{ transform: translateX(-5px); }
    40%,80%{ transform: translateX( 5px); }
}
.alert-anim { animation: fadeUp .28s ease both; }
.shake-anim { animation: shake .4s ease; }

/* Reloj */
#login-clock {
    font-variant-numeric: tabular-nums;
    font-weight: 900;
    font-size: 2.8rem;
    line-height: 1;
    color: #fff;
    letter-spacing: .02em;
}

/* Feature bullets */
.feature-item {
    display: flex;
    align-items: center;
    gap: .75rem;
    color: rgba(255,255,255,.75);
    font-size: .82rem;
    font-weight: 600;
}
.feature-dot {
    width: 7px; height: 7px;
    background: #bbd130;
    border-radius: 50%;
    flex-shrink: 0;
}

/* Responsive */
@media (max-width: 767px) {
    .panel-left { display: none !important; }
    .login-card { border-radius: 22px; }
}
@media (max-width: 400px) {
    .login-card { border-radius: 18px; }
    .form-side   { padding: 1.75rem 1.5rem !important; }
}
</style>
</head>

<body class="login-bg flex items-center justify-center p-4 py-8">

<div class="login-card w-full max-w-4xl flex overflow-hidden"
     style="min-height:600px; animation: fadeUp .48s cubic-bezier(.34,1.56,.64,1) both;">

    <!-- ══════════════════ PANEL IZQUIERDO ══════════════════ -->
    <div class="panel-left w-5/12 flex-shrink-0 flex flex-col justify-between p-10 hidden md:flex">

        <!-- Imagen fondo empresa -->
        <img src="img/empresa.jpg" alt="" class="empresa-img" onerror="this.remove()">

        <!-- Orbes decorativos -->
        <div class="orbe w-80 h-80 bg-blue-500"    style="top:-80px; right:-80px;"></div>
        <div class="orbe w-64 h-64 bg-indigo-600"  style="bottom:20px; left:-50px;"></div>
        <div class="orbe w-40 h-40 bg-blue-300"    style="top:40%; left:30%;"></div>

        <!-- Bloque superior -->
        <div class="relative z-10">
            <!-- Brand -->
            <div class="flex items-center gap-3 mb-10">
                <div class="w-12 h-12 bg-white/15 backdrop-blur-sm rounded-2xl border border-white/20
                            flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-clock text-white text-xl"></i>
                </div>
                <div>
                    <p class="text-white font-black text-lg leading-none"><?= APP_NAME ?></p>
                    <p class="text-blue-200/80 text-xs font-semibold mt-0.5">Grupo Manzanares</p>
                </div>
            </div>

            <h2 class="text-white font-black text-[1.85rem] leading-[1.2]">
                Controla tu<br>jornada laboral
            </h2>
            <p class="text-blue-200/80 text-sm font-medium mt-3 leading-relaxed">
                
            </p>

            <!-- Features -->

        </div>

        <!-- Reloj -->
        <div class="relative z-10">
            <div class="bg-white/10 backdrop-blur-sm border border-white/15 rounded-2xl px-6 py-5 inline-block">
                <p id="login-clock">--:--</p>
                <p id="login-date" class="text-blue-200/80 text-xs font-semibold mt-1.5 capitalize"></p>
            </div>
        </div>

        <!-- Footer izq -->
        <div class="relative z-10">
            <p class="text-white/25 text-xs font-semibold">&copy; <?= date('Y') ?> Grupo Manzanares</p>
        </div>
    </div>

    <!-- ══════════════════ PANEL DERECHO ════════════════════ -->
    <div class="flex-1 flex flex-col justify-center form-side"
         style="padding: 2.5rem 3rem;">

        <!-- Logo móvil -->
        <div class="flex items-center gap-3 mb-6 md:hidden">
            <div class="w-10 h-10 bg-blue-800 rounded-xl flex items-center justify-center flex-shrink-0">
                <i class="fas fa-clock text-white"></i>
            </div>
            <div>
                <p class="font-black text-slate-800 text-base leading-none"><?= APP_NAME ?></p>
                <p class="text-slate-400 text-xs mt-0.5">Grupo Manzanares</p>
            </div>
        </div>

        <!-- Logo empresa (desktop) -->
        <div class="hidden md:block mb-7">
            <img src="img/logo.jpeg" alt="Grupo Manzanares"
                 class="h-12 w-auto object-contain"
                 onerror="this.remove()">
        </div>

        <!-- Título -->
        <div class="mb-7">
            <h1 class="text-[1.6rem] font-black text-slate-800 tracking-tight leading-none">Bienvenido</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1.5">Ingresa tus credenciales para continuar</p>
        </div>

        <!-- Alerta -->
        <div id="alertBox" class="hidden mb-5"></div>

        <!-- Form -->
        <form id="loginForm" novalidate class="space-y-4">

            <!-- Cédula -->
            <div>
                <label for="cedula"
                       class="block text-sm font-bold text-slate-700 mb-1.5">
                    Cédula
                </label>
                <div class="relative">
                    <i class="fas fa-id-card absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                    <input type="text" id="cedula" name="cedula"
                           class="tc-input"
                           placeholder="Número de cédula"
                           autocomplete="username" inputmode="numeric" required>
                </div>
            </div>

            <!-- Contraseña -->
            <div>
                <label for="password"
                       class="block text-sm font-bold text-slate-700 mb-1.5">
                    Contraseña
                </label>
                <div class="relative">
                    <i class="fas fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm pointer-events-none"></i>
                    <input type="password" id="password" name="password"
                           class="tc-input pr-12"
                           placeholder="••••••••"
                           autocomplete="current-password" required>
                    <button type="button" id="togglePass"
                            class="absolute right-3.5 top-1/2 -translate-y-1/2
                                   text-slate-400 hover:text-slate-600 transition-colors p-1.5 rounded-lg"
                            aria-label="Mostrar contraseña">
                        <i class="fas fa-eye text-sm" id="toggleIcon"></i>
                    </button>
                </div>
            </div>

            <!-- Recordar -->
            <label class="flex items-center gap-2.5 cursor-pointer select-none w-fit">
                <input type="checkbox" id="remember" name="remember"
                       class="w-4 h-4 rounded accent-blue-600 cursor-pointer flex-shrink-0">
                <span class="text-sm font-semibold text-slate-600">Recordar sesión por 30 días</span>
            </label>

            <!-- Botón -->
            <button type="submit" id="btnLogin" class="btn-submit" style="margin-top:.25rem">
                <span class="spin-ring" id="spinnerEl"></span>
                <span id="btnText">
                    <i class="fas fa-arrow-right-to-bracket mr-1"></i> Iniciar sesión
                </span>
            </button>
        </form>

        <!-- Footer -->
        <div class="mt-8 pt-5 border-t border-slate-100 text-center">
            <p class="text-xs text-slate-400 font-semibold">
                &copy; <?= date('Y') ?> Grupo Manzanares &middot; Todos los derechos reservados
            </p>
        </div>
    </div>
</div>

<script>
// Reloj
(function () {
    const c = document.getElementById('login-clock');
    const d = document.getElementById('login-date');
    if (!c) return;
    const tick = () => {
        const n = new Date();
        c.textContent = n.toLocaleTimeString('es-CO', { hour:'2-digit', minute:'2-digit', second:'2-digit' });
        d.textContent = n.toLocaleDateString('es-CO', { weekday:'long', day:'numeric', month:'long' });
    };
    tick(); setInterval(tick, 1000);
})();

// Toggle password
document.getElementById('togglePass').addEventListener('click', function () {
    const inp  = document.getElementById('password');
    const icon = document.getElementById('toggleIcon');
    const show = inp.type === 'password';
    inp.type       = show ? 'text' : 'password';
    icon.className = show ? 'fas fa-eye-slash text-sm' : 'fas fa-eye text-sm';
});

// Alerta
function showAlert(msg, type) {
    const s = {
        error:   { wrap:'bg-red-50 border-red-400 text-red-800',        icon:'fa-circle-xmark text-red-500' },
        success: { wrap:'bg-emerald-50 border-emerald-400 text-emerald-800', icon:'fa-circle-check text-emerald-500' },
        warning: { wrap:'bg-amber-50 border-amber-400 text-amber-800',   icon:'fa-triangle-exclamation text-amber-500' },
    }[type] || { wrap:'bg-slate-50 border-slate-400 text-slate-800', icon:'fa-info-circle text-slate-500' };

    const box = document.getElementById('alertBox');
    box.innerHTML = `
        <div class="flex items-start gap-3 px-4 py-3.5 rounded-xl border-l-4 text-sm font-semibold alert-anim ${s.wrap}">
            <i class="fas ${s.icon} mt-0.5 flex-shrink-0 text-base"></i>
            <span>${msg}</span>
        </div>`;
    box.classList.remove('hidden');
}

// Submit
document.getElementById('loginForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const cedulaEl = document.getElementById('cedula');
    const passEl   = document.getElementById('password');
    const cedula   = cedulaEl.value.trim();
    const pass     = passEl.value;

    cedulaEl.classList.remove('err');
    passEl.classList.remove('err');
    document.getElementById('alertBox').classList.add('hidden');

    if (!cedula) { cedulaEl.classList.add('err'); cedulaEl.focus(); return; }
    if (!pass)   { passEl.classList.add('err');   passEl.focus();   return; }

    const btn     = document.getElementById('btnLogin');
    const spinner = document.getElementById('spinnerEl');
    const txt     = document.getElementById('btnText');
    btn.disabled  = true;
    spinner.style.display = 'block';
    txt.style.display     = 'none';

    try {
        const res  = await fetch('<?= BASE_URL ?>/api/auth.php?action=login', {
            method: 'POST',
            headers: { 'Content-Type':'application/json', 'X-Requested-With':'XMLHttpRequest' },
            body: JSON.stringify({ cedula, password: pass }),
        });
        const data = await res.json();

        if (data.success) {
            if (data.data?.dias_cerrados?.length) {
                showAlert('Se cerraron automáticamente días sin marcación de salida: ' + data.data.dias_cerrados.join(', '), 'warning');
                await new Promise(r => setTimeout(r, 2200));
            }
            window.location.href = data.data?.redirect || '<?= BASE_URL ?>/index.php';
        } else {
            showAlert(data.message || 'Credenciales incorrectas. Verifique e intente de nuevo.', 'error');
            const form = document.getElementById('loginForm');
            form.classList.remove('shake-anim');
            void form.offsetWidth;
            form.classList.add('shake-anim');
            setTimeout(() => form.classList.remove('shake-anim'), 500);
            btn.disabled          = false;
            spinner.style.display = 'none';
            txt.style.display     = '';
        }
    } catch {
        showAlert('Error de conexión. Verifique su red e intente de nuevo.', 'error');
        btn.disabled          = false;
        spinner.style.display = 'none';
        txt.style.display     = '';
    }
});

// Limpiar al escribir
['cedula','password'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', function () {
        this.classList.remove('err');
        document.getElementById('alertBox').classList.add('hidden');
    });
});

// Enter en cédula → foco contraseña
document.getElementById('cedula')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); document.getElementById('password').focus(); }
});
</script>
</body>
</html>
