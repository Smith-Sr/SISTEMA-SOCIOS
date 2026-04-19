<?php
/**
 * COMPONENTE SIDEBAR REUTILIZABLE - VERSIÓN CORREGIDA
 * Sin conflictos de divs
 */

function renderSidebar($activePage = '') {
    global $usuario;
    ?>
    <!-- Hamburger Menu (solo móvil) -->
    <button class="hamburger-menu" onclick="toggleSidebarMobile()" aria-label="Menú">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>

    <!-- Overlay para móvil -->
    <div class="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

    <!-- SIDEBAR EXPANDIBLE -->
    <aside class="sidebar">
        <!-- Header con logo y toggle -->
        <div class="sidebar-header">
            <a href="<?php echo getBasePath(); ?>index.php" class="sidebar-brand">
                <div class="sidebar-logo">🏢</div>
                <span class="sidebar-title">Lawn Tennis</span>
            </a>
            <button class="sidebar-toggle-btn" onclick="toggleSidebarCollapse()" title="Colapsar sidebar" aria-label="Colapsar menú">
                ◀
            </button>
        </div>

        <!-- Navegación -->
        <nav class="sidebar-nav">
            <a href="<?php echo getBasePath(); ?>index.php" class="sidebar-item <?php echo $activePage == 'dashboard' ? 'active' : ''; ?>">
                <span class="sidebar-item-icon">📊</span>
                <span class="sidebar-item-text">Dashboard</span>
            </a>

            <a href="<?php echo getBasePath(); ?>php/pages/buscar_socios.php" class="sidebar-item <?php echo $activePage == 'buscar' ? 'active' : ''; ?>">
                <span class="sidebar-item-icon">🔍</span>
                <span class="sidebar-item-text">Consultar Socio</span>
            </a>

            <a href="<?php echo getBasePath(); ?>php/pages/ver_socios.php" class="sidebar-item <?php echo $activePage == 'ver' ? 'active' : ''; ?>">
                <span class="sidebar-item-icon">📋</span>
                <span class="sidebar-item-text">Lista Completa</span>
            </a>

            <?php if ($usuario['rol'] == 'admin'): ?>
            <div class="sidebar-divider"></div>

            <a href="<?php echo getBasePath(); ?>php/pages/agregar_socio_web.php" class="sidebar-item <?php echo $activePage == 'agregar' ? 'active' : ''; ?>">
                <span class="sidebar-item-icon">➕</span>
                <span class="sidebar-item-text">Agregar Socio</span>
            </a>

            <a href="<?php echo getBasePath(); ?>php/pages/importar_excel.php" class="sidebar-item <?php echo $activePage == 'importar' ? 'active' : ''; ?>">
                <span class="sidebar-item-icon">📤</span>
                <span class="sidebar-item-text">Importar Excel</span>
            </a>

            <a href="<?php echo getBasePath(); ?>php/pages/gestionar_socios.php" class="sidebar-item <?php echo $activePage == 'gestionar' ? 'active' : ''; ?>">
                <span class="sidebar-item-icon">⚙️</span>
                <span class="sidebar-item-text">Gestionar Socios</span>
            </a>

            <a href="<?php echo getBasePath(); ?>php/pages/gestionar_usuarios.php" class="sidebar-item <?php echo $activePage == 'usuarios' ? 'active' : ''; ?>">
                <span class="sidebar-item-icon">👥</span>
                <span class="sidebar-item-text">Usuarios</span>
            </a>
            <?php endif; ?>
        </nav>

        <!-- Logout -->
        <a href="<?php echo getBasePath(); ?>php/actions/logout.php" class="sidebar-item logout">
            <span class="sidebar-item-icon">🚪</span>
            <span class="sidebar-item-text">Cerrar Sesión</span>
        </a>
    </aside>
    <?php
}

/**
 * Función helper para obtener la ruta base correcta
 */
function getBasePath() {
    $currentPath = $_SERVER['PHP_SELF'];
    
    if (strpos($currentPath, '/php/pages/') !== false) {
        return '../../';
    } elseif (strpos($currentPath, '/php/') !== false) {
        return '../';
    }
    
    return '';
}
?>