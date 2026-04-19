<?php
//configuración de seciones seguras


//configuración de seguridad de sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies',1);
ini_set('session.cookie_secure', 0);

session_start();

function getConexion() {
    $servidor = 'localhost';
    $usuario = 'root';
    $password = '';
    $base_datos = 'sistema_socios';
    
    try {
        $pdo = new PDO("mysql:host=$servidor;dbname=$base_datos;charset=utf8", $usuario, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}
function verificarSesion(){
  if(!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
  }
}
function verificarAdmin(){
  verificarSesion();
  if($_SESSION['rol'] != 'admin') {
    header('Location: index.php');
    exit;
  }
}
function getUsuarioActual(){
  if(!isset($_SESSION['usuario_id'])){
    return null;
  }

  return[
    'id'=> $_SESSION['usuario_id'],
    'usuario' => $_SESSION['usuario'],
    'nombre' => $_SESSION['nombre_completo'],
    'rol' => $_SESSION['rol']
  ];
}
function cerrarSesion(){
  session_destroy();
  header('Location: login.php');
  exit;
}