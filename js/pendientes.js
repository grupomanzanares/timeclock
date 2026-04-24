// pendientes.js
const Pend = (() => {

  let _filas      = [];   // datos cargados actualmente
  let _filtros    = {};   // filtros activos
  let _seleccion  = new Set();

  // ── Inicializar ─────────────────────────────────────────────
  document.addEventListener('DOMContentLoaded', () => filtrar());

  // ── Leer filtros del DOM ─────────────────────────────────────
  function leerFiltros() {
    return {
      usuario_id:  document.getElementById('f-empleado').value || null,
      cargo_id:    document.getElementById('f-cargo').value    || null,
      fecha_desde: document.getElementById('f-desde').value    || null,
      fecha_hasta: document.getElementById('f-hasta').value    || null,
    };
  }

  // ── Filtrar / cargar ─────────────────────────────────────────
  async function filtrar() {
    _filtros   = leerFiltros();
    _seleccion = new Set();

    const tbody = document.getElementById('tbody-pendientes');
    tbody.innerHTML = `<tr><td colspan="9" class="py-8 text-center"><span class="tc-spinner"></span></td></tr>`;

    const qs  = new URLSearchParams(Object.fromEntries(
      Object.entries(_filtros).filter(([, v]) => v)
    )).toString();
    const res = await TC.get('/api/marcaciones.php?action=pendientes' + (qs ? '&' + qs : ''));

    if (!res.success) {
      tbody.innerHTML = `<tr><td colspan="9" class="py-6 text-center text-red-500 text-sm">${res.message}</td></tr>`;
      actualizarBarra();
      return;
    }

    _filas = res.data || [];
    renderTabla();
    actualizarBarra();
  }

  function limpiar() {
    document.getElementById('f-empleado').value = '';
    document.getElementById('f-cargo').value    = '';
    document.getElementById('f-desde').value    = '';
    document.getElementById('f-hasta').value    = '';
    filtrar();
  }

  // ── Render tabla ─────────────────────────────────────────────
  function renderTabla() {
    const tbody = document.getElementById('tbody-pendientes');

    if (!_filas.length) {
      tbody.innerHTML = `
        <tr><td colspan="9" class="py-8 text-center text-sm text-gray-400">
          <i class="fas fa-circle-check text-green-400 mr-2"></i>
          No hay marcaciones pendientes con los filtros actuales
        </td></tr>`;
      return;
    }

    tbody.innerHTML = _filas.map(m => `
      <tr class="border-b border-gray-50 hover:bg-gray-50/50 transition-colors" id="row-${m.id}">
        <td class="py-3 pr-3">
          <input type="checkbox" class="chk-fila w-4 h-4 rounded border-gray-300 text-indigo-600 cursor-pointer"
                 data-id="${m.id}" onchange="Pend.toggleFila(${m.id}, this.checked)">
        </td>
        <td class="py-3 pr-4">
          <p class="font-medium text-gray-900">${m.nombre} ${m.apellido}</p>
        </td>
        <td class="py-3 pr-4 text-gray-500 text-sm">${m.cargo_nombre || '—'}</td>
        <td class="py-3 pr-4 text-gray-500 text-sm">${m.sede_nombre || '—'}</td>
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
            <button onclick="Pend.aprobarUno(${m.id})"
              class="px-2.5 py-1 text-xs font-medium bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors">
              <i class="fas fa-check mr-1"></i>Aprobar
            </button>
            <button onclick="Pend.rechazarUno(${m.id})"
              class="px-2.5 py-1 text-xs font-medium bg-red-100 text-red-600 rounded-lg hover:bg-red-200 transition-colors">
              <i class="fas fa-xmark mr-1"></i>Rechazar
            </button>
          </div>
          ${m.observacion ? `<p class="text-xs text-gray-400 mt-1 max-w-xs truncate">${m.observacion}</p>` : ''}
        </td>
      </tr>`).join('');
  }

  // ── Barra de acciones masivas ─────────────────────────────────
  function actualizarBarra() {
    const barra  = document.getElementById('barra-masiva');
    const total  = _filas.length;
    const nSel   = _seleccion.size;

    // Mostrar barra solo si hay filas
    barra.classList.toggle('hidden', total === 0);

    // Totales en botones "todas del filtro"
    document.getElementById('cnt-total').textContent  = total;
    document.getElementById('cnt-total2').textContent = total;

    // Badge en el encabezado
    const badge = document.getElementById('badge-total');
    if (total > 0) {
      badge.textContent = total + ' pendiente' + (total !== 1 ? 's' : '');
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }

    // Etiqueta y botones de selección
    const lblSel   = document.getElementById('lbl-seleccionadas');
    const btnsSel  = document.getElementById('btns-seleccion');
    const chkTodos = document.getElementById('chk-todos');

    if (nSel > 0) {
      lblSel.style.display  = '';
      btnsSel.classList.remove('hidden');
      document.getElementById('cnt-seleccionadas').textContent = nSel;
      chkTodos.indeterminate = nSel < total;
      chkTodos.checked       = nSel === total;
    } else {
      lblSel.style.display = 'none';
      btnsSel.classList.add('hidden');
      chkTodos.indeterminate = false;
      chkTodos.checked       = false;
    }
  }

  // ── Selección ─────────────────────────────────────────────────
  function toggleFila(id, checked) {
    checked ? _seleccion.add(id) : _seleccion.delete(id);
    actualizarBarra();
  }

  function toggleTodos(checked) {
    _seleccion = checked ? new Set(_filas.map(f => f.id)) : new Set();
    document.querySelectorAll('.chk-fila').forEach(chk => {
      chk.checked = checked;
    });
    actualizarBarra();
  }

  // ── Aprobar / Rechazar individual ─────────────────────────────
  async function aprobarUno(id, decision = 'aprobado', observacion = '') {
    const res = await TC.post('/api/marcaciones.php?action=aprobar', { id, decision, observacion });
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) quitarFila(id);
  }

  function rechazarUno(id) {
    _abrirModalObservacion('Rechazar marcación', 'Motivo del rechazo:', async obs => {
      await aprobarUno(id, 'rechazado', obs);
    });
  }

  // ── Aprobar / Rechazar seleccionadas ──────────────────────────
  async function aprobarSeleccion() {
    if (!_seleccion.size) return;
    await _masivo([..._seleccion], 'aprobado');
  }

  function rechazarSeleccion() {
    if (!_seleccion.size) return;
    _abrirModalObservacion('Rechazar seleccionadas', 'Motivo del rechazo (opcional):', async obs => {
      await _masivo([..._seleccion], 'rechazado', obs);
    });
  }

  // ── Aprobar / Rechazar todas del filtro ───────────────────────
  function aprobarTodas() {
    const n = _filas.length;
    if (!n) return;
    TC.confirmar(
      `¿Aprobar las <strong>${n}</strong> marcación(es) pendientes del filtro actual?`,
      async () => { await _masivo([], 'aprobado'); }
    );
  }

  function rechazarTodas() {
    const n = _filas.length;
    if (!n) return;
    _abrirModalObservacion(
      `Rechazar las ${n} marcación(es) del filtro`,
      'Motivo del rechazo (opcional):',
      async obs => { await _masivo([], 'rechazado', obs); }
    );
  }

  // ── Lógica común de aprobación masiva ─────────────────────────
  async function _masivo(ids, decision, observacion = '') {
    const body = { ids, decision, observacion };
    // Si no hay IDs explícitos, enviar filtros para que la API los use
    if (!ids.length) {
      Object.assign(body, _filtros);
    }
    const res = await TC.post('/api/marcaciones.php?action=aprobar_masivo', body);
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) {
      if (ids.length) {
        ids.forEach(id => quitarFila(id));
        _seleccion = new Set();
        actualizarBarra();
      } else {
        // Recarga completa para reflejar el estado real
        filtrar();
      }
    }
  }

  // ── Helpers ───────────────────────────────────────────────────
  function quitarFila(id) {
    const row = document.getElementById(`row-${id}`);
    if (row) {
      row.classList.add('opacity-0', 'transition-opacity', 'duration-300');
      setTimeout(() => {
        row.remove();
        _filas = _filas.filter(f => f.id !== id);
        _seleccion.delete(id);
        actualizarBarra();
      }, 300);
    }
  }

  function _abrirModalObservacion(titulo, label, onConfirm) {
    TC.openModal(titulo,
      `<div class="space-y-3">
        <p class="text-sm text-gray-600">${label}</p>
        <textarea id="obs-masiva" rows="3" placeholder="Observación..." class="tc-input resize-none"></textarea>
      </div>`,
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50">Cancelar</button>
       <button id="btn-confirmar-masivo" class="px-4 py-2 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">Confirmar</button>`
    );
    document.getElementById('btn-confirmar-masivo').onclick = () => {
      const obs = document.getElementById('obs-masiva').value;
      TC.closeModal();
      onConfirm(obs);
    };
  }

  return { filtrar, limpiar, toggleFila, toggleTodos, aprobarUno, rechazarUno, aprobarSeleccion, rechazarSeleccion, aprobarTodas, rechazarTodas };

})();
