// config.js  — maneja parámetros, sedes, cargos, equipos y festivos
const Cfg = (() => {

  // ─── Detectar qué página estamos ─────────────────────────
  document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('tbody-params'))   cargarParams();
    if (document.getElementById('tbody-sedes'))    cargarSedes();
    if (document.getElementById('tbody-cargos'))   cargarCargos();
    if (document.getElementById('tbody-equipos'))  cargarEquipos();
    if (document.getElementById('tbody-festivos')) cargarFestivos(new Date().getFullYear());
  });

  // ══════════════════════════════════════════════════════════
  // PARÁMETROS
  // ══════════════════════════════════════════════════════════
  async function cargarParams() {
    const tbody = document.getElementById('tbody-params');
    const res   = await TC.get('/api/config.php?action=parametros_listar');
    const rows  = res.data || [];
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-sm text-gray-400">Sin parámetros</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(p => `
      <tr class="border-b border-gray-50">
        <td class="py-3 pr-4"><code class="text-xs text-indigo-700 font-mono">${p.clave}</code></td>
        <td class="py-3 pr-4 font-semibold">${p.valor}</td>
        <td class="py-3 pr-4 text-sm text-gray-500">${p.cargo_nombre || '<span class="italic">Global</span>'}</td>
        <td class="py-3 pr-4 text-sm text-gray-400">${p.descripcion || ''}</td>
        <td class="py-3">
          <button onclick="Cfg.abrirParam(${JSON.stringify(p).replace(/"/g,'&quot;')})"
            class="text-indigo-600 hover:underline text-xs mr-3"><i class="fas fa-pen"></i> Editar</button>
          <button onclick="Cfg.eliminarParam(${p.id})"
            class="text-red-500 hover:underline text-xs"><i class="fas fa-trash"></i></button>
        </td>
      </tr>`).join('');
  }

  function abrirParam(p = {}) {
    const optsCargos = (typeof CARGOS_CFG !== 'undefined' ? CARGOS_CFG : []).map(c =>
      `<option value="${c.id}" ${p.cargo_id == c.id ? 'selected' : ''}>${c.nombre}</option>`
    ).join('');
    TC.openModal(p.id ? 'Editar parámetro' : 'Nuevo parámetro', `
      <div class="space-y-3">
        <div><label class="tc-label">Clave</label>
          <input id="pm-clave" class="tc-input" value="${p.clave||''}" placeholder="tolerancia_entrada_antes"></div>
        <div><label class="tc-label">Valor</label>
          <input id="pm-valor" class="tc-input" value="${p.valor||''}" placeholder="10"></div>
        <div><label class="tc-label">Aplica a cargo (vacío = global)</label>
          <select id="pm-cargo" class="tc-input">
            <option value="">Global</option>${optsCargos}
          </select></div>
        <div><label class="tc-label">Descripción</label>
          <input id="pm-desc" class="tc-input" value="${p.descripcion||''}"></div>
      </div>`,
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Cfg.guardarParam()" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>`
    );
  }

  async function guardarParam() {
    const body = {
      clave:       document.getElementById('pm-clave').value.trim(),
      valor:       document.getElementById('pm-valor').value.trim(),
      cargo_id:    document.getElementById('pm-cargo').value || null,
      descripcion: document.getElementById('pm-desc').value,
    };
    if (!body.clave || body.valor === '') { TC.toast('Clave y valor requeridos', 'warning'); return; }
    const res = await TC.post('/api/config.php?action=parametros_guardar', body);
    TC.closeModal();
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) cargarParams();
  }

  async function eliminarParam(id) {
    TC.confirmar('¿Eliminar este parámetro?', async () => {
      const res = await TC.get(`/api/config.php?action=parametros_eliminar&id=${id}`);
      TC.toast(res.message, res.success ? 'success' : 'error');
      if (res.success) cargarParams();
    });
  }

  // ══════════════════════════════════════════════════════════
  // SEDES
  // ══════════════════════════════════════════════════════════
  let _sedes = [];
  async function cargarSedes() {
    const tbody = document.getElementById('tbody-sedes');
    const res   = await TC.get('/api/sedes.php?action=listar');
    _sedes = res.data || [];
    if (!_sedes.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="py-6 text-center text-sm text-gray-400">Sin sedes</td></tr>';
      return;
    }
    tbody.innerHTML = _sedes.map(s => `
      <tr class="border-b border-gray-50">
        <td class="py-3 pr-4 font-medium">${s.nombre}</td>
        <td class="py-3 pr-4 text-sm text-gray-500">${s.direccion || '—'}</td>
        <td class="py-3 pr-4 text-sm">${s.supervisor_nombre || '—'}</td>
        <td class="py-3">
          <button onclick="Cfg.abrirSede(${s.id})" class="text-indigo-600 hover:underline text-xs mr-3">
            <i class="fas fa-pen"></i> Editar</button>
          <button onclick="Cfg.eliminarSede(${s.id})" class="text-red-500 hover:underline text-xs">
            <i class="fas fa-trash"></i></button>
        </td>
      </tr>`).join('');
  }

  function abrirSede(id) {
    const s = id ? _sedes.find(x => x.id === id) : {};
    const optsSup = (typeof SUPERVISORES !== 'undefined' ? SUPERVISORES : []).map(u =>
      `<option value="${u.id}" ${s?.supervisor_id == u.id ? 'selected' : ''}>${u.nombre_completo}</option>`
    ).join('');
    TC.openModal(id ? 'Editar sede' : 'Nueva sede', `
      <div class="space-y-3">
        <div><label class="tc-label">Nombre</label>
          <input id="sd-nom" class="tc-input" value="${s?.nombre||''}"></div>
        <div><label class="tc-label">Dirección</label>
          <input id="sd-dir" class="tc-input" value="${s?.direccion||''}"></div>
        <div><label class="tc-label">Supervisor</label>
          <select id="sd-sup" class="tc-input">
            <option value="">Sin supervisor</option>${optsSup}
          </select></div>
      </div>`,
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Cfg.guardarSede(${id||'null'})" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>`
    );
  }

  async function guardarSede(id) {
    const body = {
      id:            id || undefined,
      nombre:        document.getElementById('sd-nom').value,
      direccion:     document.getElementById('sd-dir').value,
      supervisor_id: document.getElementById('sd-sup').value || null,
    };
    const action = id ? 'editar' : 'crear';
    const res = await TC.post(`/api/sedes.php?action=${action}`, body);
    TC.closeModal();
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) cargarSedes();
  }

  async function eliminarSede(id) {
    TC.confirmar('¿Desactivar esta sede?', async () => {
      const res = await TC.get(`/api/sedes.php?action=eliminar&id=${id}`);
      TC.toast(res.message, res.success ? 'success' : 'error');
      if (res.success) cargarSedes();
    });
  }

  // ══════════════════════════════════════════════════════════
  // CARGOS
  // ══════════════════════════════════════════════════════════
  let _cargos = [];
  async function cargarCargos() {
    const tbody = document.getElementById('tbody-cargos');
    const res   = await TC.get('/api/config.php?action=cargos_listar');
    _cargos = res.data || [];
    if (!_cargos.length) {
      tbody.innerHTML = '<tr><td colspan="4" class="py-6 text-center text-sm text-gray-400">Sin cargos</td></tr>';
      return;
    }
    tbody.innerHTML = _cargos.map(c => `
      <tr class="border-b border-gray-50">
        <td class="py-3 pr-4 font-medium">${c.nombre}</td>
        <td class="py-3 pr-4 text-sm text-gray-500">${c.descripcion||'—'}</td>
        <td class="py-3">
          <button onclick="Cfg.abrirCargo(${c.id})" class="text-indigo-600 hover:underline text-xs mr-3">
            <i class="fas fa-pen"></i> Editar</button>
        </td>
      </tr>`).join('');
  }

  function abrirCargo(id) {
    const c = id ? _cargos.find(x => x.id === id) : {};
    TC.openModal(id ? 'Editar cargo' : 'Nuevo cargo', `
      <div class="space-y-3">
        <div><label class="tc-label">Nombre del cargo</label>
          <input id="ca-nom" class="tc-input" value="${c?.nombre||''}"></div>
        <div><label class="tc-label">Descripción</label>
          <input id="ca-desc" class="tc-input" value="${c?.descripcion||''}"></div>
      </div>`,
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Cfg.guardarCargo(${id||'null'})" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>`
    );
  }

  async function guardarCargo(id) {
    const body = {
      id:          id || undefined,
      nombre:      document.getElementById('ca-nom').value,
      descripcion: document.getElementById('ca-desc').value,
    };
    const res = await TC.post('/api/config.php?action=cargos_guardar', body);
    TC.closeModal();
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) cargarCargos();
  }

  // ══════════════════════════════════════════════════════════
  // EQUIPOS AUTORIZADOS
  // ══════════════════════════════════════════════════════════
  let _equipos = [];
  async function cargarEquipos() {
    const tbody = document.getElementById('tbody-equipos');
    const res   = await TC.get('/api/config.php?action=equipos_listar');
    _equipos = res.data || [];
    if (!_equipos.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-sm text-gray-400">Sin equipos registrados — todos los cargos pueden marcar desde cualquier equipo</td></tr>';
      return;
    }
    tbody.innerHTML = _equipos.map(e => `
      <tr class="border-b border-gray-50">
        <td class="py-3 pr-4 font-medium">${e.cargo_nombre}</td>
        <td class="py-3 pr-4 text-sm text-gray-500">${e.sede_nombre || 'Todas las sedes'}</td>
        <td class="py-3 pr-4"><code class="text-xs font-mono text-green-700 bg-green-50 px-2 py-0.5 rounded">${e.nombre_equipo}</code></td>
        <td class="py-3 pr-4 text-sm text-gray-400">${e.descripcion||''}</td>
        <td class="py-3">
          <button onclick="Cfg.abrirEquipo(${e.id})" class="text-indigo-600 hover:underline text-xs mr-3">
            <i class="fas fa-pen"></i></button>
          <button onclick="Cfg.eliminarEquipo(${e.id})" class="text-red-500 hover:underline text-xs">
            <i class="fas fa-trash"></i></button>
        </td>
      </tr>`).join('');
  }

  function abrirEquipo(id) {
    const e = id ? _equipos.find(x => x.id === id) : {};
    const optsCargos = (typeof CARGOS_CFG !== 'undefined' ? CARGOS_CFG : []).map(c =>
      `<option value="${c.id}" ${e?.cargo_id == c.id ? 'selected' : ''}>${c.nombre}</option>`).join('');
    const optsSedes  = (typeof SEDES_CFG  !== 'undefined' ? SEDES_CFG  : []).map(s =>
      `<option value="${s.id}" ${e?.sede_id == s.id ? 'selected' : ''}>${s.nombre}</option>`).join('');
    TC.openModal(id ? 'Editar equipo' : 'Agregar equipo autorizado', `
      <div class="space-y-3">
        <div><label class="tc-label">Cargo</label>
          <select id="eq-cargo" class="tc-input"><option value="">Seleccione...</option>${optsCargos}</select></div>
        <div><label class="tc-label">Sede (vacío = aplica a todas)</label>
          <select id="eq-sede" class="tc-input"><option value="">Todas las sedes</option>${optsSedes}</select></div>
        <div><label class="tc-label">Nombre del equipo (hostname)</label>
          <input id="eq-nombre" class="tc-input" value="${e?.nombre_equipo||''}"
                 placeholder="Ej: PC-VENTAS-01"></div>
        <div><label class="tc-label">Descripción</label>
          <input id="eq-desc" class="tc-input" value="${e?.descripcion||''}"></div>
      </div>`,
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Cfg.guardarEquipo(${id||'null'})" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>`
    );
  }

  async function guardarEquipo(id) {
    const body = {
      id:           id || undefined,
      cargo_id:     document.getElementById('eq-cargo').value,
      sede_id:      document.getElementById('eq-sede').value || null,
      nombre_equipo:document.getElementById('eq-nombre').value,
      descripcion:  document.getElementById('eq-desc').value,
    };
    const action = id ? 'equipos_guardar' : 'equipos_guardar';
    const res = await TC.post(`/api/config.php?action=${action}`, body);
    TC.closeModal();
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) cargarEquipos();
  }

  async function eliminarEquipo(id) {
    TC.confirmar('¿Eliminar este equipo?', async () => {
      const res = await TC.get(`/api/config.php?action=equipos_eliminar&id=${id}`);
      TC.toast(res.message, res.success ? 'success' : 'error');
      if (res.success) cargarEquipos();
    });
  }

  // ══════════════════════════════════════════════════════════
  // FESTIVOS
  // ══════════════════════════════════════════════════════════
  let _festivos = [];
  async function cargarFestivos(anio) {
    const tbody = document.getElementById('tbody-festivos');
    tbody.innerHTML = '<tr><td colspan="5" class="py-6 text-center"><span class="tc-spinner"></span></td></tr>';
    const res = await TC.get(`/api/config.php?action=festivos_listar&anio=${anio}`);
    _festivos = res.data || [];

    const tiposBadge = {
      fijo:         'bg-red-100 text-red-700',
      trasladable:  'bg-amber-100 text-amber-700',
      semana_santa: 'bg-orange-100 text-orange-700',
      especial:     'bg-purple-100 text-purple-700',
    };
    const dias = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];

    tbody.innerHTML = _festivos.map(f => {
      const dow   = new Date(f.fecha + 'T12:00:00').getDay();
      const badge = tiposBadge[f.tipo] || 'bg-gray-100 text-gray-600';
      const label = f.tipo === 'semana_santa' ? 'Semana Santa' : f.tipo;
      const acciones = f.auto
        ? `<span class="text-xs text-gray-300 italic">Automático</span>`
        : `<button onclick="Cfg.eliminarFestivo(${f.id})"
                   class="text-red-500 hover:underline text-xs"
                   title="Eliminar">
             <i class="fas fa-trash"></i>
           </button>`;
      return `
        <tr class="border-b border-gray-50">
          <td class="py-3 pr-4 font-mono text-sm">${TC.fmtFecha(f.fecha)}</td>
          <td class="py-3 pr-4 text-gray-500 text-sm">${dias[dow]}</td>
          <td class="py-3 pr-4 font-medium text-sm">${f.nombre}</td>
          <td class="py-3 pr-4">
            <span class="px-2 py-0.5 rounded-full text-xs font-medium ${badge}">${label}</span>
          </td>
          <td class="py-3">${acciones}</td>
        </tr>`;
    }).join('') || `<tr><td colspan="5" class="py-6 text-center text-sm text-gray-400">Sin festivos</td></tr>`;
  }

  function abrirFestivo() {
    TC.openModal('Agregar festivo especial', `
      <div class="space-y-3">
        <p class="text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2">
          <i class="fas fa-circle-info mr-1"></i>
          Los festivos oficiales de Colombia se calculan automáticamente.<br>
          Aquí solo agregue fechas especiales de su empresa.
        </p>
        <div><label class="tc-label">Fecha</label>
          <input id="ft-fecha" type="date" class="tc-input"></div>
        <div><label class="tc-label">Nombre / Motivo</label>
          <input id="ft-nom" class="tc-input" placeholder="Ej: Día de la empresa"></div>
      </div>`,
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Cfg.guardarFestivo()" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>`
    );
  }

  async function guardarFestivo() {
    const body = {
      fecha:  document.getElementById('ft-fecha').value,
      nombre: document.getElementById('ft-nom').value,
    };
    if (!body.fecha || !body.nombre) { TC.toast('Fecha y nombre requeridos', 'warning'); return; }
    const res = await TC.post('/api/config.php?action=festivos_guardar', body);
    TC.closeModal();
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) cargarFestivos(document.getElementById('filtro-anio')?.value || new Date().getFullYear());
  }

  async function eliminarFestivo(id) {
    TC.confirmar('¿Eliminar este festivo especial?', async () => {
      const res = await TC.get(`/api/config.php?action=festivos_eliminar&id=${id}`);
      TC.toast(res.message, res.success ? 'success' : 'error');
      if (res.success) cargarFestivos(document.getElementById('filtro-anio')?.value || new Date().getFullYear());
    });
  }

  return {
    // params
    abrirParam, guardarParam, eliminarParam,
    // sedes
    abrirSede, guardarSede, eliminarSede,
    // cargos
    abrirCargo, guardarCargo,
    // equipos
    abrirEquipo, guardarEquipo, eliminarEquipo,
    // festivos
    cargarFestivos, abrirFestivo, guardarFestivo, eliminarFestivo,
  };
})();
