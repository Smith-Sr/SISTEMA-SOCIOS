/* ============================================
   TOAST NOTIFICATIONS
   Mensajes flotantes modernos
   ============================================ */

// Crear contenedor de toasts al cargar
document.addEventListener('DOMContentLoaded', () => {
    if (!document.getElementById('toast-container')) {
        const container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
});

/**
 * Mostrar toast
 * @param {string} mensaje - Texto del toast
 * @param {string} tipo - 'success' | 'error' | 'warning' | 'info'
 * @param {number} duracion - Milisegundos (default 3500)
 */
function showToast(mensaje, tipo = 'info', duracion = 3500) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const iconos = {
        success: '✅',
        error:   '❌',
        warning: '⚠️',
        info:    'ℹ️'
    };

    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.innerHTML = `
        <span class="toast-icon">${iconos[tipo] || 'ℹ️'}</span>
        <span class="toast-mensaje">${mensaje}</span>
        <button class="toast-close" onclick="cerrarToast(this.parentElement)">✕</button>
        <div class="toast-progress"></div>
    `;

    container.appendChild(toast);

    // Animar entrada
    requestAnimationFrame(() => {
        toast.classList.add('visible');
    });

    // Barra de progreso
    const progress = toast.querySelector('.toast-progress');
    progress.style.transition = `width ${duracion}ms linear`;
    requestAnimationFrame(() => {
        progress.style.width = '0%';
    });

    // Auto cerrar
    const timer = setTimeout(() => cerrarToast(toast), duracion);

    // Pausar al hover
    toast.addEventListener('mouseenter', () => {
        clearTimeout(timer);
        progress.style.transition = 'none';
    });

    toast.addEventListener('mouseleave', () => {
        const tiempoRestante = 1500;
        progress.style.transition = `width ${tiempoRestante}ms linear`;
        progress.style.width = '0%';
        setTimeout(() => cerrarToast(toast), tiempoRestante);
    });

    return toast;
}

function cerrarToast(toast) {
    if (!toast) return;
    toast.classList.remove('visible');
    toast.classList.add('hiding');
    setTimeout(() => toast.remove(), 400);
}

// Shortcuts
const toast = {
    success: (msg, dur) => showToast(msg, 'success', dur),
    error:   (msg, dur) => showToast(msg, 'error', dur),
    warning: (msg, dur) => showToast(msg, 'warning', dur),
    info:    (msg, dur) => showToast(msg, 'info', dur),
};

// Exportar global
window.showToast = showToast;
window.toast = toast;