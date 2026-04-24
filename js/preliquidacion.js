// preliquidacion.js
const Preliq = (() => {

  let _datos = [];

  document.addEventListener('DOMContentLoaded', () => {
    actualizarInfo();
    actualizarCntVisibles();
  });

  // ── Filtrar lista de empleados ────────────────────────────────
  function filtrarLista() {
    const sedeId  = parseInt(document.getElementById('f-sede')?.value  || '0') || 0;
    const cargoId = parseInt(document.getElementById('f-cargo')?.value || '0') || 0;
    const buscar  = (document.getElementById('f-buscar')?.value || '').toLowerCase().trim();

    document.querySelectorAll('.emp-item').forEach(el => {
      const ok = (!sedeId  || parseInt(el.dataset.sedeId)  === sedeId)
              && (!cargoId || parseInt(el.dataset.cargoId) === cargoId)
              && (!buscar  || el.dataset.nombre.includes(buscar));
      el.style.display = ok ? '' : 'none';
    });

    // Resincronizar "seleccionar todos"
    const vis  = _visibles();
    const sel  = vis.filter(el => el.querySelector('.chk-emp').checked);
    const chk  = document.getElementById('chk-todos-emp');
    if (chk) {
      chk.checked       = vis.length > 0 && sel.length === vis.length;
      chk.indeterminate = sel.length > 0 && sel.length < vis.length;
    }

    actualizarCntVisibles();
    actualizarInfo();
  }

  function toggleTodos(checked) {
    _visibles().forEach(el => { el.querySelector('.chk-emp').checked = checked; });
    actualizarInfo();
  }

  function limpiarSeleccion() {
    document.querySelectorAll('.chk-emp').forEach(c => { c.checked = false; });
    const chk = document.getElementById('chk-todos-emp');
    if (chk) { chk.checked = false; chk.indeterminate = false; }
    actualizarInfo();
  }

  function onCheckEmp() {
    const vis = _visibles();
    const sel = vis.filter(el => el.querySelector('.chk-emp').checked);
    const chk = document.getElementById('chk-todos-emp');
    if (chk) {
      chk.checked       = vis.length > 0 && sel.length === vis.length;
      chk.indeterminate = sel.length > 0 && sel.length < vis.length;
    }
    actualizarInfo();
  }

  function _visibles() {
    return [...document.querySelectorAll('.emp-item')].filter(el => el.style.display !== 'none');
  }

  // IDs marcados explícitamente con checkbox
  function _marcados() {
    return [...document.querySelectorAll('.chk-emp:checked')].map(c => parseInt(c.value));
  }

  // IDs que se usarán al calcular: marcados o, si no hay, todos los visibles
  function _idsParaCalcular() {
    const marcados = _marcados();
    if (marcados.length) return marcados;
    return _visibles().map(el => parseInt(el.querySelector('.chk-emp').value));
  }

  function actualizarInfo() {
    const lbl = document.getElementById('lbl-seleccionados');
    if (!lbl) return;
    const marcados = _marcados().length;
    const visibles = _visibles().length;
    const total    = document.querySelectorAll('.emp-item').length;

    if (marcados > 0) {
      lbl.textContent = `Calculará para ${marcados} empleado${marcados !== 1 ? 's' : ''} seleccionado${marcados !== 1 ? 's' : ''}`;
    } else if (visibles < total) {
      lbl.textContent = `Calculará para los ${visibles} empleados del filtro actual`;
    } else {
      lbl.textContent = `Calculará para todos los empleados (${total})`;
    }

    const cnt = document.getElementById('cnt-selec');
    if (cnt) cnt.textContent = marcados > 0 ? `· ${marcados} seleccionado${marcados !== 1 ? 's' : ''}` : '';
  }

  function actualizarCntVisibles() {
    const el = document.getElementById('cnt-visibles');
    if (!el) return;
    const total = document.querySelectorAll('.emp-item').length;
    const vis   = _visibles().length;
    el.textContent = vis < total ? `${vis} de ${total} empleados visibles` : `${total} empleado${total !== 1 ? 's' : ''}`;
  }

  // ── Calcular ──────────────────────────────────────────────────
  async function calcular() {
    const desde = document.getElementById('p-desde')?.value;
    const hasta = document.getElementById('p-hasta')?.value;
    if (!desde || !hasta) { TC.toast('Seleccione el rango de fechas', 'warning'); return; }
    if (hasta < desde)    { TC.toast('La fecha "Hasta" debe ser mayor que "Desde"', 'warning'); return; }

    // Usar marcados; si ninguno, usar visibles del filtro actual
    const ids = _idsParaCalcular();

    const resDiv = document.getElementById('resultados-preliq');
    resDiv.classList.remove('hidden');
    resDiv.innerHTML = '<div class="py-10 text-center"><span class="tc-spinner"></span> <span class="text-sm text-gray-400 ml-2">Calculando...</span></div>';

    const res = await TC.post('/api/reportes.php?action=preliquidacion_masiva', {
      usuario_ids: ids,
      fecha_desde: desde,
      fecha_hasta: hasta,
    });

    if (!res.success) {
      resDiv.innerHTML = `<p class="text-center text-red-500 text-sm py-6">${res.message}</p>`;
      return;
    }

    _datos = res.data || [];
    renderResultados(desde, hasta);
  }

  // ── Render resultados ─────────────────────────────────────────
  function renderResultados(desde, hasta) {
    const resDiv = document.getElementById('resultados-preliq');

    if (!_datos.length) {
      resDiv.innerHTML = `
        <div class="tc-card p-8 text-center text-gray-400">
          <i class="fas fa-inbox text-3xl mb-3 block"></i>
          Sin registros de marcación para el período seleccionado.
        </div>`;
      return;
    }

    const periodo = `${TC.fmtFecha(desde)} — ${TC.fmtFecha(hasta)}`;
    resDiv.innerHTML = `
      <div class="flex items-center justify-between px-1 mb-1">
        <p class="text-sm text-gray-600 font-medium">
          ${periodo} &nbsp;·&nbsp;
          <span class="text-gray-400">${_datos.length} empleado${_datos.length !== 1 ? 's' : ''}</span>
        </p>
        <button onclick="Preliq.exportarCsv()"
                class="flex items-center gap-1.5 text-sm text-green-700 border border-green-300 px-3 py-1.5 rounded-lg hover:bg-green-50">
          <i class="fas fa-file-excel"></i> Exportar CSV
        </button>
      </div>
      ${_datos.map((e, i) => cardEmpleado(e, i)).join('')}`;
  }

  function cardEmpleado(e, idx) {
    const dif      = parseFloat(e.diferencia);
    const difSign  = dif >= 0 ? '+' : '';
    const difColor = dif > 0 ? 'bg-green-50 text-green-700' : dif < 0 ? 'bg-red-50 text-red-600' : 'bg-gray-50 text-gray-500';
    const totalH   = (+e.horas_normales + +e.horas_festivos + +e.horas_domingos).toFixed(2);
    const tienedet = e.detalle && e.detalle.length > 0;

    const statFest = parseFloat(e.horas_festivos) > 0
      ? `<div class="tc-stat-box bg-purple-50">
           <p class="tc-stat-label text-purple-400">H. festivos</p>
           <p class="tc-stat-val text-purple-700">${e.horas_festivos}h</p>
           <p class="tc-stat-sub text-purple-300">${e.dias_festivos} día${e.dias_festivos !== 1 ? 's' : ''}</p>
         </div>`
      : `<div class="tc-stat-box bg-gray-50 opacity-40">
           <p class="tc-stat-label">H. festivos</p>
           <p class="tc-stat-val text-gray-300">—</p>
         </div>`;

    const statDom = parseFloat(e.horas_domingos) > 0
      ? `<div class="tc-stat-box bg-pink-50">
           <p class="tc-stat-label text-pink-400">H. domingos</p>
           <p class="tc-stat-val text-pink-600">${e.horas_domingos}h</p>
           <p class="tc-stat-sub text-pink-300">${e.dias_domingos} día${e.dias_domingos !== 1 ? 's' : ''}</p>
         </div>`
      : `<div class="tc-stat-box bg-gray-50 opacity-40">
           <p class="tc-stat-label">H. domingos</p>
           <p class="tc-stat-val text-gray-300">—</p>
         </div>`;

    return `
      <div class="tc-card mb-3 overflow-hidden">
        <!-- Encabezado del empleado -->
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
          <div>
            <p class="font-semibold text-gray-900 text-base">${e.nombre} ${e.apellido}</p>
            <p class="text-xs text-gray-400 mt-0.5">
              <i class="fas fa-briefcase mr-1 opacity-50"></i>${e.cargo_nombre || '—'}
              &nbsp;·&nbsp;
              <i class="fas fa-location-dot mr-1 opacity-50"></i>${e.sede_nombre || '—'}
            </p>
          </div>
          ${tienedet ? `
          <button onclick="Preliq.toggleDetalle(${idx})" id="btn-det-${idx}"
                  class="flex items-center gap-2 px-3 py-1.5 text-sm border border-gray-200 rounded-lg
                         hover:border-indigo-300 hover:text-indigo-600 text-gray-500 transition-colors">
            <i class="fas fa-calendar-days text-xs"></i> Ver días
          </button>` : ''}
        </div>

        <!-- Stats -->
        <div class="px-5 py-4">
          <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">

            <div class="tc-stat-box bg-gray-50">
              <p class="tc-stat-label">Días norm.</p>
              <p class="tc-stat-val text-gray-800">${e.dias_normales}</p>
            </div>

            <div class="tc-stat-box bg-slate-50">
              <p class="tc-stat-label text-slate-500">H. debidas</p>
              <p class="tc-stat-val text-slate-600">${e.horas_debidas}h</p>
              <p class="tc-stat-sub text-slate-400">${e.dias_normales}×7.33</p>
            </div>

            <div class="tc-stat-box bg-indigo-50">
              <p class="tc-stat-label text-indigo-500">H. normales</p>
              <p class="tc-stat-val text-indigo-700">${e.horas_normales}h</p>
            </div>

            ${statFest}
            ${statDom}

            <div class="tc-stat-box ${difColor}">
              <p class="tc-stat-label opacity-70">Diferencia</p>
              <p class="tc-stat-val">${difSign}${e.diferencia}h</p>
              <p class="tc-stat-sub opacity-60">${dif > 0 ? 'Extra' : dif < 0 ? 'Faltante' : 'Exacto'}</p>
            </div>

          </div>
          <p class="text-xs text-gray-400 mt-2.5 text-right">
            Total laborado: <strong class="text-gray-600">${totalH}h</strong>
          </p>
        </div>

        <!-- Tabla detalle colapsable -->
        ${tienedet ? `
        <div id="det-${idx}" class="hidden border-t border-gray-100">
          ${tablaDetalle(e)}
        </div>` : ''}
      </div>`;
  }

  function tablaDetalle(e) {
    const TIPO = {
      normal:  { row: '', badge: '<span class="text-xs text-gray-400">Normal</span>' },
      festivo: { row: 'bg-purple-50/50', badge: '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-purple-100 text-purple-700 rounded-full"><i class="fas fa-star text-[10px]"></i>Festivo</span>' },
      domingo: { row: 'bg-pink-50/50',   badge: '<span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-pink-100 text-pink-600 rounded-full">Domingo</span>' },
    };

    const totalH = (+e.horas_normales + +e.horas_festivos + +e.horas_domingos).toFixed(2);

    return `
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100 bg-gray-50/80">
              <th class="text-left px-5 py-3 font-medium">Fecha</th>
              <th class="text-left px-3 py-3 font-medium">Día</th>
              <th class="text-left px-3 py-3 font-medium">Tipo</th>
              <th class="text-left px-3 py-3 font-medium">Festivo</th>
              <th class="text-center px-3 py-3 font-medium">Entrada</th>
              <th class="text-center px-3 py-3 font-medium">Salida</th>
              <th class="text-right px-5 py-3 font-medium">Horas</th>
            </tr>
          </thead>
          <tbody>
            ${e.detalle.map(d => {
              const t = TIPO[d.tipo] || TIPO.normal;
              const horas = parseFloat(d.horas_netas);
              return `
                <tr class="border-b border-gray-50 hover:bg-gray-50/40 transition-colors ${t.row}">
                  <td class="px-5 py-2.5 font-mono text-gray-700">${TC.fmtFecha(d.fecha)}</td>
                  <td class="px-3 py-2.5 text-gray-500">${d.dia}</td>
                  <td class="px-3 py-2.5">${t.badge}</td>
                  <td class="px-3 py-2.5 text-xs text-gray-400">${d.nombre_festivo || ''}</td>
                  <td class="px-3 py-2.5 text-center font-mono ${d.hora_entrada ? 'text-gray-700' : 'text-gray-300'}">${TC.fmtHora(d.hora_entrada) || '—'}</td>
                  <td class="px-3 py-2.5 text-center font-mono ${d.hora_salida ? 'text-gray-700' : 'text-gray-300'}">${TC.fmtHora(d.hora_salida) || '—'}</td>
                  <td class="px-5 py-2.5 text-right font-semibold ${horas > 0 ? 'text-gray-800' : 'text-gray-300'}">${horas > 0 ? horas + 'h' : '—'}</td>
                </tr>`;
            }).join('')}
          </tbody>
          <tfoot>
            <tr class="border-t-2 border-gray-200 bg-gray-50 text-sm font-semibold">
              <td colspan="5" class="px-5 py-3 text-gray-400 text-xs font-normal uppercase tracking-wide">Totales del período</td>
              <td class="px-3 py-3 text-right text-indigo-600">${e.horas_normales}h normales</td>
              <td class="px-5 py-3 text-right text-gray-700">${totalH}h total</td>
            </tr>
          </tfoot>
        </table>
      </div>`;
  }

  function toggleDetalle(idx) {
    const det  = document.getElementById(`det-${idx}`);
    const btn  = document.getElementById(`btn-det-${idx}`);
    const open = !det.classList.toggle('hidden');
    btn.innerHTML = open
      ? '<i class="fas fa-chevron-up text-xs"></i> Cerrar días'
      : '<i class="fas fa-calendar-days text-xs"></i> Ver días';
    btn.classList.toggle('bg-indigo-50', open);
    btn.classList.toggle('border-indigo-200', open);
    btn.classList.toggle('text-indigo-600', open);
  }

  // ── Exportar CSV ──────────────────────────────────────────────
  function exportarCsv() {
    if (!_datos.length) { TC.toast('Primero calcule la preliquidación', 'warning'); return; }

    const desde = document.getElementById('p-desde')?.value || '';
    const hasta = document.getElementById('p-hasta')?.value || '';
    const sep   = ';';
    const lines = [
      `PRELIQUIDACIÓN DE NÓMINA${sep}Período: ${desde} al ${hasta}`,
    ];

    _datos.forEach(e => {
      lines.push('');
      lines.push(`${e.nombre} ${e.apellido}${sep}${e.cargo_nombre || ''}${sep}${e.sede_nombre || ''}`);
      lines.push(`Días norm.${sep}H. debidas${sep}H. normales${sep}H. festivos${sep}H. domingos${sep}Diferencia`);
      lines.push(`${e.dias_normales}${sep}${e.horas_debidas}${sep}${e.horas_normales}${sep}${e.horas_festivos}${sep}${e.horas_domingos}${sep}${e.diferencia}`);
      if (e.detalle.length) {
        lines.push('');
        lines.push(`Fecha${sep}Día${sep}Tipo${sep}Festivo${sep}Entrada${sep}Salida${sep}Horas`);
        e.detalle.forEach(d => {
          lines.push([
            d.fecha, d.dia, d.tipo.toUpperCase(),
            d.nombre_festivo || (d.es_festivo ? 'Sí' : 'No'),
            d.hora_entrada || '', d.hora_salida || '',
            d.horas_netas || 0,
          ].join(sep));
        });
      }
      lines.push('---');
    });

    const blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = `preliquidacion_${desde}_${hasta}.csv`;
    a.click();
    URL.revokeObjectURL(url);
  }

  return {
    calcular, exportarCsv,
    filtrarLista, toggleTodos, limpiarSeleccion, onCheckEmp,
    toggleDetalle,
  };

})();
