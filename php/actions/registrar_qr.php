<?php
header('Content-Type: application/json');
header("Cache-Control: no-cache");

require_once __DIR__ . '/../includes/config_session.php';

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Sesión expirada. Por favor inicia sesión nuevamente.'
    ]);
    exit;
}

$usuario = getUsuarioActual();

// Verificar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Método no permitido'
    ]);
    exit;
}

// Obtener DNI del body JSON
$input = json_decode(file_get_contents('php://input'), true);
$dni = isset($input['dni']) ? trim($input['dni']) : '';

// Validar DNI
if (empty($dni) || !preg_match('/^[0-9]{8}$/', $dni)) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'DNI inválido: ' . $dni
    ]);
    exit;
}

try {
    $conexion = getConexion();

    // Buscar socio
    $sql = "SELECT * FROM socios WHERE DNI = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([$dni]);
    $socio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$socio) {
        echo json_encode([
            'success' => false,
            'tipo' => 'no_encontrado',
            'mensaje' => 'Socio no encontrado con DNI: ' . $dni
        ]);
        exit;
    }

    // Verificar si ya registró asistencia hoy
    $sql_hoy = "SELECT COUNT(*) FROM asistencias 
                WHERE socio_id = ? 
                AND DATE(fecha_hora) = CURDATE()
                AND tipo_verificacion = 'escaneo_qr'";
    $stmt_hoy = $conexion->prepare($sql_hoy);
    $stmt_hoy->execute([$socio['ID']]);
    $ya_registro_hoy = $stmt_hoy->fetchColumn();

    // Registrar asistencia (aunque ya haya registrado, lo guardamos)
    $sql_asistencia = "INSERT INTO asistencias 
                       (socio_id, tipo_verificacion, verificado_por, ip_acceso) 
                       VALUES (?, 'escaneo_qr', ?, ?)";
    $stmt_asistencia = $conexion->prepare($sql_asistencia);
    $stmt_asistencia->execute([
        $socio['ID'],
        $usuario['id'],
        $_SERVER['REMOTE_ADDR']
    ]);

    // Obtener total de visitas
    $sql_total = "SELECT COUNT(*) FROM asistencias WHERE socio_id = ?";
    $stmt_total = $conexion->prepare($sql_total);
    $stmt_total->execute([$socio['ID']]);
    $total_visitas = $stmt_total->fetchColumn();

    // Determinar color según estado
    $colores = [
        'activo'     => 'success',
        'vitalicio'  => 'info',
        'inactivo'   => 'danger',
        'transeunte' => 'warning',
        'suspendido' => 'danger'
    ];

    $color = $colores[$socio['ESTADO']] ?? 'info';

    // Respuesta exitosa
    echo json_encode([
        'success'       => true,
        'tipo'          => 'encontrado',
        'ya_registro'   => $ya_registro_hoy > 0,
        'color'         => $color,
        'socio' => [
            'id'            => $socio['ID'],
            'dni'           => $socio['DNI'],
            'nombres'       => $socio['NOMBRES'],
            'apellidos'     => $socio['APELLIDOS'],
            'estado'        => $socio['ESTADO'],
            'ingreso'       => $socio['INGRESO'],
            'total_visitas' => $total_visitas,
            'hora_registro' => date('H:i:s'),
            'fecha_registro'=> date('d/m/Y')
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}
?>