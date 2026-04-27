<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarAdmin();
$usuario = getUsuarioActual();

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: gestionar_socios.php');
    exit;
}

$id_socio = $_GET['id'];
$conexion = getConexion();

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
            $sql_verificar = "SELECT COUNT(*) FROM socios WHERE DNI = ? AND ID != ?";
            $verificar = $conexion->prepare($sql_verificar);
            $verificar->execute([$dni, $id_socio]);
            $existe = $verificar->fetchColumn();
            
            if ($existe > 0) {
                $mensaje = 'Ya existe otro socio con ese DNI';
                $tipo_mensaje = 'error';
            } else {
                $sql_actualizar = "UPDATE socios SET DNI = ?, APELLIDOS = ?, NOMBRES = ?, INGRESO = ?, ESTADO = ? WHERE ID = ?";
                $stmt_actualizar = $conexion->prepare($sql_actualizar);
                $resultado = $stmt_actualizar->execute([$dni, $apellidos, $nombres, $ingreso, $estado, $id_socio]);
                
                if ($resultado) {
                    $mensaje = 'Socio actualizado exitosamente';
                    $tipo_mensaje = 'success';
                } else {
                    $mensaje = 'Error al actualizar el socio';
                    $tipo_mensaje = 'error';
                }
            }
        } catch (PDOException $e) {
            $mensaje = 'Error de base de datos: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

$sql_obtener = "SELECT * FROM socios WHERE ID = ?";
$stmt_obtener = $conexion->prepare($sql_obtener);
$stmt_obtener->execute([$id_socio]);
$socio = $stmt_obtener->fetch();

if (!$socio) {
    header('Location: gestionar_socios.php');
    exit;
}

$dni = $socio['DNI'];
$apellidos = $socio['APELLIDOS'];
$nombres = $socio['NOMBRES'];
$ingreso = $socio['INGRESO'];
$estado = $socio['ESTADO'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Socio - Sistema de Gestión</title>
    <link rel="stylesheet" href="../../css/topbar-menu.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../../css/style.css?v=<?php echo time(); ?>">
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
        <a href="escanear_qr.php" class="menu-item">
            <span>📷</span>
            <span>Escanear QR</span>
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
<a href="gestionar_socios.php" class="back-btn" title="Volver">←</a>

<!-- CONTENIDO -->
        <div class="container">
            <!-- TOPBAR -->
            <div class="topbar">
                <div class="topbar-left">
                    <h1>✏️ Editar Socio</h1>
                    <p class="subtitle">Modificar datos del socio ID: #<?php echo $id_socio; ?></p>
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

            <!-- INFO ACTUAL -->
            <div class="glass-card" style="margin-bottom: var(--space-lg);">
                <h3 style="margin-bottom: var(--space-md); display: flex; align-items: center; gap: 0.5rem;">
                    <span>👤</span>
                    <span>Información Actual</span>
                </h3>
                <div
                    style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-md);">
                    <div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">DNI Actual</div>
                        <div style="font-size: 1.2rem; font-weight: 700;"><?php echo $socio['DNI']; ?></div>
                    </div>
                    <div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Estado Actual</div>
                        <span class="badge badge-<?php echo $socio['ESTADO']; ?>">
                            <?php echo strtoupper($socio['ESTADO']); ?>
                        </span>
                    </div>
                    <div>
                        <div style="color: var(--text-muted); font-size: 0.85rem;">Nombre Actual</div>
                        <div style="font-size: 1rem; font-weight: 600;">
                            <?php echo $socio['APELLIDOS'] . ', ' . $socio['NOMBRES']; ?></div>
                    </div>
                </div>
            </div>

            <!-- FORMULARIO -->
            <div class="card">
                <div class="card-header">
                    <h2 class="card-title">📝 Editar Datos</h2>
                </div>

                <form method="POST">
                    <div
                        div class="grid-form">

                        <div class="form-group">
                            <label class="form-label" for="dni">DNI / Documento de Identidad *</label>
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
                                max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($ingreso); ?>">
                        </div>

                        <div class="form-group two-columns">
                            <label class="form-label" for="estado">Estado *</label>
                            <select name="estado" id="estado" class="form-select" required>
                                <option value="">--Seleccione un estado--</option>
                                <option value="activo" <?php echo ($estado == 'activo') ? 'selected' : ''; ?>>Activo
                                </option>
                                <option value="inactivo" <?php echo ($estado == 'inactivo') ? 'selected' : ''; ?>>
                                    Inactivo</option>
                                <option value="transeunte" <?php echo ($estado == 'transeunte') ? 'selected' : ''; ?>>
                                    Transeunte</option>
                                <option value="vitalicio" <?php echo ($estado == 'vitalicio') ? 'selected' : ''; ?>>
                                    Vitalicio</option>
                                <option value="suspendido" <?php echo ($estado == 'suspendido') ? 'selected' : ''; ?>>
                                    Suspendido</option>
                            </select>
                        </div>
                    </div>

                    <div style="display: flex; gap: var(--space-md); margin-top: var(--space-lg); flex-wrap: wrap;">
                        <button type="submit" class="btn btn-primary">
                            <span>💾</span>
                            <span>Actualizar Socio</span>
                        </button>
                        <a href="gestionar_socios.php" class="btn btn-secondary">
                            <span>↩️</span>
                            <span>Volver a Gestionar</span>
                        </a>
                    </div>
                </form>
            </div>

            <!-- ADVERTENCIA -->
            <div class="alert alert-warning" style="margin-top: var(--space-lg);">
                <span>⚠️</span>
                <div>
                    <strong>Importante:</strong> Los cambios realizados serán permanentes. Asegúrese de verificar la
                    información antes de guardar.
                </div>
            </div>

        </div>
<script src="../../js/toast.js"></script>
<script src="../../js/menu.js"></script>
</body>
</html>