/* ============================================
   PAGINACIÓN DE TABLAS
   Uso: inicializarPaginacion('tabla-socios', 10)
   ============================================ */

function inicializarPaginacion(tableId, porPaginaDefault = 10) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const tbody = table.querySelector('tbody');
  if (!tbody) return;

  const tableContainer = table.closest('.table-container') || table.parentElement;

  let paginaActual = 1;
  let porPagina    = porPaginaDefault;

  function getFilasFiltradas() {
    return Array.from(tbody.querySelectorAll('tr:not(.sin-resultados-row)'))
                .filter(tr => tr.dataset.filtroVisible !== '0');
  }

  function renderPagina(pagina) {
    const filtradas    = getFilasFiltradas();
    const totalFilas   = filtradas.length;
    const totalPaginas = Math.max(1, Math.ceil(totalFilas / porPagina));

    paginaActual = Math.min(Math.max(1, pagina), totalPaginas);

    const inicio = (paginaActual - 1) * porPagina;
    const fin    = inicio + porPagina;

    Array.from(tbody.querySelectorAll('tr:not(.sin-resultados-row)')).forEach(tr => {
      tr.style.display = 'none';
    });

    filtradas.forEach((tr, i) => {
      if (i >= inicio && i < fin) tr.style.display = '';
    });

    renderControles(totalPaginas, totalFilas);
  }

  function renderControles(totalPaginas, totalFilas) {
    let ctrl = document.getElementById('paginacion-' + tableId);

    if (!ctrl) {
      ctrl = document.createElement('div');
      ctrl.id        = 'paginacion-' + tableId;
      ctrl.className = 'paginacion-wrapper';
      tableContainer.insertAdjacentElement('afterend', ctrl);
    }

    const esPrimera = paginaActual === 1;
    const esUltima  = paginaActual === totalPaginas;
    const inicio    = totalFilas === 0 ? 0 : (paginaActual - 1) * porPagina + 1;
    const fin       = Math.min(paginaActual * porPagina, totalFilas);

    const opciones = [10, 25, 50, 100]
      .map(n => `<option value="${n}" ${n === porPagina ? 'selected' : ''}>${n}</option>`)
      .join('');

    ctrl.innerHTML = `
      <div class="pag-row">
        <div class="pag-left">
          <span class="pag-label">Mostrar</span>
          <select class="pag-select" id="pag-select-${tableId}">${opciones}</select>
        </div>
        <div class="pag-info">
          ${inicio}–${fin} <span class="pag-de">de</span> ${totalFilas}
        </div>
        <div class="pag-botones">
          <button class="pag-btn" data-pagina="1"                  ${esPrimera ? 'disabled' : ''}>«</button>
          <button class="pag-btn" data-pagina="${paginaActual - 1}" ${esPrimera ? 'disabled' : ''}>‹</button>
          <span class="pag-current">${paginaActual} / ${totalPaginas}</span>
          <button class="pag-btn" data-pagina="${paginaActual + 1}" ${esUltima  ? 'disabled' : ''}>›</button>
          <button class="pag-btn" data-pagina="${totalPaginas}"     ${esUltima  ? 'disabled' : ''}>»</button>
        </div>
      </div>
    `;

    ctrl.querySelector('#pag-select-' + tableId)?.addEventListener('change', function () {
      porPagina = parseInt(this.value);
      renderPagina(1);
    });

    ctrl.querySelectorAll('.pag-btn[data-pagina]').forEach(btn => {
      btn.addEventListener('click', () => {
        const p = parseInt(btn.dataset.pagina);
        if (!isNaN(p)) renderPagina(p);
      });
    });
  }

  table._recalcularPaginacion = () => renderPagina(1);

  Array.from(tbody.querySelectorAll('tr:not(.sin-resultados-row)')).forEach(tr => {
    tr.dataset.filtroVisible = '1';
  });

  renderPagina(1);
}

(function inyectarEstilos() {
  if (document.getElementById('paginacion-styles')) return;
  const s = document.createElement('style');
  s.id = 'paginacion-styles';
  s.textContent = `
    .paginacion-wrapper { padding: 0.75rem 0.5rem 0.25rem; }
    .pag-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: nowrap;
    }
    .pag-left {
      display: flex;
      align-items: center;
      gap: 0.4rem;
      flex-shrink: 0;
    }
    .pag-label {
      font-size: 0.8rem;
      color: var(--text-secondary, #A8B3CF);
      white-space: nowrap;
    }
    .pag-select {
      padding: 0.3rem 0.5rem;
      background: rgba(0, 217, 255, 0.08);
      border: 1px solid rgba(0, 217, 255, 0.2);
      border-radius: 8px;
      color: var(--text-primary, #fff);
      font-size: 0.82rem;
      font-family: inherit;
      cursor: pointer;
      max-width: 70px;
    }
    .pag-select:focus { outline: none; border-color: var(--primary, #00D9FF); }
    .pag-select option { background: #151829; color: #fff; }
    .pag-info {
      font-size: 0.8rem;
      color: var(--text-secondary, #A8B3CF);
      white-space: nowrap;
      flex: 1;
      text-align: center;
    }
    .pag-de { opacity: 0.6; }
    .pag-botones {
      display: flex;
      align-items: center;
      gap: 0.25rem;
      flex-shrink: 0;
    }
    .pag-current {
      font-size: 0.8rem;
      color: var(--text-secondary, #A8B3CF);
      white-space: nowrap;
      padding: 0 0.25rem;
    }
    .pag-btn {
      width: 32px;
      height: 32px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(0, 217, 255, 0.08);
      border: 1px solid rgba(0, 217, 255, 0.2);
      border-radius: 8px;
      color: var(--text-primary, #fff);
      font-size: 0.9rem;
      cursor: pointer;
      transition: all 0.2s ease;
      font-family: inherit;
      flex-shrink: 0;
    }
    .pag-btn:hover:not(:disabled) {
      background: rgba(0, 217, 255, 0.2);
      border-color: var(--primary, #00D9FF);
      color: var(--primary, #00D9FF);
    }
    .pag-btn:disabled { opacity: 0.25; cursor: not-allowed; }
  `;
  document.head.appendChild(s);
})();

window.inicializarPaginacion = inicializarPaginacion;
console.log('✅ Paginación cargada');