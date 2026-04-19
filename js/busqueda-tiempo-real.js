/* ============================================
   BÚSQUEDA EN TIEMPO REAL
   Filtra tablas mientras el usuario escribe
   ============================================ */

/**
 * Inicializa la búsqueda en tiempo real para una tabla
 * @param {string} inputId - ID del input de búsqueda
 * @param {string} tableId - ID de la tabla a filtrar
 * @param {Array} columnas - Índices de las columnas donde buscar (ej: [1, 2, 3] para DNI, Apellidos, Nombres)
 */
function inicializarBusquedaTiempoReal(inputId, tableId, columnas = []) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    
    if (!input || !table) {
        console.warn('No se encontró el input o la tabla para búsqueda en tiempo real');
        return;
    }
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        console.warn('No se encontró tbody en la tabla');
        return;
    }
    
    // Evento de búsqueda
    input.addEventListener('input', function() {
        const filtro = this.value.toLowerCase().trim();
        const filas = tbody.getElementsByTagName('tr');
        let contadorVisibles = 0;
        
        // Recorrer todas las filas
        for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];
            const celdas = fila.getElementsByTagName('td');
            let coincide = false;
            
            // Si no hay filtro, mostrar todo
            if (filtro === '') {
                fila.style.display = '';
                contadorVisibles++;
                continue;
            }
            
            // Buscar en las columnas especificadas
            if (columnas.length > 0) {
                for (let j = 0; j < columnas.length; j++) {
                    const colIndex = columnas[j];
                    if (celdas[colIndex]) {
                        const textoCelda = celdas[colIndex].textContent || celdas[colIndex].innerText;
                        if (textoCelda.toLowerCase().indexOf(filtro) > -1) {
                            coincide = true;
                            break;
                        }
                    }
                }
            } else {
                // Si no se especifican columnas, buscar en todas
                for (let j = 0; j < celdas.length; j++) {
                    const textoCelda = celdas[j].textContent || celdas[j].innerText;
                    if (textoCelda.toLowerCase().indexOf(filtro) > -1) {
                        coincide = true;
                        break;
                    }
                }
            }
            
            // Mostrar u ocultar fila
            if (coincide) {
                fila.style.display = '';
                contadorVisibles++;
            } else {
                fila.style.display = 'none';
            }
        }
        
        // Actualizar contador si existe
        actualizarContador(contadorVisibles, filas.length);
        
        // Mostrar mensaje si no hay resultados
        mostrarMensajeSinResultados(tbody, contadorVisibles, filtro);
    });
    
    // Limpiar búsqueda con ESC
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            this.dispatchEvent(new Event('input'));
        }
    });
}

/**
 * Actualiza el contador de resultados
 */
function actualizarContador(visibles, total) {
    const contador = document.getElementById('contador-resultados');
    if (contador) {
        contador.textContent = `Mostrando ${visibles} de ${total} socios`;
        
        // Efecto visual
        contador.style.transition = 'all 0.3s ease';
        contador.style.transform = 'scale(1.05)';
        setTimeout(() => {
            contador.style.transform = 'scale(1)';
        }, 200);
    }
}

/**
 * Muestra mensaje cuando no hay resultados
 */
function mostrarMensajeSinResultados(tbody, contadorVisibles, filtro) {
    // Remover mensaje anterior si existe
    const mensajeAnterior = tbody.querySelector('.sin-resultados-row');
    if (mensajeAnterior) {
        mensajeAnterior.remove();
    }
    
    // Si no hay resultados y hay filtro activo
    if (contadorVisibles === 0 && filtro !== '') {
        const filas = tbody.getElementsByTagName('tr');
        const numColumnas = filas[0] ? filas[0].getElementsByTagName('td').length : 5;
        
        const filaMensaje = document.createElement('tr');
        filaMensaje.className = 'sin-resultados-row';
        filaMensaje.innerHTML = `
            <td colspan="${numColumnas}" style="text-align: center; padding: var(--space-xl); color: var(--text-secondary);">
                <div style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;">🔍</div>
                <h3 style="color: var(--text-secondary); margin-bottom: 0.5rem;">No se encontraron resultados</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">
                    No hay socios que coincidan con: <strong>"${filtro}"</strong>
                </p>
            </td>
        `;
        tbody.appendChild(filaMensaje);
    }
}

/**
 * Combina búsqueda en tiempo real con filtro de estado
 */
function inicializarBusquedaConFiltros(inputBusquedaId, selectEstadoId, tableId, columnas = []) {
    const inputBusqueda = document.getElementById(inputBusquedaId);
    const selectEstado = document.getElementById(selectEstadoId);
    const table = document.getElementById(tableId);
    
    if (!inputBusqueda || !table) return;
    
    const tbody = table.querySelector('tbody');
    if (!tbody) return;
    
    function filtrar() {
        const filtroBusqueda = inputBusqueda.value.toLowerCase().trim();
        const filtroEstado = selectEstado ? selectEstado.value.toLowerCase() : '';
        const filas = tbody.getElementsByTagName('tr');
        let contadorVisibles = 0;
        
        for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];
            const celdas = fila.getElementsByTagName('td');
            let coincideBusqueda = false;
            let coincideEstado = true;
            
            // Filtro de búsqueda
            if (filtroBusqueda === '') {
                coincideBusqueda = true;
            } else {
                for (let j = 0; j < columnas.length; j++) {
                    const colIndex = columnas[j];
                    if (celdas[colIndex]) {
                        const textoCelda = celdas[colIndex].textContent || celdas[colIndex].innerText;
                        if (textoCelda.toLowerCase().indexOf(filtroBusqueda) > -1) {
                            coincideBusqueda = true;
                            break;
                        }
                    }
                }
            }
            
            // Filtro de estado
            if (filtroEstado !== '' && selectEstado) {
                const badgeEstado = fila.querySelector('.badge');
                if (badgeEstado) {
                    const estadoFila = badgeEstado.textContent.toLowerCase().trim();
                    coincideEstado = (estadoFila === filtroEstado);
                }
            }
            
            // Mostrar solo si coincide con ambos filtros
            if (coincideBusqueda && coincideEstado) {
                fila.style.display = '';
                contadorVisibles++;
            } else {
                fila.style.display = 'none';
            }
        }
        
        actualizarContador(contadorVisibles, filas.length);
        mostrarMensajeSinResultados(tbody, contadorVisibles, filtroBusqueda);
    }
    
    // Eventos
    inputBusqueda.addEventListener('input', filtrar);
    if (selectEstado) {
        selectEstado.addEventListener('change', filtrar);
    }
    
    // ESC para limpiar
    inputBusqueda.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            this.value = '';
            if (selectEstado) selectEstado.value = '';
            filtrar();
        }
    });
}

/**
 * Highlight del texto buscado (opcional)
 */
function highlightTexto(texto, busqueda) {
    if (!busqueda) return texto;
    
    const regex = new RegExp(`(${busqueda})`, 'gi');
    return texto.replace(regex, '<mark style="background: rgba(0, 217, 255, 0.3); padding: 2px 4px; border-radius: 3px;">$1</mark>');
}

// Exportar para uso global
window.inicializarBusquedaTiempoReal = inicializarBusquedaTiempoReal;
window.inicializarBusquedaConFiltros = inicializarBusquedaConFiltros;

console.log('✅ Búsqueda en tiempo real cargada correctamente');