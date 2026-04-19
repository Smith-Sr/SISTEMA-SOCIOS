<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarAdmin();
$usuario = getUsuarioActual();

$conexion = getConexion();

$mensaje = '';
$tipo_mensaje = '';

// Procesar eliminación con POST
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['eliminar'])){
  $id_eliminar = $_POST['eliminar'];
  
  try {
    $sql_verificar = "SELECT COUNT(*) FROM asistencias WHERE socio_id = ?";
    $stmt_verificar = $conexion->prepare($sql_verificar);
    $stmt_verificar->execute([$id_eliminar]);
    $tiene_asistencias = $stmt_verificar->fetchColumn();
    
    if ($tiene_asistencias > 0) {
      $mensaje = "Este socio tiene $tiene_asistencias asistencia(s) registrada(s). ¿Está seguro de eliminarlo? Se perderán todos los registros.";
      $tipo_mensaje = 'warning';
      
      if (isset($_POST['confirmar_eliminacion'])) {
        $sql_del_asist = "DELETE FROM asistencias WHERE socio_id = ?";
        $stmt_del_asist = $conexion->prepare($sql_del_asist);
        $stmt_del_asist->execute([$id_eliminar]);
        
        $sql_eliminar = "DELETE FROM socios WHERE ID = ?";
        $stmt_eliminar = $conexion->prepare($sql_eliminar);
        $stmt_eliminar->execute([$id_eliminar]);
        
        $mensaje = 'Socio y sus asistencias eliminados exitosamente';
        $tipo_mensaje = 'success';
      }
    } else {
      $sql_eliminar = "DELETE FROM socios WHERE ID = ?";
      $stmt_eliminar = $conexion->prepare($sql_eliminar);
      $stmt_eliminar->execute([$id_eliminar]);
      
      $mensaje = 'Socio eliminado exitosamente';
      $tipo_mensaje = 'success';
    }
  } catch (PDOException $e) {
    $mensaje = 'Error al eliminar: ' . $e->getMessage();
    $tipo_mensaje = 'error';
  }
}

// Obtener TODOS los socios (sin paginación para búsqueda en tiempo real)
$sql = "SELECT * FROM socios ORDER BY ID DESC";
$consulta = $conexion->prepare($sql);
$consulta->execute();
$socios = $consulta->fetchAll();

$total_socios = count($socios);

// Estadísticas por estado
$sql_stats = "SELECT ESTADO, COUNT(*) as total FROM socios GROUP BY ESTADO";
$stmt_stats = $conexion->query($sql_stats);
$stats_estados = $stmt_stats->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Socios - Sistema de Gestión</title>
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
        <a href="ver_socios.php" class="menu-item">
            <span>📋</span>
            <span>Lista Completa</span>
        </a>
    </div>
    
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
        <a href="gestionar_socios.php" class="menu-item active">
            <span>✏️</span>
            <span>Gestionar Socios</span>
        </a>
        <a href="gestionar_usuarios.php" class="menu-item">
            <span>👥</span>
            <span>Usuarios</span>
        </a>
    </div>
    
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
<a href="javascript:history.back()" class="back-btn" title="Volver">←</a>

<!-- CONTENIDO -->
<div class="container">
    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>⚙️ Gestionar Socios</h1>
            <p class="subtitle">Editar o eliminar socios existentes</p>
        </div>

        <div class="topbar-right">
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($usuario['nombre'], 0, 1)); ?>
                </div>
                <div class="user-details">
                    <span class="user-name"><?php echo $usuario['nombre']; ?></span>
                    <span class="user-role"><?php echo $usuario['rol']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- MENSAJES -->
    <?php if ($mensaje != ''): ?>
    <div class="alert alert-<?php echo $tipo_mensaje; ?>">
        <?php 
            $icon = $tipo_mensaje == 'success' ? '✅' : ($tipo_mensaje == 'error' ? '❌' : '⚠️');
            echo $icon . ' ' . $mensaje; 
        ?>
    </div>
    <?php endif; ?>

    <!-- STATS RÁPIDAS -->
    <div class="stats-grid" style="margin-bottom: var(--space-lg);">
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
    <div class="card" style="margin-bottom: var(--space-lg);">
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
        <div class="card-header">
            <h2 class="card-title" id="contador-resultados">Total de socios: <?php echo $total_socios; ?></h2>
            <a href="agregar_socio_web.php" class="btn btn-primary">
                <span>➕</span>
                <span>Agregar Nuevo</span>
            </a>
        </div>

        <?php if ($total_socios > 0): ?>
        <div class="table-container">
            <table class="table-premium" id="tabla-socios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>DNI</th>
                        <th>Apellidos y Nombres</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($socios as $socio): ?>
                    <tr>
                        <td><strong>#<?php echo $socio['ID']; ?></strong></td>
                        <td><?php echo $socio['DNI']; ?></td>
                        <td><?php echo $socio['APELLIDOS'] . ', ' . $socio['NOMBRES']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $socio['ESTADO']; ?>">
                                <?php echo strtoupper($socio['ESTADO']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                <a href="editar_socio.php?id=<?php echo $socio['ID']; ?>"
                                    class="btn btn-ghost" style="padding: 0.5rem 1rem; font-size: 0.85rem;"
                                    title="Editar">
                                    ✏️ Editar
                                </a>

                                <form method="POST" style="display: inline;"
                                    onsubmit="return confirm('⚠️ ¿Está seguro de eliminar este socio?\n\nDNI: <?php echo $socio['DNI']; ?>\nNombre: <?php echo $socio['NOMBRES'] . ' ' . $socio['APELLIDOS']; ?>\n\nEsta acción no se puede deshacer.');">
                                    <input type="hidden" name="eliminar"
                                        value="<?php echo $socio['ID']; ?>">
                                    <button type="submit" class="btn btn-danger"
                                        style="padding: 0.5rem 1rem; font-size: 0.85rem; background: linear-gradient(135deg, #FF4560, #C81E1E); color: white;">
                                        🗑️ Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: var(--space-xl);">
            <div style="font-size: 4rem; margin-bottom: var(--space-md);">🔭</div>
            <h3 style="color: var(--text-secondary); margin-bottom: var(--space-md);">No hay socios
                registrados</h3>
            <a href="agregar_socio_web.php" class="btn btn-primary">
                <span>➕</span>
                <span>Agregar primer socio</span>
            </a>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="../../js/menu.js"></script>
<script src="../../js/busqueda-tiempo-real.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Columnas: [1] = DNI, [2] = Apellidos y Nombres
        inicializarBusquedaConFiltros('busqueda-tiempo-real', 'filtro-estado', 'tabla-socios', [1, 2]);
        
        console.log('✅ Búsqueda en tiempo real activada en gestionar_socios.php');
    });
</script>

</body>
</html>