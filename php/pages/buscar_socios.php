<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarSesion();
$usuario = getUsuarioActual();

$socio             = null;
$stats             = null;
$error             = false;
$socios_encontrados = [];
$busqueda_tipo     = '';

// PROCESAR POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['buscar'])) {
    $termino  = trim($_POST['buscar']);
    $conexion = getConexion();

    if (preg_match('/^\d{8}$/', $termino)) {
        // Búsqueda por DNI — solo campos necesarios
        $stmt = $conexion->prepare("
            SELECT ID, DNI, NOMBRES, APELLIDOS, ESTADO, INGRESO
            FROM socios WHERE DNI = ?
        ");
        $stmt->execute([$termino]);
        $socio_temp = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($socio_temp) {
            // Registrar asistencia
            try {
                $stmt_asistencia = $conexion->prepare("
                    INSERT INTO asistencias (socio_id, tipo_verificacion, verificado_por, ip_acceso)
                    VALUES (?, 'busqueda_dni', ?, ?)
                ");
                $stmt_asistencia->execute([
                    $socio_temp['ID'],
                    $usuario['id'],
                    $_SERVER['REMOTE_ADDR']
                ]);
            } catch (PDOException $e) {
                error_log("Error asistencia: " . $e->getMessage());
            }

            // Estadísticas de asistencia
            try {
                $stmt_stats = $conexion->prepare("
                    SELECT
                        COUNT(*) AS total_visitas,
                        MAX(fecha_hora) AS ultima_visita,
                        MIN(fecha_hora) AS primera_visita,
                        SUM(tipo_verificacion = 'busqueda_dni') AS por_dni,
                        SUM(tipo_verificacion = 'escaneo_qr') AS por_qr,
                        SUM(DATE(fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS visitas_mes
                    FROM asistencias WHERE socio_id = ?
                ");
                $stmt_stats->execute([$socio_temp['ID']]);
                $stats_temp = $stmt_stats->fetch(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $stats_temp = [
                    'total_visitas' => 0, 'ultima_visita'  => null,
                    'primera_visita' => null, 'por_dni'    => 0,
                    'por_qr'        => 0, 'visitas_mes'    => 0
                ];
            }

            $_SESSION['busqueda_socio'] = $socio_temp;
            $_SESSION['busqueda_stats'] = $stats_temp;
            $_SESSION['busqueda_tipo']  = 'dni';

        } else {
            $_SESSION['busqueda_error'] = true;
            $_SESSION['busqueda_tipo']  = 'dni';
        }

    } else {
        // Búsqueda por nombre — solo campos necesarios
        $like = '%' . $termino . '%';
        $stmt = $conexion->prepare("
            SELECT ID, DNI, NOMBRES, APELLIDOS, ESTADO
            FROM socios
            WHERE APELLIDOS LIKE ? OR NOMBRES LIKE ?
               OR CONCAT(NOMBRES, ' ', APELLIDOS) LIKE ?
               OR CONCAT(APELLIDOS, ' ', NOMBRES) LIKE ?
            ORDER BY APELLIDOS ASC
            LIMIT 20
        ");
        $stmt->execute([$like, $like, $like, $like]);

        $_SESSION['busqueda_lista'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $_SESSION['busqueda_tipo']  = 'nombre';
    }

    header('Location: buscar_socios.php');
    exit;
}

// RECUPERAR DE SESIÓN (GET)
if (isset($_SESSION['busqueda_tipo'])) {
    $busqueda_tipo = $_SESSION['busqueda_tipo'];

    if ($busqueda_tipo === 'dni') {
        $socio = $_SESSION['busqueda_socio'] ?? null;
        $stats = $_SESSION['busqueda_stats'] ?? null;
        $error = $_SESSION['busqueda_error'] ?? false;
    } else {
        $socios_encontrados = $_SESSION['busqueda_lista'] ?? [];
        if (empty($socios_encontrados)) $error = true;
    }

    // Limpiar todas las claves de búsqueda de sesión de una vez
    foreach (['busqueda_socio','busqueda_stats','busqueda_lista','busqueda_tipo','busqueda_error'] as $key) {
        unset($_SESSION[$key]);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Socio - Sistema de Gestión</title>
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
        <a href="buscar_socios.php" class="menu-item active">
            <span>🔍</span><span>Consultar Socio</span>
        </a>
        <a href="ver_socios.php" class="menu-item">
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
<a href="../../index.php" class="back-btn" title="Volver">←</a>

<!-- CONTENIDO -->
<div class="container">

    <!-- TOPBAR -->
    <div class="topbar">
        <div class="topbar-left">
            <h1>🔍 Buscar Socio</h1>
            <p class="subtitle">Ingrese DNI o nombre del socio</p>
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

    <!-- FORMULARIO DE BÚSQUEDA -->
    <div class="card">
        <form method="POST">
            <div class="search-bar">
                <input type="text"
                       name="buscar"
                       class="search-input"
                       placeholder="DNI (8 dígitos) o nombre del socio..."
                       required
                       minlength="3"
                       autofocus>
                <button type="submit" class="btn btn-primary">
                    <span>🔍</span><span>Buscar</span>
                </button>
            </div>
        </form>
    </div>

    <!-- RESULTADOS POR NOMBRE -->
    <?php if ($busqueda_tipo === 'nombre' && !empty($socios_encontrados)): ?>
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">🔍 <?php echo count($socios_encontrados); ?> resultado(s) encontrados</h2>
        </div>
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>DNI</th>
                        <th>Apellidos y Nombres</th>
                        <th>Estado</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($socios_encontrados as $s): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($s['DNI']); ?></td>
                        <td><?php echo htmlspecialchars($s['APELLIDOS'] . ', ' . $s['NOMBRES']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $s['ESTADO']; ?>">
                                <?php echo strtoupper($s['ESTADO']); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="buscar" value="<?php echo htmlspecialchars($s['DNI']); ?>">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    👁️ Ver detalle
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- RESULTADO ENCONTRADO POR DNI -->
    <?php if ($socio): ?>
    <div class="card card-border-success">
        <div class="card-header">
            <h2 class="card-title text-success">✅ Socio Encontrado</h2>
            <span class="badge badge-<?php echo $socio['ESTADO']; ?>">
                <?php echo strtoupper($socio['ESTADO']); ?>
            </span>
        </div>

        <!-- INFO DEL SOCIO -->
        <div class="socio-info-grid mb-xl">
            <div class="glass-card">
                <div class="info-label">ID</div>
                <div class="info-value text-primary">#<?php echo $socio['ID']; ?></div>
            </div>
            <div class="glass-card">
                <div class="info-label">DNI</div>
                <div class="info-value"><?php echo htmlspecialchars($socio['DNI']); ?></div>
            </div>
            <div class="glass-card full-width">
                <div class="info-label">Nombre Completo</div>
                <div class="info-value">
                    <?php echo htmlspecialchars($socio['APELLIDOS'] . ', ' . $socio['NOMBRES']); ?>
                </div>
            </div>
        </div>

        <!-- ESTADÍSTICAS -->
        <h3 class="section-title">
            <span>📊</span><span>Estadísticas de Asistencia</span>
        </h3>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header"><div class="stat-icon">🎯</div></div>
                <div class="stat-value"><?php echo $stats['total_visitas']; ?></div>
                <div class="stat-label">Total de Visitas</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><div class="stat-icon stat-icon-green">📅</div></div>
                <div class="stat-value"><?php echo $stats['visitas_mes']; ?></div>
                <div class="stat-label">Últimos 30 Días</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><div class="stat-icon stat-icon-yellow">🔍</div></div>
                <div class="stat-value"><?php echo $stats['por_dni']; ?></div>
                <div class="stat-label">Por DNI</div>
            </div>
            <div class="stat-card">
                <div class="stat-header"><div class="stat-icon stat-icon-cyan">📱</div></div>
                <div class="stat-value"><?php echo $stats['por_qr']; ?></div>
                <div class="stat-label">Por QR</div>
            </div>
        </div>

        <!-- FECHAS DE VISITA -->
        <div class="fechas-grid mt-lg">
            <div class="glass-card">
                <div class="fecha-item">
                    <span class="fecha-icon">🕐</span>
                    <div>
                        <div class="info-label">Primera Visita</div>
                        <div class="info-value">
                            <?php echo $stats['primera_visita']
                                ? date('d/m/Y H:i', strtotime($stats['primera_visita']))
                                : 'Esta es la primera'; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="glass-card">
                <div class="fecha-item">
                    <span class="fecha-icon">⏰</span>
                    <div>
                        <div class="info-label">Última Visita</div>
                        <div class="info-value">
                            <?php echo $stats['ultima_visita']
                                ? date('d/m/Y H:i', strtotime($stats['ultima_visita']))
                                : 'Ahora mismo'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SOCIO NO ENCONTRADO -->
    <?php elseif ($error): ?>
    <div class="card card-border-danger">
        <div class="empty-state">
            <div class="empty-icon">❌</div>
            <h2 class="text-danger">Socio no encontrado</h2>
            <p>No existe ningún socio registrado con ese documento</p>
            <?php if ($usuario['rol'] === 'admin'): ?>
            <a href="agregar_socio_web.php" class="btn btn-primary mt-lg">
                <span>➕</span><span>Registrar nuevo socio</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="../../js/menu.js"></script>
<script src="../../js/toast.js"></script>
</body>
</html>