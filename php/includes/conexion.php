<?php
// Archivo de conexión legacy — la conexión activa está en config_session.php (getConexion())
// Se mantiene solo por compatibilidad con vendor/autoload o referencias externas

$servidor   = 'localhost';
$usuario    = 'root';
$password   = '';
$base_datos = 'sistema_socios';

try {
    $conexion = new PDO(
        "mysql:host=$servidor;dbname=$base_datos;charset=utf8",
        $usuario,
        $password
    );
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}