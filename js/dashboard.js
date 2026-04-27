// dashboard.js
document.addEventListener('DOMContentLoaded', async () => {
  cargarStats();
  cargarMarcacionRapida();
  cargarUltimasMarcaciones();
});

async function cargarStats() {
  if (APP.rol === 'empleado') return;
  const res = await TC.get('/api/reportes.php?action=dashboard');
  if (!res.success) return;
  const d = res.data;
  document.getElementById('stat-hoy')?.textContent !== undefined &&
    (document.getElementById('stat-hoy').textContent = d.marcaciones_hoy);
  document.getElementById('stat-pendientes') &&
    (document.getElementById('stat-pendientes').textContent = d.pendientes_aprobacion);
  document.getElementById('stat-tardanzas') &&
    (document.getElementById('stat-tardanzas').textContent = d.tardanzas_hoy);
}

async function cargarMarcacionRapida() {
  const cont = document.getElementById('marcacion-rapida');
  const res  = await TC.get('/api/marcaciones.php?action=estado');
  if (!res.success) {
    cont.innerHTML = `<p class="text-sm text-red-500">${res.message}</p>`;
    return;
  }
  const d = res.data;

  let turnoHtml = '';
  if (d.turno) {
    turnoHtml = `<div class="mb-4 p-3 bg-indigo-50 rounded-lg text-sm">
      <p class="font-medium text-indigo-800">${d.turno.nombre}</p>
      <p class="text-indigo-600">${TC.fmtHora(d.turno.hora_inicio)} — ${TC.fmtHora(d.turno.hora_fin)}
        ${d.turno.nocturno ? '<span class="ml-1 text-xs bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded">Nocturno</span>' : ''}
      </p>
    </div>`;
  } else {
    turnoHtml = `<div class="mb-4 p-3 bg-gray-50 rounded-lg text-sm text-gray-500">Sin turno asignado para hoy</div>`;
  }

  let estadoHtml = '';
  if (d.entrada) {
    estadoHtml = `<div class="flex items-center gap-3 mb-3">
      <i class="fas fa-sign-in-alt text-green-500"></i>
      <div>
        <p class="text-xs text-gray-500">Entrada</p>
        <p class="text-sm font-semibold text-gray-800">${TC.fmtHora(d.entrada.hora)}</p>
      </div>
      ${TC.badgeEstado(d.entrada.estado)}
    </div>`;
  }
  if (d.salida) {
    estadoHtml += `<div class="flex items-center gap-3 mb-3">
      <i class="fas fa-sign-out-alt text-red-400"></i>
      <div>
        <p class="text-xs text-gray-500">Salida</p>
        <p class="text-sm font-semibold text-gray-800">${TC.fmtHora(d.salida.hora)}</p>
      </div>
      ${TC.badgeEstado(d.salida.estado)}
    </div>`;
  }

  const yaCompleto = d.entrada && d.salida;
  const btnTipo    = d.tipo_proximo;
  const btnColor   = btnTipo === 'entrada' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-500 hover:bg-red-600';
  const btnIcon    = btnTipo === 'entrada' ? 'fa-sign-in-alt' : 'fa-sign-out-alt';
  const btnLabel   = btnTipo === 'entrada' ? 'Marcar Entrada' : 'Marcar Salida';

  cont.innerHTML = `
    ${turnoHtml}
    ${estadoHtml}
    ${yaCompleto
      ? `<p class="text-sm text-green-700 font-medium flex items-center gap-2"><i class="fas fa-circle-check"></i> Jornada completada hoy</p>`
      : `<button id="btn-rapido" class="btn-marcar w-full ${btnColor} text-white py-2.5 rounded-xl font-semibold text-sm flex items-center justify-center gap-2">
           <i class="fas ${btnIcon}"></i> ${btnLabel}
         </button>
         <textarea id="obs-rapida" placeholder="Observación (opcional)..." rows="2"
           class="tc-input mt-3 resize-none text-xs"></textarea>`
    }
  `;

  document.getElementById('btn-rapido')?.addEventListener('click', async () => {
    const btn = document.getElementById('btn-rapido');
    const obs = document.getElementById('obs-rapida')?.value || '';
    TC.btnLoading(btn, true);
    const res = await TC.post('/api/marcaciones.php?action=marcar', { tipo: btnTipo, observacion: obs });
    TC.btnLoading(btn, false);
    TC.toast(res.message, res.success ? (res.data?.estado === 'puntual' ? 'success' : 'warning') : 'error');
    if (res.success) {
      setTimeout(() => { cargarMarcacionRapida(); cargarUltimasMarcaciones(); }, 800);
      if (APP.rol === 'empleado') setTimeout(() => TC.preguntarLogout(btnTipo), 900);
    }
  });
}

async function cargarUltimasMarcaciones() {
  const tbody = document.getElementById('tabla-ultimas');
  if (!tbody) return;

  const desde = new Date();
  desde.setDate(desde.getDate() - 7);
  const dStr = desde.toISOString().slice(0, 10);
  const hStr = new Date().toISOString().slice(0, 10);

  const res = await TC.get(`/api/reportes.php?action=marcaciones&desde=${dStr}&hasta=${hStr}`);
  if (!res.success || !res.data.registros.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="py-6 text-center text-sm text-gray-400">Sin registros recientes</td></tr>';
    return;
  }

  tbody.innerHTML = res.data.registros.reverse().slice(0, 7).map(r => `
    <tr class="border-b border-gray-50">
      <td class="py-3 pr-4">${TC.fmtFecha(r.fecha)}</td>
      <td class="py-3 pr-4 text-gray-500">${r.dia_nombre}</td>
      <td class="py-3 pr-4">${TC.fmtHora(r.hora_entrada) || '—'}</td>
      <td class="py-3 pr-4">${TC.fmtHora(r.hora_salida) || '—'}</td>
      <td class="py-3 pr-4 font-mono text-sm">${r.horas_netas ? r.horas_netas + 'h' : '—'}</td>
      <td class="py-3">${TC.badgeEstado(r.estado_entrada || 'sin_turno')}</td>
    </tr>`).join('');
}
