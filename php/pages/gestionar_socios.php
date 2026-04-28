<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarAdmin();
$usuario  = getUsuarioActual();
$conexion = getConexion();

$mensaje      = '';
$tipo_mensaje = '';

// Procesar eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar'])) {
    $id_eliminar = (int) $_POST['eliminar']; // Forzar entero — seguridad

    try {
        // Verificar asistencias del socio
        $stmt_verificar = $conexion->prepare("SELECT COUNT(*) FROM asistencias WHERE socio_id = ?");
        $stmt_verificar->execute([$id_eliminar]);
        $tiene_asistencias = $stmt_verificar->fetchColumn();

        if ($tiene_asistencias > 0 && !isset($_POST['confirmar_eliminacion'])) {
            // Advertir antes de eliminar con asistencias
            $mensaje      = "Este socio tiene $tiene_asistencias asistencia(s) registrada(s). ¿Está seguro de eliminarlo? Se perderán todos los registros.";
            $tipo_mensaje = 'warning';
        } else {
            // Eliminar asistencias primero si las hay
            if ($tiene_asistencias > 0) {
                $stmt_del = $conexion->prepare("DELETE FROM asistencias WHERE socio_id = ?");
                $stmt_del->execute([$id_eliminar]);
            }

            // Eliminar socio
            $stmt_eliminar = $conexion->prepare("DELETE FROM socios WHERE ID = ?");
            $stmt_eliminar->execute([$id_eliminar]);

            $mensaje      = $tiene_asistencias > 0
                ? 'Socio y sus asistencias eliminados exitosamente'
                : 'Socio eliminado exitosamente';
            $tipo_mensaje = 'success';
        }
    } catch (PDOException $e) {
        $mensaje      = 'Error al eliminar: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Solo columnas necesarias para la tabla
$socios = $conexion->query("
    SELECT ID, DNI, APELLIDOS, NOMBRES, ESTADO
    FROM socios
    ORDER BY ID DESC
")->fetchAll();

$total_socios  = count($socios);
$stats_estados = $conexion->query("
    SELECT ESTADO, COUNT(*) AS total FROM socios GROUP BY ESTADO
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Socios - Sistema de Gestión</title>
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
        <a href="ver_socios.php" class="menu-item">
            <span>📋</span><span>Lista Completa</span>
        </a>
        <a href="escanear_qr.php" class="menu-item">
            <span>📷</span><span>Escanear QR</span>
        </a>
    </div>

    <div class="menu-section">
        <div class="menu-title">⚙️ Administración</div>
        <a href="agregar_socio_web.php" class="menu-item">
            <span>➕</span><span>Agregar Socio</span>
        </a>
        <a href="importar_excel.php" class="menu-item">
            <span>📤</span><span>Importar Excel</span>
        </a>
        <a href="gestionar_socios.php" class="menu-item active">
            <span>✏️</span><span>Gestionar Socios</span>
        </a>
        <a href="gestionar_usuarios.php" class="menu-item">
            <span>👥</span><span>Usuarios</span>
        </a>
    </div>

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
            <h1>⚙️ Gestionar Socios</h1>
            <p class="subtitle">Editar o eliminar socios existentes</p>
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

    <!-- TOAST — un solo bloque unificado -->
    <?php if ($mensaje !== ''): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            <?php if ($tipo_mensaje === 'success'): ?>
                toast.success('<?php echo addslashes($mensaje); ?>');
            <?php elseif ($tipo_mensaje === 'error'): ?>
                toast.error('<?php echo addslashes($mensaje); ?>');
            <?php elseif ($tipo_mensaje === 'warning'): ?>
                toast.warning('<?php echo addslashes($mensaje); ?>');
            <?php else: ?>
                toast.info('<?php echo addslashes($mensaje); ?>');
            <?php endif; ?>
        });
    </script>
    <?php endif; ?>

    <!-- ESTADÍSTICAS -->
    <div class="stats-grid mb-lg">
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
    <div class="card mb-lg">
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
        <div class="card-header">
            <h2 class="card-title" id="contador-resultados">
                Total de socios: <?php echo $total_socios; ?>
            </h2>
            <a href="agregar_socio_web.php" class="btn btn-primary btn-sm">
                <span>➕</span><span>Agregar Nuevo</span>
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
                    <?php foreach ($socios as $socio): ?>
                    <tr>
                        <td><strong>#<?php echo $socio['ID']; ?></strong></td>
                        <td><?php echo htmlspecialchars($socio['DNI']); ?></td>
                        <td><?php echo htmlspecialchars($socio['APELLIDOS'] . ', ' . $socio['NOMBRES']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $socio['ESTADO']; ?>">
                                <?php echo strtoupper($socio['ESTADO']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="acciones-cell">
                                <a href="carnet_socio.php?dni=<?php echo htmlspecialchars($socio['DNI']); ?>"
                                   class="btn btn-primary btn-sm"
                                   title="Ver Carnet QR"
                                   target="_blank">
                                    🪪 Carnet
                                </a>
                                <a href="editar_socio.php?id=<?php echo $socio['ID']; ?>"
                                   class="btn btn-ghost btn-sm"
                                   title="Editar">
                                    ✏️ Editar
                                </a>
                                <form method="POST" onsubmit="return confirmarEliminar('<?php echo htmlspecialchars($socio['DNI']); ?>', '<?php echo htmlspecialchars($socio['NOMBRES'] . ' ' . $socio['APELLIDOS']); ?>')">
                                    <input type="hidden" name="eliminar" value="<?php echo $socio['ID']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm">
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
        <div class="empty-state">
            <div class="empty-icon">🔭</div>
            <h3>No hay socios registrados</h3>
            <a href="agregar_socio_web.php" class="btn btn-primary mt-lg">
                <span>➕</span><span>Agregar primer socio</span>
            </a>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="../../js/menu.js"></script>
<script src="../../js/busqueda-tiempo-real.js"></script>
<script src="../../js/toast.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        inicializarBusquedaConFiltros('busqueda-tiempo-real', 'filtro-estado', 'tabla-socios', [1, 2]);
    });

    function confirmarEliminar(dni, nombre) {
        return confirm(
            '⚠️ ¿Está seguro de eliminar este socio?\n\n' +
            'DNI: ' + dni + '\n' +
            'Nombre: ' + nombre + '\n\n' +
            'Esta acción no se puede deshacer.'
        );
    }
</script>
</body>
</html>