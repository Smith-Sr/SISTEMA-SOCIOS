<?php
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start();

// Si ya hay sesión activa, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($usuario) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        try {
            $conexion = new PDO(
                "mysql:host=localhost;dbname=sistema_socios;charset=utf8",
                'root',
                ''
            );
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE usuario = ? AND activo = 1");
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['usuario_id']      = $user['id'];
                $_SESSION['usuario']         = $user['usuario'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['rol']             = $user['rol'];

                $stmt_update = $conexion->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?");
                $stmt_update->execute([$user['id']]);

                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch (PDOException $e) {
            $error = 'Error de conexión: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestión de Socios</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-card">

            <div class="login-header">
                <div class="login-logo">🏢</div>
                <h1>Bienvenido</h1>
                <p>Sistema de Gestión de Socios</p>
            </div>

            <div class="login-body">

                <?php if ($error): ?>
                    <div class="error-message">⚠️ <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-group-login">
                        <label for="usuario">Usuario</label>
                        <input type="text"
                               id="usuario"
                               name="usuario"
                               required
                               autofocus
                               placeholder="Ingrese su usuario">
                        <span class="input-icon">👤</span>
                    </div>

                    <div class="form-group-login">
                        <label for="password">Contraseña</label>
                        <input type="password"
                               id="password"
                               name="password"
                               required
                               placeholder="Ingrese su contraseña">
                        <span class="input-icon">🔒</span>
                        <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                    </div>

                    <button type="submit" class="btn-login">Iniciar Sesión</button>
                </form>

                <div class="divider"><span>Usuarios de prueba</span></div>

                <div class="usuario-prueba">
                    <h4>Credenciales de Acceso</h4>
                    <p><strong>Administrador:</strong> <code>admin</code> / <code>Admin123</code></p>
                    <p><strong>Agente:</strong> <code>agente</code> / <code>Agente123</code></p>
                </div>
            </div>

            <div class="login-footer">
                <p>© 2025 Sistema de Gestión de Socios. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input  = document.getElementById('password');
            const btn    = document.querySelector('.toggle-password');
            const isPass = input.type === 'password';
            input.type   = isPass ? 'text' : 'password';
            btn.textContent = isPass ? '🙈' : '👁️';
        }
    </script>
</body>
</html>