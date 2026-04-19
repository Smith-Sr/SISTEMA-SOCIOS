<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarSesion();
$usuario = getUsuarioActual();

$conexion = getConexion();

// Obtener TODOS los socios (sin paginación para búsqueda en tiempo real)
$sql = "SELECT * FROM socios ORDER BY ID DESC";
$consulta = $conexion->prepare($sql);
$consulta->execute();
$socios = $consulta->fetchAll();

// Estadísticas por estado
$sql_stats = "SELECT ESTADO, COUNT(*) as total FROM socios GROUP BY ESTADO";
$stmt_stats = $conexion->query($sql_stats);
$stats_estados = $stmt_stats->fetchAll(PDO::FETCH_KEY_PAIR);

$total_socios = count($socios);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Socios - Sistema de Gestión</title>
    <link rel="stylesheet" href="../../css/topbar-menu.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/style.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<!-- TOPBAR FIJO -->
<div class="topbar-fixed">
    <div class="topbar-logo">
        <span>🏢</span>
        <span>Club Lawn Tennis</span>
    </div>
    <button class="menu-btn" onclick="toggleMenu()">
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
            <span>📈</span>
            <span>Dashboard</span>
        </a>
        <a href="buscar_socios.php" class="menu-item">
            <span>🔍</span>
            <span>Buscar Socio</span>
        </a>
        <a href="ver_socios.php" class="menu-item active">
            <span>📋</span>
            <span>Lista Completa</span>
        </a>
    </div>
    
    <?php if ($usuario['rol'] == 'admin'): ?>
    <div class="menu-section">
        <div class="menu-title">⚙️ Administración</div>
        <a href="agregar_socio_web.php" class="menu-item">
            <span>➕</span>
            <span>Agregar Socio</span>
        </a>
        <a href="importar_excel.php" class="menu-item">
            <span>📤</span>
            <span>Importar Excel</span>
        </a>
        <a href="gestionar_socios.php" class="menu-item">
            <span>✏️</span>
            <span>Gestionar Socios</span>
        </a>
        <a href="gestionar_usuarios.php" class="menu-item">
            <span>👥</span>
            <span>Usuarios</span>
        </a>
    </div>
    <?php endif; ?>
    
    <div class="menu-section">
        <div class="menu-title">👤 Usuario</div>
        <div style="padding: 12px; background: rgba(0, 217, 255, 0.1); border-radius: 10px; margin-bottom: 10px;">
            <div style="font-weight: 600;"><?php echo $usuario['nombre']; ?></div>
            <div style="font-size: 0.85rem; color: var(--text-secondary);"><?php echo $usuario['rol']; ?></div>
        </div>
        <a href="../actions/logout.php" class="menu-item" style="color: #FF4560;">
            <span>🚪</span>
            <span>Cerrar Sesión</span>
        </a>
    </div>
</div>

<!-- BOTÓN RETROCEDER -->
<a href="javascript:history.back()" class="back-btn" title="Volver">
    ←
</a>

<!-- CONTENIDO -->
<div class="container">
    <h1 style="margin-bottom: 10px;">📋 Lista de Socios</h1>
    <p style="color: var(--text-secondary); margin-bottom: 30px;" id="contador-resultados">
        Mostrando <?php echo $total_socios; ?> de <?php echo $total_socios; ?> socios
    </p>
    
    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?php echo $total_socios; ?></div>
            <div class="stat-label">Total</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo isset($stats_estados['activo']) ? $stats_estados['activo'] : 0; ?></div>
            <div class="stat-label">Activos</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo isset($stats_estados['vitalicio']) ? $stats_estados['vitalicio'] : 0; ?></div>
            <div class="stat-label">Vitalicios</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo isset($stats_estados['inactivo']) ? $stats_estados['inactivo'] : 0; ?></div>
            <div class="stat-label">Inactivos</div>
        </div>
    </div>
    
    <!-- FILTROS CON BÚSQUEDA EN TIEMPO REAL -->
    <div class="card">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px;">
            <div class="form-group">
                <label>🔍 Buscar (tiempo real)</label>
                <input type="text" 
                       id="busqueda-tiempo-real" 
                       placeholder="DNI, apellidos o nombres..." 
                       style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: var(--text-primary); font-size: 1rem;">
                <small style="color: var(--text-muted); font-size: 0.8rem; margin-top: 0.5rem; display: block;">
                    💡 Presiona ESC para limpiar
                </small>
            </div>
            <div class="form-group">
                <label>🏷️ Filtrar por Estado</label>
                <select id="filtro-estado" style="width: 100%; padding: 10px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 8px; color: var(--text-primary); font-size: 1rem;">
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
            <table id="tabla-socios">
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
                    <?php if (count($socios) > 0): ?>
                        <?php foreach($socios as $socio): ?>
                        <tr>
                            <td><strong>#<?php echo $socio['ID']; ?></strong></td>
                            <td><?php echo $socio['DNI']; ?></td>
                            <td><?php echo $socio['APELLIDOS'] . ', ' . $socio['NOMBRES']; ?></td>
                            <td><?php echo date('d/m/Y', strtotime($socio['INGRESO'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $socio['ESTADO']; ?>">
                                    <?php echo strtoupper($socio['ESTADO']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: var(--text-secondary);">
                                No se encontraron socios
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="../../js/menu.js"></script>
<script src="../../js/busqueda-tiempo-real.js"></script>
<script>
    // Inicializar búsqueda en tiempo real al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        // Columnas: [1] = DNI, [2] = Apellidos y Nombres
        inicializarBusquedaConFiltros('busqueda-tiempo-real', 'filtro-estado', 'tabla-socios', [1, 2]);
        
        console.log('✅ Búsqueda en tiempo real activada en ver_socios.php');
    });
</script>

</body>
</html>