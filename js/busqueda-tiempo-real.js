/* ============================================
   BÚSQUEDA EN TIEMPO REAL
   ============================================ */

function inicializarBusquedaTiempoReal(inputId, tableId, columnas = []) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    input.addEventListener('input', function () {
        const filtro = this.value.toLowerCase().trim();
        const filas  = Array.from(tbody.querySelectorAll('tr:not(.sin-resultados-row)'));
        let visibles = 0;

        filas.forEach(fila => {
            const celdas   = fila.getElementsByTagName('td');
            let coincide   = false;

            if (filtro === '') {
                coincide = true;
            } else {
                const cols = columnas.length > 0 ? columnas : [...Array(celdas.length).keys()];
                for (const idx of cols) {
                    if (celdas[idx] && celdas[idx].textContent.toLowerCase().includes(filtro)) {
                        coincide = true;
                        break;
                    }
                }
            }

            fila.dataset.filtroVisible = coincide ? '1' : '0';
            if (coincide) visibles++;
        });

        actualizarContador(visibles, filas.length);
        mostrarMensajeSinResultados(tbody, visibles, filtro);

        // Dejar que la paginación aplique display
        if (typeof table._recalcularPaginacion === 'function') {
            table._recalcularPaginacion();
        } else {
            // Sin paginación: aplicar display directamente
            filas.forEach(tr => {
                tr.style.display = tr.dataset.filtroVisible === '0' ? 'none' : '';
            });
        }
    });

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });
}

function actualizarContador(visibles, total) {
    const contador = document.getElementById('contador-resultados');
    if (contador) {
        contador.textContent = `Mostrando ${visibles} de ${total} socios`;
    }
}

function mostrarMensajeSinResultados(tbody, visibles, filtro) {
    const anterior = tbody.querySelector('.sin-resultados-row');
    if (anterior) anterior.remove();

    if (visibles === 0 && filtro !== '') {
        const filas      = tbody.querySelectorAll('tr:not(.sin-resultados-row)');
        const numCols    = filas[0] ? filas[0].getElementsByTagName('td').length : 5;
        const fila       = document.createElement('tr');
        fila.className   = 'sin-resultados-row';
        fila.innerHTML   = `
            <td colspan="${numCols}" style="text-align:center;padding:var(--space-xl);color:var(--text-secondary);">
                <div style="font-size:3rem;margin-bottom:1rem;opacity:0.5;">🔍</div>
                <h3 style="color:var(--text-secondary);margin-bottom:0.5rem;">No se encontraron resultados</h3>
                <p style="color:var(--text-muted);font-size:0.9rem;">
                    No hay socios que coincidan con: <strong>"${filtro}"</strong>
                </p>
            </td>`;
        tbody.appendChild(fila);
    }
}

function inicializarBusquedaConFiltros(inputBusquedaId, selectEstadoId, tableId, columnas = []) {
    const inputBusqueda = document.getElementById(inputBusquedaId);
    const selectEstado  = document.getElementById(selectEstadoId);
    const table         = document.getElementById(tableId);
    if (!inputBusqueda || !table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    function filtrar() {
        const filtroBusqueda = inputBusqueda.value.toLowerCase().trim();
        const filtroEstado   = selectEstado ? selectEstado.value.toLowerCase() : '';
        const filas          = Array.from(tbody.querySelectorAll('tr:not(.sin-resultados-row)'));
        let visibles         = 0;

        filas.forEach(fila => {
            const celdas = fila.getElementsByTagName('td');
            let okBusqueda = false;
            let okEstado   = true;

            // Búsqueda de texto
            if (filtroBusqueda === '') {
                okBusqueda = true;
            } else {
                const cols = columnas.length > 0 ? columnas : [...Array(celdas.length).keys()];
                for (const idx of cols) {
                    if (celdas[idx] && celdas[idx].textContent.toLowerCase().includes(filtroBusqueda)) {
                        okBusqueda = true;
                        break;
                    }
                }
            }

            // Filtro de estado
            if (filtroEstado !== '') {
                const badge = fila.querySelector('.badge');
                okEstado = badge ? badge.textContent.toLowerCase().trim() === filtroEstado : false;
            }

            const visible = okBusqueda && okEstado;
            fila.dataset.filtroVisible = visible ? '1' : '0';
            if (visible) visibles++;
        });

        actualizarContador(visibles, filas.length);
        mostrarMensajeSinResultados(tbody, visibles, filtroBusqueda);

        // Dejar que la paginación aplique display
        if (typeof table._recalcularPaginacion === 'function') {
            table._recalcularPaginacion();
        } else {
            filas.forEach(tr => {
                tr.style.display = tr.dataset.filtroVisible === '0' ? 'none' : '';
            });
        }
    }

    inputBusqueda.addEventListener('input', filtrar);
    if (selectEstado) selectEstado.addEventListener('change', filtrar);

    inputBusqueda.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            this.value = '';
            if (selectEstado) selectEstado.value = '';
            filtrar();
        }
    });
}

window.inicializarBusquedaTiempoReal = inicializarBusquedaTiempoReal;
window.inicializarBusquedaConFiltros = inicializarBusquedaConFiltros;
console.log('✅ Búsqueda en tiempo real cargada correctamente');