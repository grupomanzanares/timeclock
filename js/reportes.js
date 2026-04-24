// reportes.js
const Reportes = (() => {
  document.addEventListener('DOMContentLoaded', () => {
    if (APP.rol !== 'empleado') cargarEmpleados();
  });

  async function cargarEmpleados() {
    const sel = document.getElementById('f-usuario');
    if (!sel) return;
    const res = await TC.get('/api/usuarios.php?action=listar');
    (res.data || []).forEach(u => {
      const opt = document.createElement('option');
      opt.value = u.id;
      opt.textContent = `${u.nombre} ${u.apellido}`;
      sel.appendChild(opt);
    });
  }

  async function cargar() {
    const uid   = document.getElementById('f-usuario')?.value || APP.userId;
    const desde = document.getElementById('f-desde').value;
    const hasta = document.getElementById('f-hasta').value;
    const tbody = document.getElementById('tbody-reporte');
    tbody.innerHTML = '<tr><td colspan="8" class="py-6 text-center"><span class="tc-spinner"></span></td></tr>';

    const res = await TC.get(`/api/reportes.php?action=marcaciones&usuario_id=${uid}&desde=${desde}&hasta=${hasta}`);
    if (!res.success) {
      tbody.innerHTML = `<tr><td colspan="8" class="py-6 text-center text-red-500 text-sm">${res.message}</td></tr>`;
      return;
    }

    const { usuario, registros } = res.data;
    document.getElementById('titulo-reporte').textContent =
      `${usuario?.nombre} ${usuario?.apellido} — ${desde} al ${hasta}`;

    // Resumen
    let totalHoras = 0, tardanzas = 0, festivos = 0;
    registros.forEach(r => {
      totalHoras += parseFloat(r.horas_netas || 0);
      if (r.estado_entrada === 'llegada_tarde') tardanzas++;
      if (r.es_festivo == 1) festivos++;
    });
    document.getElementById('r-dias').textContent = registros.length;
    document.getElementById('r-horas').textContent = totalHoras.toFixed(2) + 'h';
    document.getElementById('r-tardanzas').textContent = tardanzas;
    document.getElementById('r-festivos').textContent = festivos;
    document.getElementById('resumen-cards').classList.remove('hidden');

    if (!registros.length) {
      tbody.innerHTML = '<tr><td colspan="8" class="py-6 text-center text-sm text-gray-400">Sin registros en el período</td></tr>';
      return;
    }

    tbody.innerHTML = registros.map(r => {
      const autoCerrado = r.auto_cerrado == 1
        ? '<span class="ml-1 text-xs text-gray-400" title="Cierre automático"><i class="fas fa-robot"></i></span>'
        : '';
      return `
        <tr class="border-b border-gray-50 ${r.es_festivo == 1 ? 'bg-purple-50/40' : ''}">
          <td class="py-2.5 pr-3">${TC.fmtFecha(r.fecha)}</td>
          <td class="py-2.5 pr-3 text-gray-500">${r.dia_nombre}</td>
          <td class="py-2.5 pr-3">
            ${r.es_festivo == 1
              ? `<span class="text-xs font-medium text-purple-700" title="${r.nombre_festivo||''}">
                  <i class="fas fa-star text-purple-400"></i> ${r.nombre_festivo || 'Festivo'}</span>`
              : '<span class="text-gray-300 text-xs">—</span>'}
          </td>
          <td class="py-2.5 pr-3 font-mono text-sm">${TC.fmtHora(r.hora_entrada) || '—'}</td>
          <td class="py-2.5 pr-3 font-mono text-sm">${TC.fmtHora(r.hora_salida) || '—'}${autoCerrado}</td>
          <td class="py-2.5 pr-3 font-semibold">${r.horas_netas ? r.horas_netas + 'h' : '—'}</td>
          <td class="py-2.5 pr-3">${TC.badgeEstado(r.estado_entrada || 'sin_turno')}</td>
          <td class="py-2.5 text-xs text-gray-500 max-w-xs truncate">
            ${r.obs_entrada || r.obs_salida || ''}
          </td>
        </tr>`;
    }).join('');
  }

  function exportar() {
    const uid   = document.getElementById('f-usuario')?.value || APP.userId;
    const desde = document.getElementById('f-desde').value;
    const hasta = document.getElementById('f-hasta').value;
    window.open(`${APP.baseUrl}/api/reportes.php?action=exportar_excel&usuario_id=${uid}&desde=${desde}&hasta=${hasta}&tipo=marcaciones`);
  }

  return { cargar, exportar };
})();
