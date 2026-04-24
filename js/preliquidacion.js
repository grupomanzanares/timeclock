// preliquidacion.js
const Preliq = (() => {
  document.addEventListener('DOMContentLoaded', () => {
    if (APP.rol !== 'empleado') cargarEmpleados();
  });

  async function cargarEmpleados() {
    const sel = document.getElementById('p-usuario');
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
    const uid   = document.getElementById('p-usuario')?.value || APP.userId;
    const fecha = document.getElementById('p-fecha').value;
    if (!fecha) { TC.toast('Seleccione una fecha', 'warning'); return; }

    const resDiv  = document.getElementById('resumen-preliq');
    const cardDet = document.getElementById('card-detalle-preliq');
    resDiv.classList.add('hidden');
    cardDet.style.display = 'none';

    const res = await TC.get(`/api/reportes.php?action=preliquidacion&usuario_id=${uid}&fecha=${fecha}`);
    if (!res.success) { TC.toast(res.message, 'error'); return; }

    const d = res.data;
    const u = d.usuario;

    // Resumen cards
    const signo = d.diferencia >= 0 ? '+' : '';
    const difColor = d.diferencia >= 0 ? 'text-green-700' : 'text-red-600';
    resDiv.innerHTML = `
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
        <div class="tc-card p-4 text-center col-span-2 sm:col-span-3 lg:col-span-2 lg:row-span-1">
          <p class="text-xs text-gray-400">Empleado</p>
          <p class="font-bold text-gray-900 mt-1">${u?.nombre} ${u?.apellido}</p>
          <p class="text-xs text-gray-500">${u?.cargo_nombre || ''}</p>
          <p class="text-xs text-indigo-600 mt-1">Semana: ${TC.fmtFecha(d.semana_inicio)} — ${TC.fmtFecha(d.semana_fin)}</p>
        </div>
        <div class="tc-card p-4 text-center">
          <p class="text-xs text-gray-400">Días normales</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">${d.dias_normales}</p>
          <p class="text-xs text-gray-400">Lun–Sáb</p>
        </div>
        <div class="tc-card p-4 text-center">
          <p class="text-xs text-gray-400">Horas debidas</p>
          <p class="text-2xl font-bold text-gray-900 mt-1">${d.horas_debidas}h</p>
          <p class="text-xs text-gray-400">${d.dias_normales} × 7.33</p>
        </div>
        <div class="tc-card p-4 text-center">
          <p class="text-xs text-gray-400">Horas laboradas</p>
          <p class="text-2xl font-bold text-indigo-700 mt-1">${d.horas_normales}h</p>
          <p class="text-xs text-gray-400">Días normales</p>
        </div>
        <div class="tc-card p-4 text-center">
          <p class="text-xs text-gray-400">Diferencia</p>
          <p class="text-2xl font-bold ${difColor} mt-1">${signo}${d.diferencia}h</p>
          <p class="text-xs text-gray-400">${d.diferencia >= 0 ? 'Extra' : 'Faltante'}</p>
        </div>
      </div>
      ${d.horas_festivos > 0 || d.horas_domingos > 0 ? `
      <div class="grid grid-cols-2 sm:grid-cols-2 gap-3 mt-3">
        <div class="tc-card p-4 text-center">
          <p class="text-xs text-gray-400">Horas festivos</p>
          <p class="text-2xl font-bold text-purple-700 mt-1">${d.horas_festivos}h</p>
          <p class="text-xs text-gray-400">${d.dias_festivos} día(s)</p>
        </div>
        <div class="tc-card p-4 text-center">
          <p class="text-xs text-gray-400">Horas domingos</p>
          <p class="text-2xl font-bold text-pink-600 mt-1">${d.horas_domingos}h</p>
          <p class="text-xs text-gray-400">${d.dias_domingos} día(s)</p>
        </div>
      </div>` : ''}`;
    resDiv.classList.remove('hidden');

    // Tabla detalle
    document.getElementById('titulo-preliq').textContent = `Detalle semana ${TC.fmtFecha(d.semana_inicio)} al ${TC.fmtFecha(d.semana_fin)}`;
    const tbody = document.getElementById('tbody-preliq');
    const TIPO_COLORS = {
      normal:  'bg-white',
      festivo: 'bg-purple-50',
      domingo: 'bg-pink-50',
    };
    tbody.innerHTML = d.detalle.map(r => `
      <tr class="border-b border-gray-50 ${TIPO_COLORS[r.tipo] || ''}">
        <td class="py-2.5 pr-3">${TC.fmtFecha(r.fecha)}</td>
        <td class="py-2.5 pr-3">${r.dia}</td>
        <td class="py-2.5 pr-3">
          ${r.tipo === 'festivo' ? '<span class="text-xs font-medium text-purple-700"><i class="fas fa-star mr-1"></i>Festivo</span>' :
            r.tipo === 'domingo' ? '<span class="text-xs font-medium text-pink-600">Domingo</span>' :
            '<span class="text-xs text-gray-400">Normal</span>'}
        </td>
        <td class="py-2.5 pr-3 text-xs">${r.nombre_festivo || ''}</td>
        <td class="py-2.5 pr-3 font-mono text-sm">${TC.fmtHora(r.hora_entrada) || '—'}</td>
        <td class="py-2.5 pr-3 font-mono text-sm">${TC.fmtHora(r.hora_salida) || '—'}</td>
        <td class="py-2.5 text-right font-semibold">${r.horas_netas ? r.horas_netas + 'h' : '—'}</td>
      </tr>`).join('');

    document.getElementById('tfoot-preliq').innerHTML = `
      <tr>
        <td colspan="5" class="py-3 pl-2 text-sm">TOTALES</td>
        <td class="py-3 text-right text-sm text-indigo-700">${d.horas_normales}h normales</td>
        <td class="py-3 text-right text-sm">${(parseFloat(d.horas_normales)+parseFloat(d.horas_festivos)+parseFloat(d.horas_domingos)).toFixed(2)}h total</td>
      </tr>`;

    cardDet.style.display = '';
  }

  function exportar() {
    const uid   = document.getElementById('p-usuario')?.value || APP.userId;
    const fecha = document.getElementById('p-fecha').value;
    if (!fecha) { TC.toast('Seleccione una fecha', 'warning'); return; }
    window.open(`${APP.baseUrl}/api/reportes.php?action=exportar_excel&usuario_id=${uid}&desde=${fecha}&hasta=${fecha}&tipo=preliquidacion`);
  }

  return { cargar, exportar };
})();
