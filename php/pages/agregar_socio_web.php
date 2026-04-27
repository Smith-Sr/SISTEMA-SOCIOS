<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarAdmin();
$usuario = getUsuarioActual();

$mensaje = '';
$tipo_mensaje = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $dni = $_POST['dni'];
    $apellidos = $_POST['apellidos'];
    $nombres = $_POST['nombres'];
    $ingreso = $_POST['ingreso'];
    $estado = $_POST['estado'];
    
    if (empty($dni) || empty($apellidos) || empty($nombres) || empty($ingreso)) {
        $mensaje = 'Todos los campos son obligatorios';
        $tipo_mensaje = 'error';
    } else {
        try {
            $conexion = getConexion();
            
            $sql_verificar = "SELECT COUNT(*) FROM socios WHERE DNI = ?";
            $verificar = $conexion->prepare($sql_verificar);
            $verificar->execute([$dni]);
            $existe = $verificar->fetchColumn();
            
            if ($existe > 0) {
                $mensaje = 'Ya existe un socio con ese DNI';
                $tipo_mensaje = 'error';
            } else {
                $sql = "INSERT INTO socios (DNI, APELLIDOS, NOMBRES, INGRESO, ESTADO) VALUES (?, ?, ?, ?, ?)";
                $consulta = $conexion->prepare($sql);
                $resultado = $consulta->execute([$dni, $apellidos, $nombres, $ingreso, $estado]);
                
                if ($resultado) {
                    $mensaje = 'Socio agregado exitosamente';
                    $tipo_mensaje = 'success';
                    $dni = '';
                    $apellidos = '';
                    $nombres = '';
                    $ingreso = '';
                    $estado = '';
                } else {
                    $mensaje = 'Error al agregar el socio';
                    $tipo_mensaje = 'error';
                }
            }
        } catch (PDOException $e) {
            $mensaje = 'Error de base de datos: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

if (!isset($dni)) $dni = '';
if (!isset($apellidos)) $apellidos = '';
if (!isset($nombres)) $nombres = '';
if (!isset($ingreso)) $ingreso = '';
if (!isset($estado)) $estado = '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Socio - Sistema de Gestión</title>
    <link rel="stylesheet" href="../../css/topbar-menu.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/style.css?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

<?php if ($mensaje != ''): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        toast.<?php echo $tipo_mensaje == 'success' ? 'success' : 'error'; ?>('<?php echo $mensaje; ?>');
    });
</script>
<?php endif; ?>

<!-- TOPBAR FIJO -->
<div class="topbar-fixed">
    <div class="topbar-logo">
        <span>🏢</span>
        <span>SociosApp</span>
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
        <a href="escanear_qr.php" class="menu-item">
            <span>📷</span>
            <span>Escanear QR</span>
        </a>
    </div>
    
    <div class="menu-section">
        <div class="menu-title">⚙️ Administración</div>
        <a href="agregar_socio_web.php" class="menu-item active">
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
                    <button class="btn-menu" onclick="toggleSidebar()">
                        ☰
                    </button>

                    <div class="topbar-left">
                        <h1>➕ Agregar Nuevo Socio</h1>
                        <p class="subtitle">Complete los datos del socio a registrar</p>
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
<script>
    document.addEventListener('DOMContentLoaded', () => {
        <?php if ($tipo_mensaje == 'success'): ?>
            toast.success('<?php echo addslashes($mensaje); ?>');
        <?php elseif ($tipo_mensaje == 'error'): ?>
            toast.error('<?php echo addslashes($mensaje); ?>');
        <?php elseif ($tipo_mensaje == 'warning'): ?>
            toast.warning('<?php echo addslashes($mensaje); ?>');
        <?php else: ?>
            toast.info('<?php echo addslashes($mensaje); ?>');
        <?php endif; ?>
    });
</script>
<?php endif; ?>

                <!-- FORMULARIO -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">📝 Datos del Socio</h2>
                    </div>

                    <form method="POST">
                        <div class="grid-form">

                            <div class="form-group">
                                <label class="form-label" for="dni">DNI *</label>
                                <input type="text" id="dni" name="dni" class="form-input" required
                                    placeholder="Ingrese el número de documento" pattern="[0-9]{8}" minlength="8"
                                    maxlength="8" title="DNI: 8 dígitos" value="<?php echo htmlspecialchars($dni); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="apellidos">Apellidos *</label>
                                <input type="text" id="apellidos" name="apellidos" class="form-input" required
                                    placeholder="Apellidos completos" minlength="2" maxlength="100"
                                    value="<?php echo htmlspecialchars($apellidos); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="nombres">Nombres *</label>
                                <input type="text" id="nombres" name="nombres" class="form-input" required
                                    placeholder="Nombres completos" minlength="2" maxlength="100"
                                    value="<?php echo htmlspecialchars($nombres); ?>">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="ingreso">Fecha de Ingreso *</label>
                                <input type="date" id="ingreso" name="ingreso" class="form-input" required
                                    max="<?php echo date('Y-m-d'); ?>"
                                    value="<?php echo htmlspecialchars($ingreso); ?>">
                            </div>

                            <div class="form-group two-columns">
                                <label class="form-label" for="estado">Estado *</label>
                                <select name="estado" id="estado" class="form-select" required>
                                    <option value="">--Seleccione un estado--</option>
                                    <option value="activo"
                                        <?php echo (!isset($estado) || $estado == 'activo') ? 'selected' : ''; ?>>Activo
                                    </option>
                                    <option value="inactivo"
                                        <?php echo (isset($estado) && $estado == 'inactivo') ? 'selected' : ''; ?>>
                                        Inactivo</option>
                                    <option value="transeunte"
                                        <?php echo (isset($estado) && $estado == 'transeunte') ? 'selected' : ''; ?>>
                                        Transeunte</option>
                                    <option value="vitalicio"
                                        <?php echo (isset($estado) && $estado == 'vitalicio') ? 'selected' : ''; ?>>
                                        Vitalicio</option>
                                    <option value="suspendido"
                                        <?php echo (isset($estado) && $estado == 'suspendido') ? 'selected' : ''; ?>>
                                        Suspendido</option>
                                </select>
                            </div>
                        </div>

                        <div style="display: flex; gap: var(--space-md); margin-top: var(--space-lg); flex-wrap: wrap;">
                            <button type="submit" class="btn btn-primary">
                                <span>💾</span>
                                <span>Guardar Socio</span>
                            </button>
                            <a href="../../index.php" class="btn btn-secondary">
                                <span>↩️</span>
                                <span>Cancelar</span>
                            </a>
                            <button type="reset" class="btn btn-ghost"
                                onclick="return confirm('¿Desea limpiar todos los campos?')">
                                <span>🔄</span>
                                <span>Limpiar</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- INFORMACIÓN ADICIONAL -->
                <div class="glass-card" style="margin-top: var(--space-lg);">
                    <h3 style="margin-bottom: var(--space-sm); display: flex; align-items: center; gap: 0.5rem;">
                        <span>ℹ️</span>
                        <span>Información</span>
                    </h3>
                    <ul style="list-style: none; padding: 0; display: grid; gap: 0.5rem;">
                        <li style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>✓</span>
                            <span>Todos los campos marcados con (*) son obligatorios</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>✓</span>
                            <span>El DNI debe tener exactamente 8 dígitos</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>✓</span>
                            <span>La fecha de ingreso no puede ser futura</span>
                        </li>
                        <li style="display: flex; align-items: center; gap: 0.5rem;">
                            <span>✓</span>
                            <span>El sistema verificará que el DNI no esté duplicado</span>
                        </li>
                    </ul>
                </div>

            </div>
        

<script src="../../js/menu.js"></script>
<script src="../../js/toast.js"></script>
</body>
</html>