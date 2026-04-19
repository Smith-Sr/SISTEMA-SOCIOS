<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarSesion();
$usuario = getUsuarioActual();

$socio = null;
$stats = null;
$error = false;

if (isset($_POST['dni']) && $_POST['dni'] != '') {
    $dni_buscar = $_POST['dni'];
    $conexion = getConexion();

    $sql = "SELECT * FROM socios WHERE DNI = ?";
    $consulta = $conexion->prepare($sql);
    $consulta->execute([$dni_buscar]);
    $socio = $consulta->fetch();

    if($socio) {
        try {
            $sql_asistencia = "INSERT INTO asistencias (socio_id, tipo_verificacion, verificado_por, ip_acceso) VALUES (?, ?, ?, ?)";
            $stmt_asistencia = $conexion->prepare($sql_asistencia);
            $stmt_asistencia->execute([
                $socio['ID'],
                'busqueda_dni',
                $usuario['id'],
                $_SERVER['REMOTE_ADDR']
            ]);
        } catch (PDOException $e) {
            error_log("Error al registrar asistencia: " . $e->getMessage());
        }

        try {
            $sql_stats = "SELECT 
                            COUNT(*) as total_visitas, 
                            MAX(fecha_hora) as ultima_visita, 
                            MIN(fecha_hora) as primera_visita, 
                            SUM(CASE WHEN tipo_verificacion = 'busqueda_dni' THEN 1 ELSE 0 END) as por_dni, 
                            SUM(CASE WHEN tipo_verificacion = 'escaneo_qr' THEN 1 ELSE 0 END) as por_qr, 
                            COUNT(CASE WHEN DATE(fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as visitas_mes
                          FROM asistencias 
                          WHERE socio_id = ?";
            $stmt_stats = $conexion->prepare($sql_stats);
            $stmt_stats->execute([$socio['ID']]);
            $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

            if (!$stats || !is_array($stats)) {
                $stats = [
                    'total_visitas' => 0,
                    'ultima_visita' => null,
                    'primera_visita' => null,
                    'por_dni' => 0,
                    'por_qr' => 0,
                    'visitas_mes' => 0
                ];
            }
        } catch (PDOException $e) {
            $stats = [
                'total_visitas' => 0,
                'ultima_visita' => null,
                'primera_visita' => null,
                'por_dni' => 0,
                'por_qr' => 0,
                'visitas_mes' => 0
            ];
            error_log("Error al obtener estadísticas: " . $e->getMessage());
        }
    } else {
        $error = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buscar Socio - Sistema de Gestión</title>
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
        <a href="buscar_socios.php" class="menu-item active">
            <span>🔍</span>
            <span>Consultar Socio</span>
        </a>
        <a href="ver_socios.php" class="menu-item">
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
<a href="../../index.php" class="back-btn" title="Volver">←</a>

<!-- CONTENIDO -->
<div class="container">
                <!-- TOPBAR -->
                <div class="topbar">
                    <div class="topbar-left">
                        <h1>🔍 Buscar Socio por DNI</h1>
                        <p class="subtitle">Ingrese el número de documento para consultar información</p>
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

                <!-- BÚSQUEDA -->
                <div class="card">
                    <form method="POST">
                        <div class="search-bar">
                            <input type="text" 
                                   name="dni" 
                                   class="search-input" 
                                   placeholder="Ingrese DNI (8 dígitos)" 
                                   required 
                                   pattern="[0-9]{8}" 
                                   maxlength="8" 
                                   autofocus
                                   value="<?php echo isset($_POST['dni']) ? htmlspecialchars($_POST['dni']) : ''; ?>">
                            <button type="submit" class="btn btn-primary">
                                <span>🔍</span>
                                <span>Buscar</span>
                            </button>
                        </div>
                    </form>
                </div>

                <?php if($socio): ?>
                    <!-- RESULTADO ENCONTRADO -->
                    <div class="card" style="border-left: 6px solid var(--success); animation: slideIn 0.4s ease;">
                        <div class="card-header">
                            <h2 class="card-title" style="color: var(--success);">✅ Socio Encontrado</h2>
                            <span class="badge badge-<?php echo $socio['ESTADO']; ?>">
                                <?php echo strtoupper($socio['ESTADO']); ?>
                            </span>
                        </div>

                        <!-- INFO DEL SOCIO -->
                        <div class="socio-info-grid" style="margin-bottom: var(--space-xl);">
                            <div class="glass-card">
                                <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.25rem;">ID</div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);">#<?php echo $socio['ID']; ?></div>
                            </div>

                            <div class="glass-card">
                                <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.25rem;">DNI</div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);"><?php echo $socio['DNI']; ?></div>
                            </div>

                            <div class="glass-card full-width">
                                <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 0.25rem;">Nombre Completo</div>
                                <div style="font-size: 1.5rem; font-weight: 700; color: var(--text-primary);">
                                    <?php echo $socio['APELLIDOS'] . ', ' . $socio['NOMBRES']; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ESTADÍSTICAS -->
                        <h3 style="font-size: 1.2rem; margin-bottom: var(--space-md); display: flex; align-items: center; gap: 0.5rem;">
                            <span>📊</span>
                            <span>Estadísticas de Asistencia</span>
                        </h3>

                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon">🎯</div>
                                </div>
                                <div class="stat-value"><?php echo $stats['total_visitas']; ?></div>
                                <div class="stat-label">Total de Visitas</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #10B981, #059669);">📅</div>
                                </div>
                                <div class="stat-value"><?php echo $stats['visitas_mes']; ?></div>
                                <div class="stat-label">Últimos 30 Días</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);">🔍</div>
                                </div>
                                <div class="stat-value"><?php echo $stats['por_dni']; ?></div>
                                <div class="stat-label">Por DNI</div>
                            </div>

                            <div class="stat-card">
                                <div class="stat-header">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, #06B6D4, #0891B2);">📱</div>
                                </div>
                                <div class="stat-value"><?php echo $stats['por_qr']; ?></div>
                                <div class="stat-label">Por QR</div>
                            </div>
                        </div>

                        <!-- FECHAS -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-md); margin-top: var(--space-lg);">
                            <div class="glass-card">
                                <div style="display: flex; align-items: center; gap: var(--space-sm);">
                                    <span style="font-size: 2rem;">🕐</span>
                                    <div>
                                        <div style="color: var(--text-muted); font-size: 0.85rem;">Primera Visita</div>
                                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">
                                            <?php 
                                            if ($stats['primera_visita']) {
                                                echo date('d/m/Y H:i', strtotime($stats['primera_visita']));
                                            } else {
                                                echo 'Esta es la primera';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="glass-card">
                                <div style="display: flex; align-items: center; gap: var(--space-sm);">
                                    <span style="font-size: 2rem;">⏰</span>
                                    <div>
                                        <div style="color: var(--text-muted); font-size: 0.85rem;">Última Visita</div>
                                        <div style="font-size: 1.1rem; font-weight: 700; color: var(--text-primary);">
                                            <?php 
                                            if ($stats['ultima_visita']) {
                                                echo date('d/m/Y H:i', strtotime($stats['ultima_visita']));
                                            } else {
                                                echo 'Ahora mismo';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php elseif($error): ?>
                    <!-- SOCIO NO ENCONTRADO -->
                    <div class="card" style="border-left: 6px solid var(--danger); animation: slideIn 0.4s ease;">
                        <div style="text-align: center; padding: var(--space-xl);">
                            <div style="font-size: 4rem; margin-bottom: var(--space-md);">❌</div>
                            <h2 style="font-size: 1.5rem; color: var(--danger); margin-bottom: var(--space-sm);">Socio no encontrado</h2>
                            <p style="color: var(--text-secondary); margin-bottom: var(--space-lg);">
                                No existe ningún socio registrado con el DNI: <strong><?php echo htmlspecialchars($dni_buscar); ?></strong>
                            </p>
                            <?php if ($usuario['rol'] == 'admin'): ?>
                            <a href="agregar_socio_web.php" class="btn btn-primary">
                                <span>➕</span>
                                <span>Registrar nuevo socio</span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

            </div>

<script src="../../js/menu.js"></script>
</body>
</html>