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

// Procesar creación de nuevo usuario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_usuario'])) {
    $nuevo_usuario = trim($_POST['nuevo_usuario']);
    $nueva_password = $_POST['nueva_password'];
    $nuevo_nombre = trim($_POST['nuevo_nombre']);
    $nuevo_rol = isset($_POST['nuevo_rol']) ? $_POST['nuevo_rol'] : 'agente';
    
    if (empty($nuevo_usuario) || empty($nueva_password) || empty($nuevo_nombre)) {
        $mensaje = 'Todos los campos son obligatorios';
        $tipo_mensaje = 'error';
    }
    elseif (strlen($nueva_password) < 8) {
        $mensaje = 'La contraseña debe tener al menos 8 caracteres';
        $tipo_mensaje = 'error';
    }
    else{
        try{
            $sql_verificar = "SELECT COUNT(*) FROM usuarios WHERE usuario = ?";
            $stmt = $conexion->prepare($sql_verificar);
            $stmt->execute([$nuevo_usuario]);
            $existe = $stmt->fetchColumn();
            
            if ($existe > 0){
                $mensaje = 'El nombre de usuario ya existe';
                $tipo_mensaje = 'error';
            }
            else {
                $password_hash = password_hash($nueva_password, PASSWORD_DEFAULT);
                
                $sql_insertar = "INSERT INTO usuarios (usuario, password, nombre_completo, rol) VALUES (?, ?, ?, ?)";
                $stmt_insertar = $conexion->prepare($sql_insertar);
                $resultado = $stmt_insertar->execute([$nuevo_usuario, $password_hash, $nuevo_nombre, $nuevo_rol]);

                if ($resultado) {
                    $mensaje = "Usuario '$nuevo_usuario' creado exitosamente";
                    $tipo_mensaje = 'success';
                }
            }
        } catch (PDOException $e){
            $mensaje = 'Error al crear usuario: ' . $e->getMessage();
            $tipo_mensaje = 'error';
        }
    }
}

// Procesar cambio de estado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cambiar_estado'])){
    $id_usuario = $_POST['id_usuario'];
    $nuevo_estado = $_POST['nuevo_estado'];

    try {
        $sql_estado = "UPDATE usuarios SET activo = ? WHERE id = ?";
        $stmt_estado = $conexion->prepare($sql_estado);
        $stmt_estado->execute([$nuevo_estado, $id_usuario]);

        $mensaje = 'Estado actualizado correctamente';
        $tipo_mensaje = 'success';
    } catch (PDOException $e) {
        $mensaje = 'Error al cambiar estado: ' . $e->getMessage();
        $tipo_mensaje = 'error';
    }
}

// Obtener todos los usuarios
$sql_usuarios = "SELECT * FROM usuarios ORDER BY id DESC";
$stmt_usuarios = $conexion->prepare($sql_usuarios);
$stmt_usuarios->execute();
$usuarios = $stmt_usuarios->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar Usuarios - Sistema de Gestión</title>
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
        <a href="gestionar_socios.php" class="menu-item">
            <span>✏️</span>
            <span>Gestionar Socios</span>
        </a>
        <a href="gestionar_usuarios.php" class="menu-item active">
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
                        <h1>👥 Gestionar Usuarios</h1>
                        <p class="subtitle">Administrar cuentas de acceso al sistema</p>
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
                <?php if ($mensaje): ?>
                <div class="alert alert-<?php echo $tipo_mensaje; ?>">
                    <?php 
                        $icon = $tipo_mensaje == 'success' ? '✅' : '❌';
                        echo $icon . ' ' . $mensaje; 
                        ?>
                </div>
                <?php endif; ?>

                <!-- FORMULARIO CREAR USUARIO -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">➕ Crear Nuevo Usuario</h2>
                    </div>

                    <form method="POST" style="max-width: 800px;">
                        <div
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-md);">
                            <div class="form-group">
                                <label class="form-label" for="nuevo_usuario">Usuario *</label>
                                <input type="text" id="nuevo_usuario" name="nuevo_usuario" class="form-input" required
                                    placeholder="ej: portero_yhefri" pattern="[a-zA-Z0-9_]{3,50}"
                                    title="Solo letras, números y guión bajo. Mínimo 3 caracteres.">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="nueva_password">Contraseña *</label>
                                <div style="position: relative;">
                                    <input type="password" id="nueva_password" name="nueva_password" class="form-input"
                                        required minlength="8" placeholder="Mínimo 8 caracteres">
                                    <button type="button" class="toggle-password" onclick="togglePassword()"
                                        style="position: absolute; right: 1rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1.2rem;">
                                        👁️
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="nuevo_nombre">Nombre Completo *</label>
                                <input type="text" id="nuevo_nombre" name="nuevo_nombre" class="form-input" required
                                    placeholder="Nombre completo del usuario">
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="nuevo_rol">Rol *</label>
                                <select name="nuevo_rol" id="nuevo_rol" class="form-select" required>
                                    <option value="">--Seleccione--</option>
                                    <option value="agente">Agente (solo consulta)</option>
                                    <option value="admin">Admin (acceso total)</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-top: var(--space-md);">
                            <button type="submit" name="crear_usuario" class="btn btn-primary">
                                <span>➕</span>
                                <span>Crear Usuario</span>
                            </button>
                        </div>
                    </form>
                </div>

                <!-- LISTA DE USUARIOS -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="card-title">📋 Usuarios Registrados</h2>
                    </div>

                    <?php if (count($usuarios) > 0): ?>
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
                                <?php foreach ($usuarios as $user): ?>
                                <tr>
                                    <td><strong>#<?php echo $user['id']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($user['nombre_completo']); ?></td>
                                    <td>
                                        <?php if (isset($user['rol'])): ?>
                                        <span class="badge badge-<?php echo $user['rol']; ?>">
                                            <?php echo strtoupper($user['rol']); ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="badge">Sin rol</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span
                                            class="badge badge-<?php echo $user['activo'] ? 'activo' : 'inactivo'; ?>">
                                            <?php echo $user['activo'] ? 'ACTIVO' : 'INACTIVO'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($user['ultimo_acceso']) {
                                            echo date('d/m/Y H:i', strtotime($user['ultimo_acceso']));
                                        } else {
                                            echo '<span style="color: var(--text-muted);">Nunca</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user['id'] != $usuario['id']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="id_usuario" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="nuevo_estado"
                                                value="<?php echo $user['activo'] ? 0 : 1; ?>">
                                            <button type="submit" name="cambiar_estado"
                                                class="btn <?php echo $user['activo'] ? 'btn-danger' : 'btn-success'; ?>"
                                                style="padding: 0.5rem 1rem; font-size: 0.85rem;"
                                                onclick="return confirm('¿Cambiar estado de este usuario?');">
                                                <?php echo $user['activo'] ? '🚫 Desactivar' : '✅ Activar'; ?>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span style="color: var(--text-muted); font-size: 0.85rem;">(Tú mismo)</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div style="text-align: center; padding: var(--space-xl);">
                        <p style="color: var(--text-secondary);">No hay usuarios registrados</p>
                    </div>
                    <?php endif; ?>
                </div>

            </div>

<script src="../../js/menu.js"></script>
    <script>
    function togglePassword() {
        const passwordInput = document.getElementById('nueva_password');
        const toggleButton = document.querySelector('.toggle-password');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleButton.textContent = '🙈';
        } else {
            passwordInput.type = 'password';
            toggleButton.textContent = '👁️';
        }
    }
    </script>
</body>
</html>