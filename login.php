<?php

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

session_start(); 

if (isset($_SESSION['usuario_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST'){
    $usuario = trim($_POST['usuario']);
    $password = $_POST['password'];

    if(empty($usuario) || empty($password)){
        $error = 'Por favor complete todos los campos';
    } else {
        try {
            $servidor = 'localhost';
            $user_db = 'root';
            $pass_db = '';
            $base_datos = 'sistema_socios';

            $conexion = new PDO("mysql:host=$servidor;dbname=$base_datos", $user_db, $pass_db);
            $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $sql = "SELECT * FROM usuarios WHERE usuario = ? AND activo = 1";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([$usuario]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['usuario_id'] = $user['id'];
                $_SESSION['usuario'] = $user['usuario'];
                $_SESSION['nombre_completo'] = $user['nombre_completo'];
                $_SESSION['rol'] = $user['rol'];

                $sql_update = "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = ?";
                $stmt_update = $conexion->prepare($sql_update);
                $stmt_update->execute([$user['id']]);

                header('Location: index.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } catch(PDOException $e) {
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
    <link rel="stylesheet" href="css/login.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            
            <!-- HEADER -->
            <div class="login-header">
                <div class="login-logo">
                    🏢
                </div>
                <h1>Bienvenido</h1>
                <p>Sistema de Gestión de Socios</p>
            </div>

            <!-- BODY -->
            <div class="login-body">
                
                <?php if($error): ?>
                    <div class="error-message">
                        ⚠️ <?php echo $error; ?>
                    </div>
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

                    <button type="submit" class="btn-login">
                        Iniciar Sesión
                    </button>
                </form>
            </div>

            <!-- FOOTER -->
            <div class="login-footer">
                <p>© 2025 Sistema de Gestión de Socios. Todos los derechos reservados.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(){
            const passwordInput = document.getElementById('password');
            const toggleButton = document.querySelector('.toggle-password');
            
            if(passwordInput.type === 'password'){
                passwordInput.type = 'text';
                toggleButton.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }

        // Animación del logo
        document.addEventListener('DOMContentLoaded', function() {
            const logo = document.querySelector('.login-logo');
            logo.style.animation = 'float 3s ease-in-out infinite';
        });

        // Efecto de shake en error
        <?php if($error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const errorMsg = document.querySelector('.error-message');
            if(errorMsg) {
                errorMsg.style.animation = 'shake 0.5s ease';
            }
        });
        <?php endif; ?>
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>  