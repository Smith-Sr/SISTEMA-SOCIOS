<?php
header('Content-Type: application/json');
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../includes/config_session.php';

// Verificar método antes que todo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'mensaje' => 'Sesión expirada. Por favor inicia sesión nuevamente.']);
    exit;
}

$usuario = getUsuarioActual();

// Obtener y validar DNI del body JSON
$input = json_decode(file_get_contents('php://input'), true);
$dni   = trim($input['dni'] ?? '');

if (empty($dni) || !preg_match('/^\d{8}$/', $dni)) {
    echo json_encode(['success' => false, 'mensaje' => 'DNI inválido']);
    exit;
}

try {
    $conexion = getConexion();

    // Buscar socio — solo campos necesarios
    $stmt = $conexion->prepare("
        SELECT ID, DNI, NOMBRES, APELLIDOS, ESTADO, INGRESO
        FROM socios
        WHERE DNI = ?
    ");
    $stmt->execute([$dni]);
    $socio = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$socio) {
        echo json_encode([
            'success' => false,
            'tipo'    => 'no_encontrado',
            'mensaje' => 'Socio no encontrado'
        ]);
        exit;
    }

    // Verificar asistencia hoy y obtener total en una sola query
    $stmt_stats = $conexion->prepare("
        SELECT
            COUNT(*) AS total_visitas,
            SUM(DATE(fecha_hora) = CURDATE() AND tipo_verificacion = 'escaneo_qr') AS ya_registro_hoy
        FROM asistencias
        WHERE socio_id = ?
    ");
    $stmt_stats->execute([$socio['ID']]);
    $stats_asistencia = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // Registrar asistencia
    $stmt_insert = $conexion->prepare("
        INSERT INTO asistencias (socio_id, tipo_verificacion, verificado_por, ip_acceso)
        VALUES (?, 'escaneo_qr', ?, ?)
    ");
    $stmt_insert->execute([
        $socio['ID'],
        $usuario['id'],
        $_SERVER['REMOTE_ADDR']
    ]);

    // Color según estado del socio
    $colores = [
        'activo'     => 'success',
        'vitalicio'  => 'info',
        'inactivo'   => 'danger',
        'transeunte' => 'warning',
        'suspendido' => 'danger'
    ];

    echo json_encode([
        'success'     => true,
        'tipo'        => 'encontrado',
        'ya_registro' => $stats_asistencia['ya_registro_hoy'] > 0,
        'color'       => $colores[$socio['ESTADO']] ?? 'info',
        'socio'       => [
            'id'            => $socio['ID'],
            'dni'           => $socio['DNI'],
            'nombres'       => $socio['NOMBRES'],
            'apellidos'     => $socio['APELLIDOS'],
            'estado'        => $socio['ESTADO'],
            'ingreso'       => $socio['INGRESO'],
            'total_visitas' => (int) $stats_asistencia['total_visitas'] + 1,
            'hora_registro' => date('H:i:s'),
            'fecha_registro' => date('d/m/Y')
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error de base de datos: ' . $e->getMessage()
    ]);
}