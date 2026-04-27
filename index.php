<?php

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");


require_once __DIR__ . '/php/includes/config_session.php';
verificarSesion();
$usuario = getUsuarioActual();

$conexion = getConexion();

// Estadísticas
$sql_total = "SELECT COUNT(*) as total FROM socios";
$stmt_total = $conexion->query($sql_total);
$total_socios = $stmt_total->fetch()['total'];

$sql_activos = "SELECT COUNT(*) as total FROM socios WHERE ESTADO = 'activo'";
$stmt_activos = $conexion->query($sql_activos);
$socios_activos = $stmt_activos->fetch()['total'];

$sql_hoy = "SELECT COUNT(*) as total FROM asistencias WHERE DATE(fecha_hora) = CURDATE()";
$stmt_hoy = $conexion->query($sql_hoy);
$asistencias_hoy = $stmt_hoy->fetch()['total'];

$sql_mes = "SELECT COUNT(*) as total FROM asistencias WHERE MONTH(fecha_hora) = MONTH(CURDATE()) AND YEAR(fecha_hora) = YEAR(CURDATE())";
$stmt_mes = $conexion->query($sql_mes);
$asistencias_mes = $stmt_mes->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Gestión de Socios</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
    <!-- Hamburger Menu (solo móvil) -->
    <button class="hamburger-menu" onclick="toggleSidebarMobile()">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>

    <!-- Overlay para móvil -->
    <div class="sidebar-overlay"></div>

    <div class="app-layout">
        <!-- SIDEBAR EXPANDIBLE -->
        <aside class="sidebar">
            <!-- Header con logo y toggle -->
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-brand">
                    <div class="sidebar-logo">🏢</div>
                    <span class="sidebar-title">Lawn Tennis</span>
                </a>
                <button class="sidebar-toggle-btn" onclick="toggleSidebarCollapse()" title="Colapsar sidebar">
                    ◀
                </button>
            </div>

            <!-- Navegación -->
            <nav class="sidebar-nav">
                <a href="index.php" class="sidebar-item active">
                    <span class="sidebar-item-icon">📊</span>
                    <span class="sidebar-item-text">Dashboard</span>
                </a>

                <a href="php/pages/buscar_socios.php" class="sidebar-item">
                    <span class="sidebar-item-icon">🔍</span>
                    <span class="sidebar-item-text">Buscar Socio</span>
                </a>

                <a href="php/pages/ver_socios.php" class="sidebar-item">
                    <span class="sidebar-item-icon">📋</span>
                    <span class="sidebar-item-text">Lista Completa</span>
                </a>

                <a href="php/pages/escanear_qr.php" class="sidebar-item">
                    <span class="sidebar-item-icon">📷</span>
                    <span class="sidebar-item-text">Escanear QR</span>
                </a>

                <?php if ($usuario['rol'] == 'admin'): ?>
                <div class="sidebar-divider"></div>

                <a href="php/pages/agregar_socio_web.php" class="sidebar-item">
                    <span class="sidebar-item-icon">➕</span>
                    <span class="sidebar-item-text">Agregar Socio</span>
                </a>

                <a href="php/pages/importar_excel.php" class="sidebar-item">
                    <span class="sidebar-item-icon">📤</span>
                    <span class="sidebar-item-text">Importar Excel</span>
                </a>

                <a href="php/pages/gestionar_socios.php" class="sidebar-item">
                    <span class="sidebar-item-icon">⚙️</span>
                    <span class="sidebar-item-text">Gestionar Socios</span>
                </a>

                <a href="php/pages/gestionar_usuarios.php" class="sidebar-item">
                    <span class="sidebar-item-icon">👥</span>
                    <span class="sidebar-item-text">Usuarios</span>
                </a>
                <?php endif; ?>
            </nav>

            <!-- Logout -->
            <a href="php/actions/logout.php" class="sidebar-item logout">
                <span class="sidebar-item-icon">🚪</span>
                <span class="sidebar-item-text">Cerrar Sesión</span>
            </a>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            <div class="container">

                <!-- ✅ TOPBAR MEJORADO (CONSISTENTE CON OTRAS PÁGINAS) -->
                <div class="topbar">
                    <div class="topbar-left">
                        <h1>📊 Dashboard General</h1>
                        <p class="subtitle">Panel de control y estadísticas del sistema</p>
                    </div>

                    <div class="topbar-right">
                        <div class="user-profile">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="user-name"><?php echo $usuario['nombre']; ?></div>
                                <div class="user-role"><?php echo strtoupper($usuario['rol']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACCESOS RÁPIDOS -->
                <div class="card" style="margin-bottom: var(--space-lg);">
                    <div class="card-header">
                        <h2 class="card-title">⚡ Accesos Rápidos</h2>
                    </div>

                    <div class="quick-access-grid">
                        <a href="php/pages/buscar_socios.php" class="quick-access-item">
                            <div class="quick-icon">🔍</div>
                            <div class="quick-text">
                                <h3>Buscar</h3>
                                <p>Por DNI</p>
                            </div>
                        </a>

                        <a href="php/pages/ver_socios.php" class="quick-access-item">
                            <div class="quick-icon">📋</div>
                            <div class="quick-text">
                                <h3>Ver Lista</h3>
                                <p>Todos</p>
                            </div>
                        </a>

                        <?php if ($usuario['rol'] == 'admin'): ?>
                        <a href="php/pages/agregar_socio_web.php" class="quick-access-item">
                            <div class="quick-icon">➕</div>
                            <div class="quick-text">
                                <h3>Agregar</h3>
                                <p>Nuevo socio</p>
                            </div>
                        </a>

                        <a href="php/pages/gestionar_socios.php" class="quick-access-item">
                            <div class="quick-icon">⚙️</div>
                            <div class="quick-text">
                                <h3>Gestionar</h3>
                                <p>Editar/Eliminar</p>
                            </div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- STATS CARDS CON GLASSMORPHISM -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">👥</div>
                            <div class="stat-trend">
                                <span>↗</span>
                                <span>+12%</span>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $total_socios; ?></div>
                        <div class="stat-label">Total de Socios</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">✅</div>
                            <div class="stat-trend">
                                <span>↗</span>
                                <span>+8%</span>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $socios_activos; ?></div>
                        <div class="stat-label">Socios Activos</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">📅</div>
                            <div class="stat-trend">
                                <span>↗</span>
                                <span>+15%</span>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $asistencias_hoy; ?></div>
                        <div class="stat-label">Asistencias Hoy</div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">📊</div>
                            <div class="stat-trend">
                                <span>↗</span>
                                <span>+23%</span>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo $asistencias_mes; ?></div>
                        <div class="stat-label">Asistencias del Mes</div>
                    </div>
                </div>



                <!-- ✅ ACTIVIDAD RECIENTE MEJORADA (VISIBLE PARA ADMIN Y AGENTE) -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">🕐 Actividad Reciente</h2>
                        <a href="php/pages/ver_socios.php" class="btn btn-ghost"
                            style="padding: 0.5rem 1rem; font-size: 0.85rem;">
                            <span>📋</span>
                            <span>Ver socios</span>
                        </a>
                    </div>

                    <?php
                    $sql_reciente = "SELECT a.*, s.DNI, s.NOMBRES, s.APELLIDOS, s.ESTADO, u.nombre_completo as verificador 
                                    FROM asistencias a 
                                    JOIN socios s ON a.socio_id = s.ID 
                                    JOIN usuarios u ON a.verificado_por = u.id 
                                    ORDER BY a.fecha_hora DESC 
                                    LIMIT 10";
                    $stmt_reciente = $conexion->query($sql_reciente);
                    $actividades = $stmt_reciente->fetchAll();
                    ?>

                    <?php if (count($actividades) > 0): ?>
                    <div class="table-container">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th style="width: 50px;">🆔</th>
                                    <th>👤 Socio</th>
                                    <th>🆔 DNI</th>
                                    <th>🏷️ Estado</th>
                                    <th>🔍 Tipo</th>
                                    <th>👨‍💼 Verificado por</th>
                                    <th>📅 Fecha y Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actividades as $act): ?>
                                <tr>
                                    <td><strong style="color: var(--primary);">#<?php echo $act['socio_id']; ?></strong>
                                    </td>
                                    <td>
                                        <div style="font-weight: 600; color: var(--text-primary);">
                                            <?php echo $act['APELLIDOS'] . ', ' . $act['NOMBRES']; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            style="font-family: 'Courier New', monospace; background: rgba(0, 217, 255, 0.1); padding: 0.25rem 0.75rem; border-radius: 6px; font-weight: 600;">
                                            <?php echo $act['DNI']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $act['ESTADO']; ?>">
                                            <?php echo strtoupper($act['ESTADO']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span
                                            class="badge <?php echo $act['tipo_verificacion'] == 'busqueda_dni' ? 'badge-activo' : 'badge-vitalicio'; ?>">
                                            <?php echo $act['tipo_verificacion'] == 'busqueda_dni' ? '🔍 DNI' : '📱 QR'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                                            <div
                                                style="width: 32px; height: 32px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem;">
                                                <?php echo strtoupper(substr($act['verificador'], 0, 1)); ?>
                                            </div>
                                            <span style="font-weight: 500;"><?php echo $act['verificador']; ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div style="display: flex; flex-direction: column; gap: 0.25rem;">
                                            <span style="font-weight: 600; color: var(--text-primary);">
                                                📅 <?php echo date('d/m/Y', strtotime($act['fecha_hora'])); ?>
                                            </span>
                                            <span style="font-size: 0.85rem; color: var(--text-secondary);">
                                                🕐 <?php echo date('H:i:s', strtotime($act['fecha_hora'])); ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: var(--space-xl);">
                        <div style="font-size: 4rem; margin-bottom: var(--space-md); opacity: 0.5;">📭</div>
                        <h3 style="color: var(--text-secondary); margin-bottom: var(--space-sm);">Sin actividad reciente
                        </h3>
                        <p style="color: var(--text-muted); font-size: 0.9rem;">
                            Aún no hay registros de asistencias en el sistema
                        </p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <!-- JavaScript con efectos -->
    <script src="js/effects.js"></script>

    <style>
    /* Overlay responsive */
    .sidebar-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(5px);
        z-index: 999;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.active {
        display: block;
        opacity: 1;
    }

    @media (max-width: 768px) {
        .sidebar {
            transform: translateX(-100%);
        }

        .sidebar.active {
            transform: translateX(0);
        }
    }
    </style>
<script src="js/toast.js"></script>
</body>

</html>