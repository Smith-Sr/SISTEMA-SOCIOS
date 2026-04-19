/* ============================================
   MENU.JS - Funcionalidad del menú flotante
   Sistema de navegación tipo app móvil
   ============================================ */

/**
 * Toggle del menú flotante
 * Abre/cierra el menú lateral con animación
 */
function toggleMenu() {
    const menu = document.querySelector('.floating-menu');
    const overlay = document.querySelector('.menu-overlay');
    const btn = document.querySelector('.menu-btn');
    
    if (menu) menu.classList.toggle('active');
    if (overlay) overlay.classList.toggle('active');
    if (btn) btn.classList.toggle('active');
}

/**
 * Cerrar menú al hacer click en un item (solo en móvil)
 */
document.addEventListener('DOMContentLoaded', () => {
    const menuItems = document.querySelectorAll('.menu-item');
    
    menuItems.forEach(item => {
        item.addEventListener('click', () => {
            // Solo cerrar en móvil/tablet
            if (window.innerWidth <= 1024) {
                toggleMenu();
            }
        });
    });
});

/**
 * Cerrar menú con tecla ESC
 */
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const menu = document.querySelector('.floating-menu');
        if (menu && menu.classList.contains('active')) {
            toggleMenu();
        }
    }
});

/**
 * Prevenir scroll del body cuando el menú está abierto
 */
document.addEventListener('DOMContentLoaded', () => {
    const menu = document.querySelector('.floating-menu');
    
    if (menu) {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                if (mutation.attributeName === 'class') {
                    const isActive = menu.classList.contains('active');
                    document.body.style.overflow = isActive ? 'hidden' : '';
                }
            });
        });
        
        observer.observe(menu, { attributes: true });
    }
});

/**
 * Animación de fade-in al cargar la página
 */
window.addEventListener('load', () => {
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.5s ease';
        document.body.style.opacity = '1';
    }, 100);
});

/**
 * Console log profesional
 */
console.log(
    '%c🏢 Sistema de Gestión de Socios %c v2.0',
    'background: linear-gradient(135deg, #00D9FF, #0099CC); color: white; padding: 10px 20px; border-radius: 5px; font-weight: bold; font-size: 16px;',
    'background: #151829; color: #00D9FF; padding: 10px 20px; border-radius: 5px; font-size: 14px;'
);

console.log(
    '%cNuevo sistema de navegación flotante activo ✨',
    'color: #00D9FF; font-size: 12px; font-style: italic;'
);