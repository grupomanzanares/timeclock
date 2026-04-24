// turnos.js
const Turnos = (() => {
  const DIAS = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
  let listaTurnos = [];
  let listaEmpleados = [];

  document.addEventListener('DOMContentLoaded', () => {
    cargarTurnos();
    cargarEmpleados().then(cargarAsignaciones);
    document.getElementById('filtro-empleado')?.addEventListener('change', cargarAsignaciones);
  });

  async function cargarTurnos() {
    // Siempre cargamos los datos (los necesita el modal de asignar aunque el rol sea supervisor)
    const res = await TC.get('/api/turnos.php?action=listar');
    listaTurnos = res.data || [];

    const tbody = document.getElementById('tbody-turnos');
    if (!tbody) return; // Supervisor no tiene esa tabla; solo detenemos el render

    if (!listaTurnos.length) {
      tbody.innerHTML = '<tr><td colspan="5" class="py-6 text-center text-sm text-gray-400">Sin turnos registrados</td></tr>';
      return;
    }
    tbody.innerHTML = listaTurnos.map(t => `
      <tr class="border-b border-gray-50">
        <td class="py-3 pr-4 font-medium">${t.nombre}</td>
        <td class="py-3 pr-4">${TC.fmtHora(t.hora_inicio)}</td>
        <td class="py-3 pr-4">${TC.fmtHora(t.hora_fin)}</td>
        <td class="py-3 pr-4">
          ${t.nocturno
            ? '<span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-700">Nocturno</span>'
            : '<span class="px-2 py-0.5 rounded-full text-xs bg-blue-100 text-blue-700">Diurno</span>'}
        </td>
        <td class="py-3">
          <button onclick="Turnos.abrirEditar(${t.id})" class="text-indigo-600 hover:underline text-xs mr-3">
            <i class="fas fa-pen"></i> Editar
          </button>
          <button onclick="Turnos.eliminar(${t.id})" class="text-red-500 hover:underline text-xs">
            <i class="fas fa-trash"></i>
          </button>
        </td>
      </tr>`).join('');
  }

  async function cargarEmpleados() {
    const res = await TC.get('/api/usuarios.php?action=listar');
    listaEmpleados = res.data || [];
    const sel = document.getElementById('filtro-empleado');
    if (!sel) return;
    listaEmpleados.forEach(u => {
      const opt = document.createElement('option');
      opt.value = u.id;
      opt.textContent = `${u.nombre} ${u.apellido}`;
      sel.appendChild(opt);
    });
  }

  async function cargarAsignaciones() {
    const tbody = document.getElementById('tbody-asignaciones');
    const uid   = document.getElementById('filtro-empleado')?.value || '';
    const url   = uid
      ? `/api/turnos.php?action=asignaciones&usuario_id=${uid}`
      : '/api/turnos.php?action=asignaciones';
    tbody.innerHTML = '<tr><td colspan="7" class="py-6 text-center"><span class="tc-spinner"></span></td></tr>';
    const res = await TC.get(url);
    if (!res.data?.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-6 text-center text-sm text-gray-400">Sin asignaciones</td></tr>';
      return;
    }
    tbody.innerHTML = res.data.map(a => {
      const dias = (a.dias_semana || '').split(',').map(Number);
      const pillsHtml = DIAS.map((d, i) =>
        `<span class="day-pill ${dias.includes(i) ? 'active' : 'inactive'}">${d}</span>`
      ).join('');
      return `
        <tr class="border-b border-gray-50">
          <td class="py-3 pr-4 font-medium">${a.usuario_nombre || '—'} ${a.usuario_apellido || ''}</td>
          <td class="py-3 pr-4">
            <p class="font-medium">${a.turno_nombre}</p>
            <p class="text-xs text-gray-400">${TC.fmtHora(a.hora_inicio)} — ${TC.fmtHora(a.hora_fin)}</p>
          </td>
          <td class="py-3 pr-4">${TC.fmtFecha(a.fecha_inicio)}</td>
          <td class="py-3 pr-4">${TC.fmtFecha(a.fecha_fin)}</td>
          <td class="py-3 pr-4"><div class="flex gap-0.5">${pillsHtml}</div></td>
          <td class="py-3 pr-4">
            ${a.aprobado
              ? '<span class="px-2 py-0.5 rounded-full text-xs bg-green-100 text-green-700">Aprobado</span>'
              : '<span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700">Pendiente</span>'}
          </td>
          <td class="py-3">
            ${!a.aprobado ? `
              <button onclick="Turnos.aprobarAsignacion(${a.id},1)"
                class="text-xs text-green-600 hover:underline mr-2">Aprobar</button>` : ''}
          </td>
        </tr>`;
    }).join('');
  }

  function _formTurno(t = {}) {
    return `
      <div class="space-y-3">
        <div><label class="tc-label">Nombre del turno</label>
          <input id="t-nombre" class="tc-input" value="${t.nombre||''}" placeholder="Ej: Mañana"></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="tc-label">Hora inicio</label>
            <input id="t-ini" type="time" class="tc-input" value="${t.hora_inicio?.slice(0,5)||''}"></div>
          <div><label class="tc-label">Hora fin</label>
            <input id="t-fin" type="time" class="tc-input" value="${t.hora_fin?.slice(0,5)||''}"></div>
        </div>
        <label class="flex items-center gap-2 text-sm cursor-pointer">
          <input id="t-noc" type="checkbox" ${t.nocturno?'checked':''} class="rounded">
          Turno nocturno (cruza medianoche)
        </label>
      </div>`;
  }

  function abrirCrear() {
    TC.openModal('Nuevo turno', _formTurno(),
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Turnos.guardarTurno(null)" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Crear</button>`
    );
  }

  function abrirEditar(id) {
    const t = listaTurnos.find(x => x.id === id);
    if (!t) return;
    TC.openModal('Editar turno', _formTurno(t),
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Turnos.guardarTurno(${id})" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>`
    );
  }

  async function guardarTurno(id) {
    const body = {
      id: id || undefined,
      nombre:      document.getElementById('t-nombre').value,
      hora_inicio: document.getElementById('t-ini').value + ':00',
      hora_fin:    document.getElementById('t-fin').value + ':00',
      nocturno:    document.getElementById('t-noc').checked ? 1 : 0,
    };
    const action = id ? 'editar' : 'crear';
    const res = await TC.post(`/api/turnos.php?action=${action}`, body);
    TC.closeModal();
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) cargarTurnos();
  }

  async function eliminar(id) {
    TC.confirmar('¿Desactivar este turno?', async () => {
      const res = await TC.get(`/api/turnos.php?action=eliminar&id=${id}`);
      TC.toast(res.message, res.success ? 'success' : 'error');
      if (res.success) cargarTurnos();
    });
  }

  function abrirAsignar() {
    const optsEmp = listaEmpleados.map(u =>
      `<option value="${u.id}">${u.nombre} ${u.apellido}</option>`).join('');
    const optsTurno = listaTurnos.map(t =>
      `<option value="${t.id}">${t.nombre} (${TC.fmtHora(t.hora_inicio)}–${TC.fmtHora(t.hora_fin)})</option>`).join('');
    const diasHtml = DIAS.map((d, i) => `
      <label class="flex items-center gap-1.5 text-sm cursor-pointer">
        <input type="checkbox" class="dia-check" value="${i}" ${i >= 1 && i <= 5 ? 'checked' : ''}> ${d}
      </label>`).join('');

    TC.openModal('Asignar turno', `
      <div class="space-y-3">
        <div><label class="tc-label">Empleado</label>
          <select id="as-emp" class="tc-input">${optsEmp}</select></div>
        <div><label class="tc-label">Turno</label>
          <select id="as-turno" class="tc-input">${optsTurno}</select></div>
        <div class="grid grid-cols-2 gap-3">
          <div><label class="tc-label">Desde</label>
            <input id="as-ini" type="date" class="tc-input" value="${new Date().toISOString().slice(0,10)}"></div>
          <div><label class="tc-label">Hasta</label>
            <input id="as-fin" type="date" class="tc-input"></div>
        </div>
        <div>
          <label class="tc-label">Días de la semana</label>
          <div class="flex flex-wrap gap-3 mt-1">${diasHtml}</div>
        </div>
        <div><label class="tc-label">Observación</label>
          <textarea id="as-obs" rows="2" class="tc-input resize-none"></textarea></div>
      </div>`,
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Turnos.confirmarAsignar()" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">Asignar</button>`
    );
  }

  async function confirmarAsignar() {
    const dias = [...document.querySelectorAll('.dia-check:checked')].map(c => parseInt(c.value));
    const body = {
      usuario_id:   parseInt(document.getElementById('as-emp').value),
      turno_id:     parseInt(document.getElementById('as-turno').value),
      fecha_inicio: document.getElementById('as-ini').value,
      fecha_fin:    document.getElementById('as-fin').value,
      dias_semana:  dias,
      observacion:  document.getElementById('as-obs').value,
    };
    if (!body.fecha_inicio || !body.fecha_fin) { TC.toast('Ingrese fechas', 'warning'); return; }
    if (!dias.length) { TC.toast('Seleccione al menos un día', 'warning'); return; }
    const res = await TC.post('/api/turnos.php?action=asignar', body);
    TC.closeModal();
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) cargarAsignaciones();
  }

  async function aprobarAsignacion(id, aprobado) {
    const res = await TC.post('/api/turnos.php?action=aprobar_asignacion', { id, aprobado });
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) cargarAsignaciones();
  }

  return { cargarTurnos, abrirCrear, abrirEditar, guardarTurno, eliminar,
           abrirAsignar, confirmarAsignar, aprobarAsignacion };
})();
