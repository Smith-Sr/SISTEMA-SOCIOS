<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/php/includes/config_session.php';
verificarSesion();
$usuario  = getUsuarioActual();
$conexion = getConexion();

// Estadísticas generales en una sola query
$stats = $conexion->query("
    SELECT
        COUNT(*) as total_socios,
        SUM(ESTADO = 'activo') as socios_activos
    FROM socios
")->fetch(PDO::FETCH_ASSOC);

$asistencias_hoy = $conexion->query("
    SELECT COUNT(*) as total FROM asistencias
    WHERE DATE(fecha_hora) = CURDATE()
")->fetch()['total'];

$asistencias_mes = $conexion->query("
    SELECT COUNT(*) as total FROM asistencias
    WHERE MONTH(fecha_hora) = MONTH(CURDATE())
    AND YEAR(fecha_hora) = YEAR(CURDATE())
")->fetch()['total'];

// Actividad reciente
$actividades = $conexion->query("
    SELECT a.*, s.DNI, s.NOMBRES, s.APELLIDOS, s.ESTADO,
           u.nombre_completo AS verificador
    FROM asistencias a
    JOIN socios s ON a.socio_id = s.ID
    JOIN usuarios u ON a.verificado_por = u.id
    ORDER BY a.fecha_hora DESC
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Sistema de Gestión de Socios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>

    <!-- Hamburger Menu (solo móvil) -->
    <button class="hamburger-menu" onclick="toggleSidebarMobile()" aria-label="Abrir menú">
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
    </button>

    <!-- Overlay para móvil -->
    <div class="sidebar-overlay" onclick="toggleSidebarMobile()"></div>

    <div class="app-layout">

        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <a href="index.php" class="sidebar-brand">
                    <div class="sidebar-logo">🏢</div>
                    <span class="sidebar-title">Lawn Tennis</span>
                </a>
                <button class="sidebar-toggle-btn" onclick="toggleSidebarCollapse()" title="Colapsar sidebar">◀</button>
            </div>

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

                <?php if ($usuario['rol'] === 'admin'): ?>
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

            <a href="php/actions/logout.php" class="sidebar-item logout">
                <span class="sidebar-item-icon">🚪</span>
                <span class="sidebar-item-text">Cerrar Sesión</span>
            </a>
        </aside>

        <!-- CONTENIDO PRINCIPAL -->
        <main class="main-content">
            <div class="container">

                <!-- TOPBAR -->
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
                                <div class="user-name"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
                                <div class="user-role"><?php echo strtoupper($usuario['rol']); ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACCESOS RÁPIDOS -->
                <div class="card mb-lg">
                    <div class="card-header">
                        <h2 class="card-title">⚡ Accesos Rápidos</h2>
                    </div>
                    <div class="quick-access-grid">
                        <a href="php/pages/buscar_socios.php" class="quick-access-item">
                            <div class="quick-icon">🔍</div>
                            <div class="quick-text"><h3>Buscar</h3><p>Por DNI</p></div>
                        </a>
                        <a href="php/pages/ver_socios.php" class="quick-access-item">
                            <div class="quick-icon">📋</div>
                            <div class="quick-text"><h3>Ver Lista</h3><p>Todos</p></div>
                        </a>
                        <a href="php/pages/escanear_qr.php" class="quick-access-item">
                            <div class="quick-icon">📷</div>
                            <div class="quick-text"><h3>Escanear QR</h3><p>Verificar socio</p></div>
                        </a>
                        <?php if ($usuario['rol'] === 'admin'): ?>
                        <a href="php/pages/agregar_socio_web.php" class="quick-access-item">
                            <div class="quick-icon">➕</div>
                            <div class="quick-text"><h3>Agregar</h3><p>Nuevo socio</p></div>
                        </a>
                        <a href="php/pages/gestionar_socios.php" class="quick-access-item">
                            <div class="quick-icon">⚙️</div>
                            <div class="quick-text"><h3>Gestionar</h3><p>Editar/Eliminar</p></div>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ESTADÍSTICAS -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">👥</div>
                            <div class="stat-trend"><span>↗</span><span>Total</span></div>
                        </div>
                        <div class="stat-value"><?php echo $stats['total_socios']; ?></div>
                        <div class="stat-label">Total de Socios</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">✅</div>
                            <div class="stat-trend"><span>↗</span><span>Activos</span></div>
                        </div>
                        <div class="stat-value"><?php echo $stats['socios_activos']; ?></div>
                        <div class="stat-label">Socios Activos</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">📅</div>
                            <div class="stat-trend"><span>↗</span><span>Hoy</span></div>
                        </div>
                        <div class="stat-value"><?php echo $asistencias_hoy; ?></div>
                        <div class="stat-label">Asistencias Hoy</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">📊</div>
                            <div class="stat-trend"><span>↗</span><span>Mes</span></div>
                        </div>
                        <div class="stat-value"><?php echo $asistencias_mes; ?></div>
                        <div class="stat-label">Asistencias del Mes</div>
                    </div>
                </div>

                <!-- ACTIVIDAD RECIENTE -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">🕐 Actividad Reciente</h2>
                        <a href="php/pages/ver_socios.php" class="btn btn-ghost btn-sm">
                            <span>📋</span><span>Ver socios</span>
                        </a>
                    </div>

                    <?php if (count($actividades) > 0): ?>
                    <div class="table-container">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>🆔</th>
                                    <th>👤 Socio</th>
                                    <th>DNI</th>
                                    <th>Estado</th>
                                    <th>Tipo</th>
                                    <th>Verificado por</th>
                                    <th>Fecha y Hora</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($actividades as $act): ?>
                                <tr>
                                    <td><strong class="text-primary">#<?php echo $act['socio_id']; ?></strong></td>
                                    <td>
                                        <span class="fw-600">
                                            <?php echo htmlspecialchars($act['APELLIDOS'] . ', ' . $act['NOMBRES']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="dni-badge">
                                            <?php echo htmlspecialchars($act['DNI']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?php echo $act['ESTADO']; ?>">
                                            <?php echo strtoupper($act['ESTADO']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $act['tipo_verificacion'] === 'busqueda_dni' ? 'badge-activo' : 'badge-vitalicio'; ?>">
                                            <?php echo $act['tipo_verificacion'] === 'busqueda_dni' ? '🔍 DNI' : '📱 QR'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="verificador-cell">
                                            <div class="verificador-avatar">
                                                <?php echo strtoupper(substr($act['verificador'], 0, 1)); ?>
                                            </div>
                                            <span><?php echo htmlspecialchars($act['verificador']); ?></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fecha-cell">
                                            <span>📅 <?php echo date('d/m/Y', strtotime($act['fecha_hora'])); ?></span>
                                            <span class="text-muted">🕐 <?php echo date('H:i:s', strtotime($act['fecha_hora'])); ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📭</div>
                        <h3>Sin actividad reciente</h3>
                        <p>Aún no hay registros de asistencias en el sistema</p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </main>
    </div>

    <script src="js/effects.js"></script>
    <script src="js/toast.js"></script>
</body>
</html>