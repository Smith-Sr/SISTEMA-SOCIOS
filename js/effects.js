/* ============================================
   EFECTOS Y ANIMACIONES
   ============================================ */

// ===== SIDEBAR MÓVIL (HAMBURGER MENU) =====
function toggleSidebarMobile() {
  const sidebar   = document.querySelector('.sidebar');
  const overlay   = document.querySelector('.sidebar-overlay');
  const hamburger = document.querySelector('.hamburger-menu');

  if (sidebar)   sidebar.classList.toggle('active');
  if (overlay)   overlay.classList.toggle('active');
  if (hamburger) hamburger.classList.toggle('active');
}

// ===== SIDEBAR COLAPSAR/EXPANDIR (DESKTOP) =====
function toggleSidebarCollapse() {
  const sidebar     = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');
  if (!sidebar) return;

  sidebar.classList.toggle('collapsed');
  const isCollapsed = sidebar.classList.contains('collapsed');

  // Ajustar margen del contenido en tiempo real
  if (mainContent) {
    mainContent.classList.toggle('sidebar-collapsed', isCollapsed);
  }

  localStorage.setItem('sidebarCollapsed', isCollapsed);
}

// Restaurar estado del sidebar al cargar (solo desktop)
document.addEventListener('DOMContentLoaded', () => {
  if (window.innerWidth <= 768) return; // en móvil no restaurar

  const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
  const sidebar     = document.querySelector('.sidebar');
  const mainContent = document.querySelector('.main-content');

  if (isCollapsed && sidebar) {
    sidebar.classList.add('collapsed');
    if (mainContent) mainContent.classList.add('sidebar-collapsed');
  }
});

// Cerrar sidebar al hacer click en overlay (móvil)
document.addEventListener('DOMContentLoaded', () => {
  const overlay = document.querySelector('.sidebar-overlay');
  if (overlay) overlay.addEventListener('click', toggleSidebarMobile);
});

// Cerrar sidebar al hacer click en un link (móvil)
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.sidebar-item').forEach(item => {
    item.addEventListener('click', () => {
      if (window.innerWidth <= 768) toggleSidebarMobile();
    });
  });
});

// Recalcular al redimensionar la ventana
window.addEventListener('resize', () => {
  const mainContent = document.querySelector('.main-content');
  const sidebar     = document.querySelector('.sidebar');
  if (!mainContent || !sidebar) return;

  if (window.innerWidth <= 768) {
    // Móvil: sin margen
    mainContent.style.marginLeft = '';
    mainContent.classList.remove('sidebar-collapsed');
    sidebar.classList.remove('active');
    document.querySelector('.sidebar-overlay')?.classList.remove('active');
    document.querySelector('.hamburger-menu')?.classList.remove('active');
  }
});

// ===== ANIMACIONES AL SCROLL =====
const observerOptions = { threshold: 0.1, rootMargin: '0px 0px -50px 0px' };
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.style.opacity = '1';
      entry.target.style.transform = 'translateY(0)';
    }
  });
}, observerOptions);

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.card, .stat-card, .glass-card').forEach(el => {
    el.style.opacity    = '0';
    el.style.transform  = 'translateY(20px)';
    el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
    observer.observe(el);
  });
});

// ===== EFECTO RIPPLE EN BOTONES =====
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.btn, .sidebar-item, .glass-card').forEach(button => {
    button.addEventListener('click', function(e) {
      const ripple = document.createElement('span');
      const rect   = this.getBoundingClientRect();
      const size   = Math.max(rect.width, rect.height);
      ripple.style.cssText = `
        width:${size}px; height:${size}px;
        left:${e.clientX - rect.left - size/2}px;
        top:${e.clientY - rect.top - size/2}px;
        position:absolute; border-radius:50%;
        background:rgba(0,217,255,0.4);
        transform:scale(0); animation:ripple 0.6s ease-out;
        pointer-events:none;
      `;
      this.style.position = 'relative';
      this.style.overflow = 'hidden';
      this.appendChild(ripple);
      setTimeout(() => ripple.remove(), 600);
    });
  });
});

// Inyectar keyframes ripple
const _s = document.createElement('style');
_s.textContent = `@keyframes ripple { to { transform:scale(4); opacity:0; } }`;
document.head.appendChild(_s);

// ===== FADE IN AL CARGAR =====
window.addEventListener('load', () => {
  document.body.style.opacity = '0';
  setTimeout(() => {
    document.body.style.transition = 'opacity 0.5s ease';
    document.body.style.opacity    = '1';
  }, 100);
});

console.log('%c🏢 Sistema de Gestión de Socios %c v1.1',
  'background:linear-gradient(135deg,#00D9FF,#0099CC);color:#fff;padding:8px 16px;border-radius:5px;font-weight:bold;',
  'background:#1a1d2e;color:#00D9FF;padding:8px 16px;border-radius:5px;');