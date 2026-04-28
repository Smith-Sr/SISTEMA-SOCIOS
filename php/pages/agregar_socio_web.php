<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';
verificarAdmin();
$usuario = getUsuarioActual();

$mensaje      = '';
$tipo_mensaje = '';

// Valores del formulario — vacíos por defecto
$dni       = '';
$apellidos = '';
$nombres   = '';
$ingreso   = '';
$estado    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni       = trim($_POST['dni']       ?? '');
    $apellidos = trim($_POST['apellidos'] ?? '');
    $nombres   = trim($_POST['nombres']   ?? '');
    $ingreso   = trim($_POST['ingreso']   ?? '');
    $estado    = trim($_POST['estado']    ?? '');

    if (empty($dni) || empty($apellidos) || empty($nombres) || empty($ingreso) || empty($estado)) {
        $mensaje      = 'Todos los campos son obligatorios';
        $tipo_mensaje = 'error';
    } elseif (!preg_match('/^\d{8}$/', $dni)) {
        $mensaje      = 'El DNI debe tener exactamente 8 dígitos';
        $tipo_mensaje = 'error';
    } else {
        try {
            $conexion = getConexion();

            // Verificar DNI duplicado
            $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM socios WHERE DNI = ?");
            $stmt_check->execute([$dni]);

            if ($stmt_check->fetchColumn() > 0) {
                $mensaje      = 'Ya existe un socio con ese DNI';
                $tipo_mensaje = 'error';
            } else {
                $stmt_insert = $conexion->prepare("
                    INSERT INTO socios (DNI, APELLIDOS, NOMBRES, INGRESO, ESTADO)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_insert->execute([$dni, $apellidos, $nombres, $ingreso, $estado]);

                $mensaje      = 'Socio agregado exitosamente';
                $tipo_mensaje = 'success';

                // Limpiar formulario tras éxito
                $dni = $apellidos = $nombres = $ingreso = $estado = '';
            }
        } catch (PDOException $e) {
            $mensaje      = 'Error de base de datos: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Helper para marcar option seleccionado
function selected(string $valor, string $actual): string {
    return $valor === $actual ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Socio - Sistema de Gestión</title>
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
        <a href="agregar_socio_web.php" class="menu-item active">
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
            <h1>➕ Agregar Nuevo Socio</h1>
            <p class="subtitle">Complete los datos del socio a registrar</p>
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

    <!-- TOAST — un solo bloque -->
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

    <!-- FORMULARIO -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📝 Datos del Socio</h2>
        </div>

        <form method="POST">
            <div class="grid-form">

                <div class="form-group">
                    <label class="form-label" for="dni">DNI *</label>
                    <input type="text" id="dni" name="dni" class="form-input"
                           required placeholder="8 dígitos"
                           pattern="[0-9]{8}" minlength="8" maxlength="8"
                           title="DNI: exactamente 8 dígitos"
                           value="<?php echo htmlspecialchars($dni); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="apellidos">Apellidos *</label>
                    <input type="text" id="apellidos" name="apellidos" class="form-input"
                           required placeholder="Apellidos completos"
                           minlength="2" maxlength="100"
                           value="<?php echo htmlspecialchars($apellidos); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="nombres">Nombres *</label>
                    <input type="text" id="nombres" name="nombres" class="form-input"
                           required placeholder="Nombres completos"
                           minlength="2" maxlength="100"
                           value="<?php echo htmlspecialchars($nombres); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="ingreso">Fecha de Ingreso *</label>
                    <input type="date" id="ingreso" name="ingreso" class="form-input"
                           required max="<?php echo date('Y-m-d'); ?>"
                           value="<?php echo htmlspecialchars($ingreso); ?>">
                </div>

                <div class="form-group two-columns">
                    <label class="form-label" for="estado">Estado *</label>
                    <select name="estado" id="estado" class="form-select" required>
                        <option value="">-- Seleccione un estado --</option>
                        <option value="activo"      <?php echo selected('activo',      $estado); ?>>Activo</option>
                        <option value="inactivo"    <?php echo selected('inactivo',    $estado); ?>>Inactivo</option>
                        <option value="transeunte"  <?php echo selected('transeunte',  $estado); ?>>Transeunte</option>
                        <option value="vitalicio"   <?php echo selected('vitalicio',   $estado); ?>>Vitalicio</option>
                        <option value="suspendido"  <?php echo selected('suspendido',  $estado); ?>>Suspendido</option>
                    </select>
                </div>

            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <span>💾</span><span>Guardar Socio</span>
                </button>
                <a href="../../index.php" class="btn btn-secondary">
                    <span>↩️</span><span>Cancelar</span>
                </a>
                <button type="reset" class="btn btn-ghost"
                        onclick="return confirm('¿Desea limpiar todos los campos?')">
                    <span>🔄</span><span>Limpiar</span>
                </button>
            </div>
        </form>
    </div>

    <!-- INFORMACIÓN -->
    <div class="glass-card info-card mt-lg">
        <h3 class="section-title"><span>ℹ️</span><span>Información</span></h3>
        <ul class="info-list">
            <li><span>✓</span><span>Todos los campos marcados con (*) son obligatorios</span></li>
            <li><span>✓</span><span>El DNI debe tener exactamente 8 dígitos</span></li>
            <li><span>✓</span><span>La fecha de ingreso no puede ser futura</span></li>
            <li><span>✓</span><span>El sistema verificará que el DNI no esté duplicado</span></li>
        </ul>
    </div>

</div>

<script src="../../js/menu.js"></script>
<script src="../../js/toast.js"></script>
</body>
</html>