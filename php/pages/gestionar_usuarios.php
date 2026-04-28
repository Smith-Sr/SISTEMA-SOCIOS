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

// Procesar creación de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_usuario'])) {
    $nuevo_usuario  = trim($_POST['nuevo_usuario']  ?? '');
    $nueva_password = trim($_POST['nueva_password'] ?? '');
    $nuevo_nombre   = trim($_POST['nuevo_nombre']   ?? '');
    $nuevo_rol      = $_POST['nuevo_rol'] ?? 'agente';

    if (empty($nuevo_usuario) || empty($nueva_password) || empty($nuevo_nombre) || empty($nuevo_rol)) {
        $mensaje      = 'Todos los campos son obligatorios';
        $tipo_mensaje = 'error';
    } elseif (strlen($nueva_password) < 8) {
        $mensaje      = 'La contraseña debe tener al menos 8 caracteres';
        $tipo_mensaje = 'error';
    } else {
        try {
            $stmt_check = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE usuario = ?");
            $stmt_check->execute([$nuevo_usuario]);

            if ($stmt_check->fetchColumn() > 0) {
                $mensaje      = 'El nombre de usuario ya existe';
                $tipo_mensaje = 'error';
            } else {
                $conexion->prepare("
                    INSERT INTO usuarios (usuario, password, nombre_completo, rol)
                    VALUES (?, ?, ?, ?)
                ")->execute([
                    $nuevo_usuario,
                    password_hash($nueva_password, PASSWORD_DEFAULT),
                    $nuevo_nombre,
                    $nuevo_rol
                ]);

                $mensaje      = "Usuario '$nuevo_usuario' creado exitosamente";
                $tipo_mensaje = 'success';
            }
        } catch (PDOException $e) {
            $mensaje      = 'Error al crear usuario: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {
    $id_cambiar   = (int) ($_POST['id_usuario']   ?? 0);
    $nuevo_estado = (int) ($_POST['nuevo_estado'] ?? 0);

    try {
        $conexion->prepare("UPDATE usuarios SET activo = ? WHERE id = ?")
                 ->execute([$nuevo_estado, $id_cambiar]);

        $mensaje      = 'Estado actualizado correctamente';
        $tipo_mensaje = 'success';
    } catch (PDOException $e) {
        $mensaje      = 'Error al cambiar estado: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener usuarios — solo columnas necesarias
$usuarios_lista = $conexion->query("
    SELECT id, usuario, nombre_completo, rol, activo, ultimo_acceso
    FROM usuarios
    ORDER BY id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Sistema de Gestión</title>
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
        <a href="gestionar_socios.php" class="menu-item">
            <span>✏️</span><span>Gestionar Socios</span>
        </a>
        <a href="gestionar_usuarios.php" class="menu-item active">
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
            <h1>👥 Gestionar Usuarios</h1>
            <p class="subtitle">Administrar cuentas de acceso al sistema</p>
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

    <!-- FORMULARIO CREAR USUARIO -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">➕ Crear Nuevo Usuario</h2>
        </div>
        <form method="POST" class="form-usuarios">
            <div class="grid-form">
                <div class="form-group">
                    <label class="form-label" for="nuevo_usuario">Usuario *</label>
                    <input type="text" id="nuevo_usuario" name="nuevo_usuario" class="form-input"
                           required placeholder="ej: portero_juan"
                           pattern="[a-zA-Z0-9_]{3,50}"
                           title="Solo letras, números y guión bajo. Mínimo 3 caracteres.">
                </div>

                <div class="form-group">
                    <label class="form-label" for="nueva_password">Contraseña *</label>
                    <div class="password-wrapper">
                        <input type="password" id="nueva_password" name="nueva_password" class="form-input"
                               required minlength="8" placeholder="Mínimo 8 caracteres">
                        <button type="button" class="toggle-password-btn" onclick="togglePassword()" aria-label="Mostrar contraseña">
                            👁️
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="nuevo_nombre">Nombre Completo *</label>
                    <input type="text" id="nuevo_nombre" name="nuevo_nombre" class="form-input"
                           required placeholder="Nombre completo del usuario">
                </div>

                <div class="form-group">
                    <label class="form-label" for="nuevo_rol">Rol *</label>
                    <select name="nuevo_rol" id="nuevo_rol" class="form-select" required>
                        <option value="">-- Seleccione --</option>
                        <option value="agente">Agente (solo consulta)</option>
                        <option value="admin">Admin (acceso total)</option>
                    </select>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="crear_usuario" class="btn btn-primary">
                    <span>➕</span><span>Crear Usuario</span>
                </button>
            </div>
        </form>
    </div>

    <!-- LISTA DE USUARIOS -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">📋 Usuarios Registrados</h2>
        </div>

        <?php if (count($usuarios_lista) > 0): ?>
        <div class="table-container">
            <table class="table-premium">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
                        <th>Nombre Completo</th>
                        <th>Rol</th>
                        <th>Estado</th>
                        <th>Último Acceso</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios_lista as $user): ?>
                    <tr>
                        <td><strong>#<?php echo $user['id']; ?></strong></td>
                        <td><?php echo htmlspecialchars($user['usuario']); ?></td>
                        <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $user['rol'] ?? 'agente'; ?>">
                                <?php echo strtoupper($user['rol'] ?? 'Sin rol'); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $user['activo'] ? 'activo' : 'inactivo'; ?>">
                                <?php echo $user['activo'] ? 'ACTIVO' : 'INACTIVO'; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo $user['ultimo_acceso']
                                ? date('d/m/Y H:i', strtotime($user['ultimo_acceso']))
                                : '<span class="text-muted">Nunca</span>'; ?>
                        </td>
                        <td>
                            <?php if ($user['id'] != $usuario['id']): ?>
                            <form method="POST" onsubmit="return confirm('¿Cambiar estado de este usuario?')">
                                <input type="hidden" name="id_usuario"   value="<?php echo $user['id']; ?>">
                                <input type="hidden" name="nuevo_estado" value="<?php echo $user['activo'] ? 0 : 1; ?>">
                                <button type="submit" name="cambiar_estado"
                                        class="btn btn-sm <?php echo $user['activo'] ? 'btn-danger' : 'btn-success'; ?>">
                                    <?php echo $user['activo'] ? '🚫 Desactivar' : '✅ Activar'; ?>
                                </button>
                            </form>
                            <?php else: ?>
                            <span class="text-muted">(Tú mismo)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">👤</div>
            <h3>No hay usuarios registrados</h3>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="../../js/menu.js"></script>
<script src="../../js/toast.js"></script>
<script>
    function togglePassword() {
        const input = document.getElementById('nueva_password');
        const btn   = document.querySelector('.toggle-password-btn');
        const isPass = input.type === 'password';
        input.type      = isPass ? 'text' : 'password';
        btn.textContent = isPass ? '🙈' : '👁️';
    }
</script>
</body>
</html>