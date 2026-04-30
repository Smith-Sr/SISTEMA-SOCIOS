<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarSesion();
$usuario  = getUsuarioActual();
$conexion = getConexion();

// Solo columnas necesarias para la tabla
$socios = $conexion->query("
    SELECT ID, DNI, APELLIDOS, NOMBRES, INGRESO, ESTADO
    FROM socios
    ORDER BY ID DESC
")->fetchAll();

// Estadísticas por estado
$stats_estados = $conexion->query("
    SELECT ESTADO, COUNT(*) as total FROM socios GROUP BY ESTADO
")->fetchAll(PDO::FETCH_KEY_PAIR);

$total_socios = count($socios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Socios - Sistema de Gestión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../css/topbar-menu.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/style.css?v=<?php echo time(); ?>">
</head>
<body>

<!-- TOPBAR FIJO -->
<div class="topbar-fixed">
    <div class="topbar-logo">
        <span>🏢</span>
        <span>Club Lawn Tennis</span>
    </div>
    <button class="menu-btn" onclick="toggleMenu()" aria-label="Abrir menú">
        <div class="menu-line"></div>
        <div class="menu-line"></div>
        <div class="menu-line"></div>
    </button>
</div>

<!-- OVERLAY -->
<div class="menu-overlay" onclick="toggleMenu()"></div>

<!-- MENÚ FLOTANTE -->
<div class="floating-menu">
    <div class="menu-section">
        <div class="menu-title">📊 Principal</div>
        <a href="../../index.php" class="menu-item">
            <span>📈</span><span>Dashboard</span>
        </a>
        <a href="buscar_socios.php" class="menu-item">
            <span>🔍</span><span>Buscar Socio</span>
        </a>
        <a href="ver_socios.php" class="menu-item active">
            <span>📋</span><span>Lista Completa</span>
        </a>
        <a href="escanear_qr.php" class="menu-item">
            <span>📷</span><span>Escanear QR</span>
        </a>
    </div>

    <?php if ($usuario['rol'] === 'admin'): ?>
    <div class="menu-section">
        <div class="menu-title">⚙️ Administración</div>
        <a href="agregar_socio_web.php" class="menu-item">
            <span>➕</span><span>Agregar Socio</span>
        </a>
        <a href="importar_excel.php" class="menu-item">
            <span>📤</span><span>Importar Excel</span>
        </a>
        <a href="gestionar_socios.php" class="menu-item">
            <span>✏️</span><span>Gestionar Socios</span>
        </a>
        <a href="gestionar_usuarios.php" class="menu-item">
            <span>👥</span><span>Usuarios</span>
        </a>
    </div>
    <?php endif; ?>

    <div class="menu-section">
        <div class="menu-title">👤 Usuario</div>
        <div class="menu-user-info">
            <div class="fw-600"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
            <div class="text-muted"><?php echo htmlspecialchars($usuario['rol']); ?></div>
        </div>
        <a href="../actions/logout.php" class="menu-item menu-item-danger">
            <span>🚪</span><span>Cerrar Sesión</span>
        </a>
    </div>
</div>

<!-- BOTÓN RETROCEDER -->
<a href="javascript:history.back()" class="back-btn" title="Volver">←</a>

<!-- CONTENIDO -->
<div class="container">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>📋 Lista de Socios</h1>
            <p class="subtitle" id="contador-resultados">
                Mostrando <?php echo $total_socios; ?> de <?php echo $total_socios; ?> socios
            </p>
        </div>
        <div class="topbar-right">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo htmlspecialchars($usuario['nombre']); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($usuario['rol']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTADÍSTICAS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_socios; ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats_estados['activo'] ?? 0; ?></div>
            <div class="stat-label">Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats_estados['vitalicio'] ?? 0; ?></div>
            <div class="stat-label">Vitalicios</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo $stats_estados['inactivo'] ?? 0; ?></div>
            <div class="stat-label">Inactivos</div>
        </div>
    </div>

    <!-- FILTROS -->
    <div class="card">
        <div class="filters-grid">
            <div class="form-group">
                <label>🔍 Buscar (tiempo real)</label>
                <input type="text"
                       id="busqueda-tiempo-real"
                       class="form-input"
                       placeholder="DNI, apellidos o nombres...">
                <small class="text-muted">💡 Presiona ESC para limpiar</small>
            </div>
            <div class="form-group">
                <label>🏷️ Filtrar por Estado</label>
                <select id="filtro-estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="inactivo">Inactivo</option>
                    <option value="vitalicio">Vitalicio</option>
                    <option value="transeunte">Transeunte</option>
                    <option value="suspendido">Suspendido</option>
                </select>
            </div>
        </div>
    </div>

    <!-- TABLA -->
    <div class="card">
        <div class="table-container">
            <?php if ($total_socios > 0): ?>
            <table class="table-premium" id="tabla-socios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>DNI</th>
                        <th>Apellidos y Nombres</th>
                        <th>Ingreso</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($socios as $socio): ?>
                    <tr>
                        <td><strong>#<?php echo $socio['ID']; ?></strong></td>
                        <td><?php echo htmlspecialchars($socio['DNI']); ?></td>
                        <td><?php echo htmlspecialchars($socio['APELLIDOS'] . ', ' . $socio['NOMBRES']); ?></td>
                        <td><?php echo $socio['INGRESO'] ? date('d/m/Y', strtotime($socio['INGRESO'])) : '—'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $socio['ESTADO']; ?>">
                                <?php echo strtoupper($socio['ESTADO']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🔭</div>
                <h3>No se encontraron socios</h3>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="../../js/menu.js"></script>
<script src="../../js/busqueda-tiempo-real.js"></script>
<script src="../../js/paginacion.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        inicializarBusquedaConFiltros('busqueda-tiempo-real', 'filtro-estado', 'tabla-socios', [1, 2]);
        inicializarPaginacion('tabla-socios', 30);
    });
</script>
<script src="../../js/toast.js"></script>
</body>
</html>