<?php
$servidor = 'localhost';
$usuario = 'root';
$password = '';
$base_datos = 'sistema_socios';

//Creación de un objeto PDO que permite hablar con la base de datos
//Se conecta al servidor MySQL especificado en $servidor
//Accede a la base de datos llamada $base_datos
//Usa las credenciales $usuario y $password para autenticarse 
$conexion = new PDO("mysql:host=$servidor;dbname=$base_datos", $usuario, $password);
echo "👍 ¡Conexión exitosa a la base de datos!";

// 