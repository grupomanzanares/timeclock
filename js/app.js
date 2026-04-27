// TimeClock — app.js
const TC = (() => {

    // ─── API ────────────────────────────────────────────────
    async function api(url, options = {}) {
        const res = await fetch(APP.baseUrl + url, {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
            ...options,
        });
        const data = await res.json().catch(() => ({ success: false, message: 'Error de respuesta' }));
        if (!res.ok && !data.message) data.message = `Error HTTP ${res.status}`;
        return data;
    }

    const get  = path        => api(path);
    const post = (path, body) => api(path, { method: 'POST', body: JSON.stringify(body) });

    // ─── Toast ──────────────────────────────────────────────
    function toast(msg, type = 'info', duration = 4500) {
        const map = {
            success: { cls: 'tc-toast-success', icon: 'fa-circle-check' },
            error:   { cls: 'tc-toast-error',   icon: 'fa-circle-xmark' },
            warning: { cls: 'tc-toast-warning',  icon: 'fa-triangle-exclamation' },
            info:    { cls: 'tc-toast-info',     icon: 'fa-circle-info' },
        };
        const s = map[type] || map.info;
        const el = document.createElement('div');
        el.className = `tc-toast ${s.cls}`;
        el.innerHTML = `
            <i class="fas ${s.icon}" style="flex-shrink:0;font-size:1rem;margin-top:.1rem;"></i>
            <span style="flex:1;line-height:1.4;">${msg}</span>
            <button onclick="this.parentElement.remove()"
                    style="background:none;border:none;color:rgba(255,255,255,.7);cursor:pointer;
                           font-size:1rem;padding:0;line-height:1;flex-shrink:0;margin-left:.25rem;"
                    aria-label="Cerrar">
                <i class="fas fa-xmark"></i>
            </button>`;
        document.getElementById('toast-container').appendChild(el);
        setTimeout(() => {
            el.classList.add('hide');
            setTimeout(() => el.remove(), 300);
        }, duration);
    }

    // ─── Modal ──────────────────────────────────────────────
    function openModal(title, bodyHtml, footerHtml = '') {
        document.getElementById('modal-title').textContent  = title;
        document.getElementById('modal-body').innerHTML     = bodyHtml;
        document.getElementById('modal-footer').innerHTML   = footerHtml;
        const overlay = document.getElementById('modal-overlay');
        overlay.style.display = 'flex';
    }

    function closeModal() {
        document.getElementById('modal-overlay').style.display = 'none';
    }

    // ─── Sidebar ────────────────────────────────────────────
    function openSidebar() {
        document.getElementById('sidebar')?.classList.add('open');
        document.getElementById('sidebar-overlay')?.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        document.getElementById('sidebar')?.classList.remove('open');
        document.getElementById('sidebar-overlay')?.classList.remove('show');
        document.body.style.overflow = '';
    }

    // ─── Reloj ───────────────────────────────────────────────
    function iniciarReloj(elId) {
        const el = document.getElementById(elId);
        if (!el) return;
        const tick = () => {
            const n = new Date();
            el.textContent = n.toLocaleTimeString('es-CO', {
                hour: '2-digit', minute: '2-digit', second: '2-digit'
            });
        };
        tick();
        setInterval(tick, 1000);
    }

    // ─── Formateo ────────────────────────────────────────────
    function fmtHora(h) {
        if (!h) return '—';
        return String(h).substring(0, 5);
    }

    function fmtFecha(f) {
        if (!f) return '';
        const [y, m, d] = f.split('-');
        return `${d}/${m}/${y}`;
    }

    function fmtMin(min) {
        if (min === null || min === undefined) return '—';
        const abs  = Math.abs(min);
        const h    = Math.floor(abs / 60);
        const m2   = abs % 60;
        const sign = min < 0 ? '-' : '+';
        return h > 0 ? `${sign}${h}h ${m2}m` : `${sign}${m2}m`;
    }

    function badgeEstado(estado) {
        const map = {
            puntual:           { txt: 'Puntual',          cls: 'badge-puntual' },
            llegada_tarde:     { txt: 'Llegada tarde',    cls: 'badge-llegada_tarde' },
            salida_temprana:   { txt: 'Salida temprana',  cls: 'badge-salida_temprana' },
            salida_tarde:      { txt: 'Salida tarde',     cls: 'badge-salida_tarde' },
            compensacion:      { txt: 'Compensación',     cls: 'badge-compensacion' },
            cierre_automatico: { txt: 'Auto-cerrado',     cls: 'badge-cierre_automatico' },
            fuera_turno:       { txt: 'Fuera de turno',   cls: 'badge-fuera_turno' },
            sin_turno:         { txt: 'Sin turno',        cls: 'badge-sin_turno' },
        };
        const e = map[estado] || { txt: estado || '—', cls: 'badge-sin_turno' };
        return `<span class="badge ${e.cls}">${e.txt}</span>`;
    }

    // ─── Confirmar ───────────────────────────────────────────
    function confirmar(msg, onConfirm) {
        openModal(
            'Confirmar acción',
            `<p style="color:#475569;font-size:.9rem;font-weight:600;">${msg}</p>`,
            `<button onclick="TC.closeModal()"
                     style="padding:.55rem 1rem;border:1.5px solid #e2e8f0;border-radius:10px;
                            background:#fff;color:#64748b;font-family:inherit;font-size:.82rem;
                            font-weight:700;cursor:pointer;">Cancelar</button>
             <button id="btn-confirm-yes"
                     style="padding:.55rem 1rem;border:none;border-radius:10px;
                            background:#dc2626;color:#fff;font-family:inherit;font-size:.82rem;
                            font-weight:800;cursor:pointer;">Confirmar</button>`
        );
        document.getElementById('btn-confirm-yes').onclick = () => {
            closeModal();
            onConfirm();
        };
    }

    // ─── Spinner en botón ────────────────────────────────────
    function btnLoading(btn, on) {
        if (on) {
            btn.dataset.orig = btn.innerHTML;
            btn.innerHTML    = '<span class="tc-spinner" style="width:18px;height:18px;border-width:2.5px;"></span>';
            btn.disabled     = true;
        } else {
            btn.innerHTML = btn.dataset.orig || btn.innerHTML;
            btn.disabled  = false;
        }
    }

    // ─── Logout ──────────────────────────────────────────────
    async function logout() {
        await get('/api/auth.php?action=logout').catch(() => {});
        window.location.href = APP.baseUrl + '/login.php';
    }

    // ─── Preguntar cierre de sesión (empleados) ───────────────
    function preguntarLogout(tipo) {
        const tipoLabel = tipo === 'entrada' ? 'entrada' : 'salida';
        openModal(
            '¿Cerrar sesión?',
            `<div style="display:flex;align-items:flex-start;gap:.85rem;">
                <i class="fas fa-right-from-bracket" style="font-size:1.6rem;color:#dc2626;margin-top:.15rem;flex-shrink:0;"></i>
                <div>
                    <p style="color:#1e293b;font-size:.95rem;font-weight:700;margin:0 0 .35rem;">
                        ${tipoLabel.charAt(0).toUpperCase() + tipoLabel.slice(1)} registrada exitosamente
                    </p>
                    <p style="color:#64748b;font-size:.88rem;line-height:1.55;margin:0;">
                        ¿Deseas cerrar sesión ahora?
                    </p>
                </div>
            </div>`,
            `<button onclick="TC.closeModal()"
                     style="padding:.55rem 1rem;border:1.5px solid #e2e8f0;border-radius:10px;
                            background:#fff;color:#64748b;font-family:inherit;font-size:.82rem;
                            font-weight:700;cursor:pointer;">Seguir aquí</button>
             <button id="btn-logout-si"
                     style="padding:.55rem 1rem;border:none;border-radius:10px;
                            background:#dc2626;color:#fff;font-family:inherit;font-size:.82rem;
                            font-weight:800;cursor:pointer;display:flex;align-items:center;gap:.4rem;">
                 <i class="fas fa-right-from-bracket"></i> Cerrar sesión
             </button>`
        );
        document.getElementById('btn-logout-si').onclick = () => {
            closeModal();
            logout();
        };
    }

    // ─── Init ─────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        iniciarReloj('reloj-header');

        // Cerrar modal al click en overlay
        document.getElementById('modal-overlay')?.addEventListener('click', e => {
            if (e.target.id === 'modal-overlay') closeModal();
        });

        // Cerrar sidebar con ESC
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                closeModal();
                closeSidebar();
            }
        });
    });

    return {
        api, get, post,
        toast, openModal, closeModal,
        openSidebar, closeSidebar,
        iniciarReloj, fmtHora, fmtFecha, fmtMin, badgeEstado,
        confirmar, btnLoading, logout, preguntarLogout,
    };
})();
