// marcacion.js
let estadoActual = null;

document.addEventListener('DOMContentLoaded', () => {
  TC.iniciarReloj('reloj-marcacion');
  cargarEstado();
});

async function cargarEstado() {
  const res = await TC.get('/api/marcaciones.php?action=estado');
  if (!res.success) {
    TC.toast(res.message, 'error');
    return;
  }
  estadoActual = res.data;
  renderTurno(res.data);
  renderEstado(res.data);
  renderBoton(res.data);
}

function renderTurno(d) {
  const panel = document.getElementById('panel-turno');
  if (!d.turno) {
    panel.innerHTML = `
      <div class="flex items-center gap-3 text-amber-600">
        <i class="fas fa-triangle-exclamation text-xl"></i>
        <div>
          <p class="font-semibold">Sin turno asignado</p>
          <p class="text-sm text-gray-500">No tiene turno asignado para el día de hoy.</p>
        </div>
      </div>`;
    return;
  }
  const t = d.turno;
  const dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
  const diasActivos = (t.dias_semana || '1,2,3,4,5').split(',').map(Number);
  const pillsHtml = dias.map((n, i) =>
    `<span class="day-pill ${diasActivos.includes(i) ? 'active' : 'inactive'}">${n}</span>`
  ).join('');

  panel.innerHTML = `
    <div class="flex items-start justify-between">
      <div>
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Turno asignado</p>
        <p class="text-lg font-bold text-gray-900">${t.nombre}</p>
        <p class="text-indigo-600 font-semibold text-base mt-0.5">
          ${TC.fmtHora(t.hora_inicio)} — ${TC.fmtHora(t.hora_fin)}
          ${t.nocturno ? '<span class="ml-2 text-xs font-medium bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">Nocturno</span>' : ''}
        </p>
      </div>
    </div>
    <div class="flex gap-1.5 mt-3">${pillsHtml}</div>`;
}

function renderEstado(d) {
  const panel = document.getElementById('panel-estado');
  if (!d.entrada) { panel.classList.add('hidden'); return; }
  panel.classList.remove('hidden');

  let html = `<p class="text-xs text-gray-400 uppercase tracking-wider mb-3">Estado del día</p>
    <div class="space-y-2">
      <div class="flex items-center justify-between py-2 border-b border-gray-50">
        <div class="flex items-center gap-2 text-sm">
          <i class="fas fa-sign-in-alt text-green-500 w-4"></i>
          <span class="text-gray-500">Entrada</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-gray-900">${TC.fmtHora(d.entrada.hora)}</span>
          ${TC.badgeEstado(d.entrada.estado)}
        </div>
      </div>`;

  if (d.salida) {
    html += `<div class="flex items-center justify-between py-2">
        <div class="flex items-center gap-2 text-sm">
          <i class="fas fa-sign-out-alt text-red-400 w-4"></i>
          <span class="text-gray-500">Salida</span>
        </div>
        <div class="flex items-center gap-2">
          <span class="font-semibold text-gray-900">${TC.fmtHora(d.salida.hora)}</span>
          ${TC.badgeEstado(d.salida.estado)}
        </div>
      </div>`;
  } else {
    html += `<div class="flex items-center justify-between py-2">
        <div class="flex items-center gap-2 text-sm">
          <i class="fas fa-sign-out-alt text-gray-300 w-4"></i>
          <span class="text-gray-400">Salida</span>
        </div>
        <span class="text-gray-400 text-sm">Pendiente</span>
      </div>`;
  }

  html += '</div>';
  panel.innerHTML = html;
}

function renderBoton(d) {
  const panel = document.getElementById('panel-marcar');
  const yaCompleto = d.entrada && d.salida;

  if (yaCompleto) {
    panel.classList.remove('hidden');
    panel.innerHTML = `
      <div class="flex items-center gap-3 text-green-700 bg-green-50 rounded-xl p-4">
        <i class="fas fa-circle-check text-2xl"></i>
        <div>
          <p class="font-semibold">Jornada completada</p>
          <p class="text-sm text-green-600">Ha registrado entrada y salida del día de hoy.</p>
        </div>
      </div>`;
    return;
  }

  if (!d.puede_marcar) {
    panel.classList.add('hidden');
    return;
  }

  panel.classList.remove('hidden');
  const tipo   = d.tipo_proximo;
  const esEnt  = tipo === 'entrada';
  const btnEl  = document.getElementById('btn-marcar');
  const lblEl  = document.getElementById('btn-label');
  const icnEl  = document.getElementById('btn-icon');
  const titEl  = document.getElementById('titulo-marcar');

  titEl.textContent = esEnt ? 'Registrar entrada' : 'Registrar salida';
  lblEl.textContent = esEnt ? 'Marcar Entrada' : 'Marcar Salida';
  icnEl.className   = `fas ${esEnt ? 'fa-sign-in-alt' : 'fa-sign-out-alt'}`;
  btnEl.className   = `btn-marcar w-full py-3 rounded-xl text-white font-semibold text-base flex items-center justify-center gap-2
    ${esEnt ? 'bg-green-600 hover:bg-green-700' : 'bg-red-500 hover:bg-red-600'}`;

  btnEl.onclick = async () => {
    const obs = document.getElementById('observacion-marc').value;
    TC.btnLoading(btnEl, true);
    const res = await TC.post('/api/marcaciones.php?action=marcar', { tipo, observacion: obs });
    TC.btnLoading(btnEl, false);
    const tipo_toast = res.success
      ? (res.data?.estado === 'puntual' ? 'success' : 'warning')
      : 'error';
    TC.toast(res.message, tipo_toast, 6000);
    if (res.success) {
      document.getElementById('observacion-marc').value = '';
      await cargarEstado();
    }
  };
}
