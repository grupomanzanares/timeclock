// pendientes.js
document.addEventListener('DOMContentLoaded', cargarPendientes);

async function cargarPendientes() {
  const tbody = document.getElementById('tbody-pendientes');
  tbody.innerHTML = '<tr><td colspan="8" class="py-8 text-center"><span class="tc-spinner"></span></td></tr>';

  const res = await TC.get('/api/marcaciones.php?action=pendientes');
  if (!res.success) {
    tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-red-500 text-sm">${res.message}</td></tr>`;
    return;
  }

  if (!res.data.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-sm text-gray-400"><i class="fas fa-circle-check text-green-400 mr-2"></i>No hay marcaciones pendientes</td></tr>';
    return;
  }

  tbody.innerHTML = res.data.map(m => `
    <tr class="border-b border-gray-50" id="row-${m.id}">
      <td class="py-3 pr-4">
        <p class="font-medium text-gray-900">${m.nombre} ${m.apellido}</p>
      </td>
      <td class="py-3 pr-4 text-gray-500">${m.cargo_nombre || '—'}</td>
      <td class="py-3 pr-4 text-gray-500">${m.sede_nombre || '—'}</td>
      <td class="py-3 pr-4">
        <p class="font-medium">${TC.fmtFecha(m.fecha)}</p>
        <p class="text-xs text-gray-400">${TC.fmtHora(m.hora)}</p>
      </td>
      <td class="py-3 pr-4">
        <span class="px-2 py-0.5 rounded-full text-xs font-medium ${m.tipo === 'entrada' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-600'}">
          ${m.tipo}
        </span>
      </td>
      <td class="py-3 pr-4">${TC.badgeEstado(m.estado)}</td>
      <td class="py-3 pr-4 font-mono text-sm ${m.minutos_diferencia > 0 ? 'text-red-600' : 'text-green-600'}">
        ${TC.fmtMin(m.minutos_diferencia)}
      </td>
      <td class="py-3">
        <div class="flex items-center gap-2">
          <button onclick="aprobar(${m.id},'aprobado')"
            class="px-3 py-1.5 text-xs font-medium bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors">
            <i class="fas fa-check mr-1"></i>Aprobar
          </button>
          <button onclick="rechazar(${m.id})"
            class="px-3 py-1.5 text-xs font-medium bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
            <i class="fas fa-xmark mr-1"></i>Rechazar
          </button>
        </div>
        ${m.observacion ? `<p class="text-xs text-gray-400 mt-1 max-w-xs truncate">${m.observacion}</p>` : ''}
      </td>
    </tr>`).join('');
}

async function aprobar(id, decision, observacion = '') {
  const res = await TC.post('/api/marcaciones.php?action=aprobar', { id, decision, observacion });
  TC.toast(res.message, res.success ? 'success' : 'error');
  if (res.success) {
    const row = document.getElementById(`row-${id}`);
    row?.classList.add('opacity-0', 'transition-opacity');
    setTimeout(() => row?.remove(), 300);
  }
}

function rechazar(id) {
  TC.openModal(
    'Rechazar marcación',
    `<div class="space-y-3">
      <p class="text-sm text-gray-600">Indique el motivo del rechazo:</p>
      <textarea id="obs-rechazo" rows="3" placeholder="Motivo..." class="tc-input resize-none"></textarea>
    </div>`,
    `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
     <button onclick="confirmarRechazo(${id})" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">Rechazar</button>`
  );
}

async function confirmarRechazo(id) {
  const obs = document.getElementById('obs-rechazo').value;
  TC.closeModal();
  await aprobar(id, 'rechazado', obs);
}
