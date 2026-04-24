// empleados.js
const Empleados = (() => {
  let todos = [];

  document.addEventListener('DOMContentLoaded', cargar);

  async function cargar() {
    const res = await TC.get('/api/usuarios.php?action=listar');
    todos = res.data || [];
    render(todos);
  }

  function render(lista) {
    const tbody = document.getElementById('tbody-empleados');
    if (!lista.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-sm text-gray-400">Sin empleados registrados</td></tr>';
      return;
    }
    const roles = { admin:'bg-purple-100 text-purple-700', supervisor:'bg-blue-100 text-blue-700', empleado:'bg-green-100 text-green-700' };
    tbody.innerHTML = lista.map(u => `
      <tr class="border-b border-gray-50">
        <td class="py-3 pr-4">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-semibold flex-shrink-0">
              ${(u.nombre[0]||'').toUpperCase()}
            </div>
            <div>
              <p class="font-medium text-gray-900">${u.nombre} ${u.apellido}</p>
              <p class="text-xs text-gray-400">${u.email||''}</p>
            </div>
          </div>
        </td>
        <td class="py-3 pr-4 text-sm">${u.cedula||'—'}</td>
        <td class="py-3 pr-4 text-sm">${u.cargo_nombre||'—'}</td>
        <td class="py-3 pr-4 text-sm">${u.sede_nombre||'—'}</td>
        <td class="py-3 pr-4">
          <span class="px-2 py-0.5 rounded-full text-xs font-medium ${roles[u.rol]||''}">
            ${u.rol}
          </span>
        </td>
        <td class="py-3 pr-4 text-xs text-gray-400">
          ${u.equipo_permitido || '<span class="italic">Cualquier equipo</span>'}
        </td>
        <td class="py-3">
          <button onclick="Empleados.abrirEditar(${u.id})" class="text-indigo-600 hover:underline text-xs mr-3">
            <i class="fas fa-pen"></i> Editar
          </button>
          ${APP.rol === 'admin' ? `
          <button onclick="Empleados.eliminar(${u.id})" class="text-red-500 hover:underline text-xs">
            <i class="fas fa-ban"></i>
          </button>` : ''}
        </td>
      </tr>`).join('');
  }

  function filtrar(q) {
    const lq = q.toLowerCase();
    render(todos.filter(u =>
      `${u.nombre} ${u.apellido} ${u.cedula||''} ${u.email||''}`.toLowerCase().includes(lq)
    ));
  }

  function _opsCargos()  { return (window.CARGOS||[]).map(c => `<option value="${c.id}">${c.nombre}</option>`).join(''); }
  function _opsSedes()   { return (window.SEDES||[]).map(s => `<option value="${s.id}">${s.nombre}</option>`).join(''); }
  function _formHtml(u = {}) {
    return `<div class="space-y-3">
      <div class="grid grid-cols-2 gap-3">
        <div><label class="tc-label">Nombre</label>
          <input id="em-nom" class="tc-input" value="${u.nombre||''}"></div>
        <div><label class="tc-label">Apellido</label>
          <input id="em-ape" class="tc-input" value="${u.apellido||''}"></div>
      </div>
      <div><label class="tc-label">Cédula</label>
        <input id="em-ced" class="tc-input" value="${u.cedula||''}"></div>
      <div><label class="tc-label">Email <span class="text-gray-400 font-normal">(opcional)</span></label>
        <input id="em-email" type="email" class="tc-input" value="${u.email||''}" placeholder="correo@empresa.com"></div>
      <div><label class="tc-label">Contraseña ${u.id ? '(dejar en blanco para no cambiar)' : ''}</label>
        <input id="em-pass" type="password" class="tc-input" placeholder="Mínimo 8 caracteres"></div>
      <div class="grid grid-cols-2 gap-3">
        <div><label class="tc-label">Cargo</label>
          <select id="em-cargo" class="tc-input">
            <option value="">Sin cargo</option>${_opsCargos()}
          </select></div>
        <div><label class="tc-label">Sede</label>
          <select id="em-sede" class="tc-input">
            <option value="">Sin sede</option>${_opsSedes()}
          </select></div>
      </div>
      <div><label class="tc-label">Rol</label>
        <select id="em-rol" class="tc-input">
          <option value="empleado" ${u.rol==='empleado'?'selected':''}>Empleado</option>
          <option value="supervisor" ${u.rol==='supervisor'?'selected':''}>Supervisor</option>
          ${APP.rol==='admin'?`<option value="admin" ${u.rol==='admin'?'selected':''}>Admin</option>`:''}
        </select></div>
      <div><label class="tc-label">Equipo permitido (hostname, dejar vacío = cualquiera)</label>
        <input id="em-equipo" class="tc-input" value="${u.equipo_permitido||''}" placeholder="Ej: PC-VENTAS-01"></div>
    </div>`;
  }

  function _setSelects(u) {
    if (u.cargo_id) document.getElementById('em-cargo').value = u.cargo_id;
    if (u.sede_id)  document.getElementById('em-sede').value  = u.sede_id;
  }

  function abrirCrear() {
    TC.openModal('Nuevo empleado', _formHtml(),
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Empleados.guardar(null)" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Crear</button>`
    );
  }

  function abrirEditar(id) {
    const u = todos.find(x => x.id === id);
    if (!u) return;
    TC.openModal('Editar empleado', _formHtml(u),
      `<button onclick="TC.closeModal()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg">Cancelar</button>
       <button onclick="Empleados.guardar(${id})" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Guardar</button>`
    );
    _setSelects(u);
  }

  async function guardar(id) {
    const body = {
      id:               id || undefined,
      nombre:           document.getElementById('em-nom').value,
      apellido:         document.getElementById('em-ape').value,
      cedula:           document.getElementById('em-ced').value,
      email:            document.getElementById('em-email').value,
      password:         document.getElementById('em-pass').value,
      cargo_id:         document.getElementById('em-cargo').value || null,
      sede_id:          document.getElementById('em-sede').value  || null,
      rol:              document.getElementById('em-rol').value,
      equipo_permitido: document.getElementById('em-equipo').value || null,
    };
    const action = id ? 'editar' : 'crear';
    const res = await TC.post(`/api/usuarios.php?action=${action}`, body);
    TC.closeModal();
    TC.toast(res.message, res.success ? 'success' : 'error');
    if (res.success) cargar();
  }

  async function eliminar(id) {
    TC.confirmar('¿Desactivar este empleado?', async () => {
      const res = await TC.get(`/api/usuarios.php?action=eliminar&id=${id}`);
      TC.toast(res.message, res.success ? 'success' : 'error');
      if (res.success) cargar();
    });
  }

  return { abrirCrear, abrirEditar, guardar, eliminar, filtrar };
})();
